<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Middleware para registrar acciones críticas de la API para auditoría.
 *
 * Registra operaciones CRUD importantes, cambios en usuarios de proveedores,
 * importaciones, y otras acciones sensibles del sistema.
 */
class LogApiActions
{
    /**
     * Acciones que siempre deben ser auditadas
     */
    private const CRITICAL_ACTIONS = [
        'POST' => [
            'api/proveedores',
            'api/proveedores/*/users',
            'api/proveedores/*/productos',
            'api/proveedores/*/productos/import',
            'api/auth/login',
            'api/auth/register'
        ],
        'PUT' => [
            'api/proveedores/*',
            'api/proveedores/*/users/*',
            'api/usuarios/*'
        ],
        'PATCH' => [
            'api/proveedores/*',
            'api/proveedores/*/users/*',
            'api/usuarios/*'
        ],
        'DELETE' => [
            'api/proveedores/*',
            'api/proveedores/*/users/*',
            'api/proveedores/*/productos/*',
            'api/usuarios/*'
        ]
    ];

    /**
     * Campos sensibles que no deben ser registrados completos
     */
    private const SENSITIVE_FIELDS = [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        'api_token',
        'access_token',
        'refresh_token'
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);

        // Capturar datos del request antes de procesarlo
        $requestData = $this->captureRequestData($request);

        // Ejecutar la solicitud
        $response = $next($request);

        $endTime = microtime(true);
        $processingTime = round(($endTime - $startTime) * 1000, 2); // en ms

        // Registrar la acción si es necesario
        if ($this->shouldLogAction($request, $response)) {
            $this->logAction($request, $response, $requestData, $processingTime);
        }

        return $response;
    }

    /**
     * Captura los datos relevantes del request.
     *
     * @param Request $request
     * @return array
     */
    private function captureRequestData(Request $request): array
    {
        $data = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'headers' => $this->sanitizeHeaders($request->headers->all()),
            'query_params' => $request->query(),
            'body' => $this->sanitizeRequestBody($request->all()),
            'timestamp' => now(),
            'request_id' => Str::uuid()
        ];

        // Agregar información del usuario si está autenticado
        if (Auth::check()) {
            $user = Auth::user();
            $data['user'] = [
                'id' => $user->id,
                'email' => $user->email,
                'role' => $user->role ? $user->role->name : null
            ];
        }

        return $data;
    }

    /**
     * Sanitiza las cabeceras removiendo información sensible.
     *
     * @param array $headers
     * @return array
     */
    private function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = ['authorization', 'cookie', 'x-api-key'];

        foreach ($sensitiveHeaders as $header) {
            if (isset($headers[$header])) {
                $headers[$header] = ['***SANITIZED***'];
            }
        }

        return $headers;
    }

    /**
     * Sanitiza el body del request removiendo campos sensibles.
     *
     * @param array $body
     * @return array
     */
    private function sanitizeRequestBody(array $body): array
    {
        foreach (static::SENSITIVE_FIELDS as $field) {
            if (isset($body[$field])) {
                $body[$field] = '***SANITIZED***';
            }
        }

        return $body;
    }

    /**
     * Determina si la acción debe ser registrada.
     *
     * @param Request $request
     * @param $response
     * @return bool
     */
    private function shouldLogAction(Request $request, $response): bool
    {
        // Siempre registrar errores 4xx y 5xx
        if ($response->getStatusCode() >= 400) {
            return true;
        }

        // Verificar si es una acción crítica
        $method = $request->method();
        $path = $request->path();

        if (!isset(static::CRITICAL_ACTIONS[$method])) {
            return false;
        }

        foreach (static::CRITICAL_ACTIONS[$method] as $pattern) {
            if ($this->matchesPattern($path, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica si una ruta coincide con un patrón.
     *
     * @param string $path
     * @param string $pattern
     * @return bool
     */
    private function matchesPattern(string $path, string $pattern): bool
    {
        // Convertir el patrón a regex
        $regex = str_replace(['*', '/'], ['[^/]+', '\/'], $pattern);

        return (bool) preg_match("/^{$regex}$/", $path);
    }

    /**
     * Registra la acción en el sistema de auditoría.
     *
     * @param Request $request
     * @param $response
     * @param array $requestData
     * @param float $processingTime
     * @return void
     */
    private function logAction(Request $request, $response, array $requestData, float $processingTime): void
    {
        $logEntry = [
            'request_id' => $requestData['request_id'],
            'user_id' => $requestData['user']['id'] ?? null,
            'user_email' => $requestData['user']['email'] ?? null,
            'user_role' => $requestData['user']['role'] ?? null,
            'action' => $this->getActionName($request),
            'resource' => $this->getResourceName($request),
            'resource_id' => $this->getResourceId($request),
            'proveedor_context' => $this->getProveedorContext($request),
            'method' => $requestData['method'],
            'path' => $requestData['path'],
            'url' => $requestData['url'],
            'ip_address' => $requestData['ip'],
            'user_agent' => $requestData['user_agent'],
            'request_headers' => json_encode($requestData['headers']),
            'request_body' => json_encode($requestData['body']),
            'response_status' => $response->getStatusCode(),
            'response_headers' => json_encode($response->headers->all()),
            'processing_time_ms' => $processingTime,
            'timestamp' => $requestData['timestamp'],
            'created_at' => now(),
            'updated_at' => now()
        ];

        // Capturar respuesta para errores
        if ($response->getStatusCode() >= 400) {
            $content = $response->getContent();
            $logEntry['response_body'] = $content;
            $logEntry['error_details'] = $this->extractErrorDetails($content);
        }

        // Registrar en base de datos
        try {
            DB::table('api_audit_logs')->insert($logEntry);
        } catch (\Exception $e) {
            // Fallback a archivo de log si falla la BD
            Log::channel('audit')->error('Failed to log to database', [
                'error' => $e->getMessage(),
                'log_entry' => $logEntry
            ]);
        }

        // También registrar en archivo para análisis
        Log::channel('audit')->info('API Action', $logEntry);
    }

    /**
     * Obtiene un nombre descriptivo para la acción.
     *
     * @param Request $request
     * @return string
     */
    private function getActionName(Request $request): string
    {
        $method = $request->method();
        $path = $request->path();

        // Mapear a nombres más descriptivos
        $actionMap = [
            'POST' => 'CREATE',
            'PUT' => 'UPDATE',
            'PATCH' => 'UPDATE',
            'DELETE' => 'DELETE',
            'GET' => 'READ'
        ];

        $baseAction = $actionMap[$method] ?? $method;

        // Agregar contexto específico
        if (str_contains($path, 'import')) {
            return 'IMPORT';
        }

        if (str_contains($path, 'login')) {
            return 'LOGIN';
        }

        if (str_contains($path, 'register')) {
            return 'REGISTER';
        }

        return $baseAction;
    }

    /**
     * Obtiene el nombre del recurso principal.
     *
     * @param Request $request
     * @return string|null
     */
    private function getResourceName(Request $request): ?string
    {
        $path = $request->path();
        $segments = explode('/', $path);

        // Buscar el recurso principal en los segmentos
        $resources = ['proveedores', 'productos', 'users', 'usuarios'];

        foreach ($segments as $segment) {
            if (in_array($segment, $resources)) {
                return $segment;
            }
        }

        return $segments[0] ?? null;
    }

    /**
     * Obtiene el ID del recurso si está presente en la ruta.
     *
     * @param Request $request
     * @return int|null
     */
    private function getResourceId(Request $request): ?int
    {
        $path = $request->path();

        // Buscar números en la ruta que representen IDs
        if (preg_match('/\/(\d+)/', $path, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * Obtiene el contexto del proveedor si está presente.
     *
     * @param Request $request
     * @return int|null
     */
    private function getProveedorContext(Request $request): ?int
    {
        $path = $request->path();

        // Buscar patrón proveedores/{id}
        if (preg_match('/proveedores\/(\d+)/', $path, $matches)) {
            return (int) $matches[1];
        }

        // También revisar en el request body o query params
        return $request->get('proveedor_id') ?? $request->route('proveedor');
    }

    /**
     * Extrae detalles del error de la respuesta.
     *
     * @param string $responseContent
     * @return array|null
     */
    private function extractErrorDetails(string $responseContent): ?array
    {
        $content = json_decode($responseContent, true);

        if (json_last_error() === JSON_ERROR_NONE && isset($content['message'])) {
            return [
                'message' => $content['message'],
                'error_code' => $content['error_code'] ?? null,
                'errors' => $content['errors'] ?? null
            ];
        }

        return null;
    }
}
