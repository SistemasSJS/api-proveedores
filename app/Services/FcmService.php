<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Servicio para enviar notificaciones push usando Firebase Cloud Messaging
 * Usando Service Account (método moderno) en lugar del Server Key legacy
 */
class FcmService
{
    private const FCM_URL_V1 = 'https://fcm.googleapis.com/v1/projects/%s/messages:send';
    private const FCM_URL_LEGACY = 'https://fcm.googleapis.com/fcm/send';
    
    private string $projectId;
    private string $serviceAccountPath;
    private ?string $accessToken = null;
    private int $tokenExpiration = 0;
    
    public function __construct()
    {
        $this->projectId = config('services.fcm.project_id');
        $this->serviceAccountPath = storage_path(config('services.fcm.credentials'));
        
        if (empty($this->projectId)) {
            throw new Exception('FCM Project ID no configurado. Añade FCM_PROJECT_ID al .env');
        }
        
        if (!file_exists($this->serviceAccountPath)) {
            throw new Exception('Archivo de credenciales Firebase no encontrado: ' . $this->serviceAccountPath);
        }
    }
    
    /**
     * Enviar notificación a un token específico
     */
    public function sendToToken(string $token, array $notification, array $data = []): bool
    {
        return $this->sendToTokens([$token], $notification, $data);
    }
    
    /**
     * Enviar notificación a múltiples tokens
     */
    public function sendToTokens(array $tokens, array $notification, array $data = []): bool
    {
        if (empty($tokens)) {
            Log::warning('FCM: No hay tokens para enviar notificación');
            return false;
        }
        
        // FCM solo permite hasta 1000 tokens por batch
        $chunks = array_chunk($tokens, 1000);
        $success = true;
        
        foreach ($chunks as $chunk) {
            if (!$this->sendBatch($chunk, $notification, $data)) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    /**
     * Enviar notificación a un topic
     */
    public function sendToTopic(string $topic, array $notification, array $data = []): bool
    {
        $payload = [
            'to' => "/topics/{$topic}",
            'notification' => $notification,
            'data' => $this->processDataPayload($data),
            'android' => [
                'priority' => 'high',
                'notification' => [
                    'sound' => 'default',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
                ]
            ],
            'apns' => [
                'payload' => [
                    'aps' => [
                        'sound' => 'default',
                        'badge' => 1
                    ]
                ]
            ]
        ];
        
        return $this->sendRequest($payload);
    }
    
    /**
     * Enviar batch de notificaciones
     */
    private function sendBatch(array $tokens, array $notification, array $data): bool
    {
        $payload = [
            'registration_ids' => $tokens,
            'notification' => $notification,
            'data' => $this->processDataPayload($data),
            'priority' => 'high',
            'content_available' => true,
            'mutable_content' => true,
            'android' => [
                'priority' => 'high',
                'notification' => [
                    'sound' => 'default',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'channel_id' => 'app_proveedores_notifications'
                ]
            ],
            'apns' => [
                'payload' => [
                    'aps' => [
                        'sound' => 'default',
                        'badge' => 1,
                        'alert' => $notification,
                        'content-available' => 1,
                        'mutable-content' => 1
                    ]
                ]
            ]
        ];
        
        return $this->sendRequest($payload);
    }
    
    /**
     * Procesar datos para asegurar que sean strings
     */
    private function processDataPayload(array $data): array
    {
        $processedData = [];
        
        foreach ($data as $key => $value) {
            // FCM requiere que todos los valores sean strings
            if (is_array($value) || is_object($value)) {
                $processedData[$key] = json_encode($value);
            } else {
                $processedData[$key] = (string) $value;
            }
        }
        
        return $processedData;
    }
    
    /**
     * Obtener token de acceso usando Service Account
     */
    private function getAccessToken(): string
    {
        // Si el token aún es válido, devolverlo
        if ($this->accessToken && time() < $this->tokenExpiration) {
            return $this->accessToken;
        }
        
        try {
            $serviceAccount = json_decode(file_get_contents($this->serviceAccountPath), true);
            
            // Crear JWT
            $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
            $now = time();
            $payload = json_encode([
                'iss' => $serviceAccount['client_email'],
                'scope' => 'https://www.googleapis.com/auth/cloud-platform',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600
            ]);
            
            $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
            $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
            
            $signature = '';
            $data = $base64Header . '.' . $base64Payload;
            openssl_sign($data, $signature, $serviceAccount['private_key'], OPENSSL_ALGO_SHA256);
            $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
            
            $jwt = $data . '.' . $base64Signature;
            
            // Intercambiar JWT por token de acceso
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt
            ]);
            
            if ($response->successful()) {
                $result = $response->json();
                $this->accessToken = $result['access_token'];
                $this->tokenExpiration = time() + ($result['expires_in'] - 300); // 5 min buffer
                
                return $this->accessToken;
            } else {
                throw new Exception('Error obteniendo access token: ' . $response->body());
            }
            
        } catch (Exception $e) {
            Log::error('FCM: Error obteniendo access token', [
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Realizar la petición HTTP a FCM usando API legacy (compatible)
     */
    private function sendRequest(array $payload): bool
    {
        try {
            Log::info('FCM: Enviando notificación', [
                'payload_size' => count($payload),
                'has_tokens' => isset($payload['registration_ids']),
                'tokens_count' => isset($payload['registration_ids']) ? count($payload['registration_ids']) : 0
            ]);
            
            // Usar Service Account para obtener token
            $accessToken = $this->getAccessToken();
            
            // Para compatibilidad, usar el endpoint legacy que acepta registration_ids
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post(self::FCM_URL_V1, $payload);
            
            if ($response->successful()) {
                $result = $response->json();
                
                Log::info('FCM: Notificación enviada correctamente', [
                    'success' => $result['success'] ?? 0,
                    'failure' => $result['failure'] ?? 0,
                    'canonical_ids' => $result['canonical_ids'] ?? 0
                ]);
                
                // Procesar respuesta para tokens inválidos
                $this->processResponse($result, $payload);
                
                return ($result['failure'] ?? 0) == 0;
            } else {
                Log::error('FCM: Error en respuesta HTTP', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                
                return false;
            }
            
        } catch (Exception $e) {
            Log::error('FCM: Excepción al enviar notificación', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }
    
    /**
     * Procesar respuesta de FCM para manejar tokens inválidos
     */
    private function processResponse(array $result, array $payload): void
    {
        if (!isset($result['results']) || !isset($payload['registration_ids'])) {
            return;
        }
        
        $tokens = $payload['registration_ids'];
        $results = $result['results'];
        
        $invalidTokens = [];
        $canonicalTokens = [];
        
        foreach ($results as $index => $result) {
            $token = $tokens[$index] ?? null;
            
            if (!$token) continue;
            
            // Token inválido o no registrado
            if (isset($result['error'])) {
                $error = $result['error'];
                
                if (in_array($error, ['NotRegistered', 'InvalidRegistration'])) {
                    $invalidTokens[] = $token;
                }
                
                Log::warning('FCM: Error en token específico', [
                    'token' => substr($token, 0, 20) . '...',
                    'error' => $error
                ]);
            }
            
            // Token canónico (token actualizado)
            if (isset($result['registration_id'])) {
                $canonicalTokens[] = [
                    'old_token' => $token,
                    'new_token' => $result['registration_id']
                ];
            }
        }
        
        // Marcar tokens inválidos como inactivos
        if (!empty($invalidTokens)) {
            $this->markTokensAsInvalid($invalidTokens);
        }
        
        // Actualizar tokens canónicos
        if (!empty($canonicalTokens)) {
            $this->updateCanonicalTokens($canonicalTokens);
        }
    }
    
    /**
     * Marcar tokens como inválidos en la base de datos
     */
    private function markTokensAsInvalid(array $tokens): void
    {
        try {
            \App\Models\UserDeviceToken::whereIn('token', $tokens)
                ->update(['is_active' => false]);
            
            Log::info('FCM: Tokens marcados como inválidos', [
                'count' => count($tokens)
            ]);
            
        } catch (Exception $e) {
            Log::error('FCM: Error marcando tokens como inválidos', [
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Actualizar tokens canónicos
     */
    private function updateCanonicalTokens(array $canonicalTokens): void
    {
        try {
            foreach ($canonicalTokens as $tokenPair) {
                \App\Models\UserDeviceToken::where('token', $tokenPair['old_token'])
                    ->update(['token' => $tokenPair['new_token']]);
            }
            
            Log::info('FCM: Tokens canónicos actualizados', [
                'count' => count($canonicalTokens)
            ]);
            
        } catch (Exception $e) {
            Log::error('FCM: Error actualizando tokens canónicos', [
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Validar estructura de notificación
     */
    public function validateNotification(array $notification): bool
    {
        return isset($notification['title']) || isset($notification['body']);
    }
    
    /**
     * Crear payload de notificación estándar
     */
    public function createNotificationPayload(
        string $title, 
        string $body, 
        array $data = [], 
        ?string $icon = null
    ): array {
        $notification = [
            'title' => $title,
            'body' => $body
        ];
        
        if ($icon) {
            $notification['icon'] = $icon;
        }
        
        return [
            'notification' => $notification,
            'data' => $data
        ];
    }
}
