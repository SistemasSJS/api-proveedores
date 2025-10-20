<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SseController extends Controller
{
    /**
     * Stream de notificaciones en tiempo real
     */
    public function notifications(Request $request): StreamedResponse
    {
        return new StreamedResponse(function () use ($request) {
            // Enviar evento inicial simple
            echo "event: connected\n";
            echo "data: " . json_encode(['message' => 'SSE Connected', 'timestamp' => now()->toIsoString()]) . "\n\n";
            flush();
            
            // Solo enviar heartbeat cada 10 segundos para probar
            for ($i = 0; $i < 6; $i++) {
                if (connection_aborted()) {
                    break;
                }
                
                sleep(10);
                
                echo "event: heartbeat\n";
                echo "data: " . json_encode(['timestamp' => now()->toIsoString(), 'count' => $i]) . "\n\n";
                flush();
            }
            
            echo "event: close\n";
            echo "data: " . json_encode(['message' => 'Connection closed']) . "\n\n";
            flush();
            
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Headers' => 'Cache-Control'
        ]);
    }
    
    /**
     * Autenticar usuario desde token en query parameter
     */
    private function authenticateFromToken(Request $request): ?User
    {
        $token = $request->query('token');
        
        if (!$token) {
            return null;
        }
        
        try {
            // Buscar el token en la base de datos
            $accessToken = PersonalAccessToken::findToken($token);
            
            if (!$accessToken) {
                return null;
            }
            
            // Verificar que el token no haya expirado
            if ($accessToken->expires_at && $accessToken->expires_at->isPast()) {
                return null;
            }
            
            return $accessToken->tokenable;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Enviar evento SSE
     */
    private function sendEvent(string $event, array $data): void
    {
        echo "event: {$event}\n";
        echo "data: " . json_encode($data) . "\n\n";
        flush();
        
        // Asegurar que se envíe inmediatamente
        if (ob_get_level()) {
            ob_flush();
        }
    }
}