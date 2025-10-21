<?php

namespace App\Http\Middleware;

use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class LogApiActions
{
    /**
     * Rutas que NO deben ser loggeadas (por performance)
     */
    protected $excludedPaths = [
        'api/health',
        'api/ping',
        'sanctum/csrf-cookie',
    ];

    /**
     * Métodos HTTP que NO deben ser loggeados
     */
    protected $excludedMethods = [
        'OPTIONS',
    ];

    /**
     * Campos sensibles que deben ser ocultados en logs
     */
    protected $sensitiveFields = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'token',
        'api_key',
        'secret',
        'credit_card',
        'cvv',
    ];

    public function handle(Request $request, Closure $next)
    {
        // Verificar si debe loggear esta request
        if ($this->shouldSkipLogging($request)) {
            return $next($request);
        }

        $startTime = microtime(true);
        $requestId = $this->generateRequestId();

        // Log de entrada
        $this->logRequestStart($request, $requestId);

        // Ejecutar request
        $response = $next($request);

        $executionTime = round((microtime(true) - $startTime) * 1000, 2);

        // Log de salida
        $this->logRequestEnd($request, $response, $executionTime, $requestId);

        // Log adicional para errores
        if ($response->getStatusCode() >= 400) {
            $this->logError($request, $response, $requestId);
        }

        // Log para acciones críticas
        if ($this->isCriticalAction($request)) {
            $this->logCriticalAction($request, $response, $requestId);
        }

        // Detectar intentos de acceso sospechoso
        $this->detectSuspiciousActivity($request, $response);

        return $response;
    }

    /**
     * Verificar si debe saltar el logging
     */
    protected function shouldSkipLogging(Request $request): bool
    {
        // Saltar si es una ruta excluida
        foreach ($this->excludedPaths as $path) {
            if ($request->is($path)) {
                return true;
            }
        }

        // Saltar si es un método excluido
        if (in_array($request->method(), $this->excludedMethods)) {
            return true;
        }

        // Saltar si es ambiente de testing con flag específico
        if (app()->environment('testing') && $request->header('X-Skip-Logging')) {
            return true;
        }

        return false;
    }

    /**
     * Generar ID único para la request
     */
    protected function generateRequestId(): string
    {
        return uniqid('req_', true);
    }

    /**
     * Log al inicio de la request
     */
    protected function logRequestStart(Request $request, string $requestId): void
    {
        $data = [
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => $request->path(),
            'full_url' => $request->fullUrl(),
            'ip_address' => $this->getRealIpAddress($request),
            'user_agent' => $request->userAgent(),
            'referer' => $request->header('referer'),
            'user_id' => Auth::id(),
            'user_email' => Auth::check() ? Auth::user()->email : null,
            'headers' => $this->sanitizeHeaders($request->headers->all()),
            'query_params' => $request->query(),
            'request_data' => $this->sanitizeRequestData($request->all()),
            'request_size' => strlen($request->getContent()),
            'timestamp' => now()->toISOString(),
        ];

        AuditService::logAction(
            'request_start',
            'API_Request',
            0,
            $data
        );
    }

    /**
     * Log al final de la request
     */
    protected function logRequestEnd(Request $request, $response, float $executionTime, string $requestId): void
    {
        $data = [
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => $request->path(),
            'status_code' => $response->getStatusCode(),
            'execution_time_ms' => $executionTime,
            'response_size' => strlen($response->getContent()),
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'user_id' => Auth::id(),
            'performance_category' => $this->categorizePerformance($executionTime),
        ];

        // Agregar headers de respuesta importantes
        $responseHeaders = [];
        if ($response->headers->has('X-RateLimit-Remaining')) {
            $responseHeaders['rate_limit_remaining'] = $response->headers->get('X-RateLimit-Remaining');
        }
        if ($response->headers->has('Content-Type')) {
            $responseHeaders['content_type'] = $response->headers->get('Content-Type');
        }

        $data['response_headers'] = $responseHeaders;

        AuditService::logAction(
            'request_end',
            'API_Request',
            0,
            $data
        );
    }

    /**
     * Log específico para errores
     */
    protected function logError(Request $request, $response, string $requestId): void
    {
        $responseContent = $response->getContent();
        $errorData = null;

        // Intentar decodificar el JSON de error
        if ($this->isJsonResponse($response)) {
            $decoded = json_decode($responseContent, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $errorData = $decoded;
            }
        }

        $data = [
            'request_id' => $requestId,
            'error_type' => $this->getErrorType($response->getStatusCode()),
            'status_code' => $response->getStatusCode(),
            'method' => $request->method(),
            'path' => $request->path(),
            'error_data' => $errorData,
            'user_id' => Auth::id(),
            'ip_address' => $this->getRealIpAddress($request),
            'user_agent' => $request->userAgent(),
            'request_data' => $this->sanitizeRequestData($request->all()),
        ];

        AuditService::logError(
            "HTTP_{$response->getStatusCode()}",
            'API_Error',
            $data
        );
    }

    /**
     * Log para acciones críticas
     */
    protected function logCriticalAction(Request $request, $response, string $requestId): void
    {
        $data = [
            'request_id' => $requestId,
            'action_type' => $this->getCriticalActionType($request),
            'method' => $request->method(),
            'path' => $request->path(),
            'status_code' => $response->getStatusCode(),
            'user_id' => Auth::id(),
            'user_email' => Auth::check() ? Auth::user()->email : null,
            'ip_address' => $this->getRealIpAddress($request),
            'request_data' => $this->sanitizeRequestData($request->all()),
            'success' => $response->getStatusCode() < 400,
        ];

        AuditService::logSensitiveChange(
            'Critical_API_Action',
            Auth::id() ?? 0,
            $data
        );
    }

    /**
     * Detectar actividad sospechosa
     */
    protected function detectSuspiciousActivity(Request $request, $response): void
    {
        $ip = $this->getRealIpAddress($request);
        $userId = Auth::id();
        $cacheKey = "suspicious_activity_{$ip}_{$userId}";

        // Contador de requests por IP/usuario
        $requestCount = Cache::get($cacheKey, 0) + 1;
        Cache::put($cacheKey, $requestCount, now()->addMinutes(10));

        // Detectar diferentes tipos de actividad sospechosa
        $suspiciousIndicators = [];

        // 1. Demasiadas requests
        if ($requestCount > 100) {
            $suspiciousIndicators[] = 'high_request_volume';
        }

        // 2. Muchos errores 401/403
        if (in_array($response->getStatusCode(), [401, 403])) {
            $errorKey = "auth_errors_{$ip}";
            $errorCount = Cache::get($errorKey, 0) + 1;
            Cache::put($errorKey, $errorCount, now()->addMinutes(5));

            if ($errorCount > 10) {
                $suspiciousIndicators[] = 'auth_brute_force';
            }
        }

        // 3. User-Agent sospechoso
        $userAgent = $request->userAgent();
        if ($this->isSuspiciousUserAgent($userAgent)) {
            $suspiciousIndicators[] = 'suspicious_user_agent';
        }

        // 4. Intentos de inyección
        if ($this->hasInjectionAttempts($request)) {
            $suspiciousIndicators[] = 'injection_attempt';
        }

        // Log si hay actividad sospechosa
        if (! empty($suspiciousIndicators)) {
            AuditService::logError(
                'Suspicious Activity Detected',
                'Security',
                [
                    'indicators' => $suspiciousIndicators,
                    'ip_address' => $ip,
                    'user_id' => $userId,
                    'user_agent' => $userAgent,
                    'path' => $request->path(),
                    'request_count' => $requestCount,
                ]
            );
        }
    }

    /**
     * Obtener la IP real del usuario
     */
    protected function getRealIpAddress(Request $request): string
    {
        // Verificar headers de proxy/load balancer
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Proxy estándar
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            if (! empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);

                if (filter_var(
                    $ip,
                    FILTER_VALIDATE_IP,
                    FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
                )) {
                    return $ip;
                }
            }
        }

        return $request->ip();
    }

    /**
     * Sanitizar headers sensibles
     */
    protected function sanitizeHeaders(array $headers): array
    {
        $sanitized = [];

        foreach ($headers as $key => $value) {
            if (
                str_contains(strtolower($key), 'authorization') ||
                str_contains(strtolower($key), 'token') ||
                str_contains(strtolower($key), 'cookie')
            ) {
                $sanitized[$key] = '[HIDDEN]';
            } else {
                $sanitized[$key] = is_array($value) ? $value : [$value];
            }
        }

        return $sanitized;
    }

    /**
     * Sanitizar datos sensibles de la request
     */
    protected function sanitizeRequestData(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $this->sensitiveFields)) {
                $sanitized[$key] = '[HIDDEN]';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeRequestData($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Verificar si es una acción crítica
     */
    protected function isCriticalAction(Request $request): bool
    {
        $criticalPaths = [
            'api/auth/login',
            'api/auth/logout',
            'api/auth/register',
            'api/users',
            'api/proveedores',
            'api/requisiciones/*/estatus',
        ];

        $criticalMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

        foreach ($criticalPaths as $path) {
            if ($request->is($path) && in_array($request->method(), $criticalMethods)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtener tipo de acción crítica
     */
    protected function getCriticalActionType(Request $request): string
    {
        if ($request->is('api/auth/*')) {
            return 'authentication';
        }

        if ($request->is('api/users*')) {
            return 'user_management';
        }

        if ($request->is('api/proveedores*')) {
            return 'provider_management';
        }

        if ($request->is('api/requisiciones*')) {
            return 'requisition_management';
        }

        return 'unknown_critical';
    }

    /**
     * Categorizar performance
     */
    protected function categorizePerformance(float $executionTime): string
    {
        if ($executionTime < 200) {
            return 'fast';
        } elseif ($executionTime < 1000) {
            return 'normal';
        } elseif ($executionTime < 3000) {
            return 'slow';
        } else {
            return 'very_slow';
        }
    }

    /**
     * Obtener tipo de error
     */
    protected function getErrorType(int $statusCode): string
    {
        return match (true) {
            $statusCode >= 500 => 'server_error',
            $statusCode >= 400 && $statusCode < 500 => 'client_error',
            $statusCode >= 300 && $statusCode < 400 => 'redirect',
            default => 'unknown'
        };
    }

    /**
     * Verificar si la respuesta es JSON
     */
    protected function isJsonResponse($response): bool
    {
        return str_contains($response->headers->get('Content-Type', ''), 'application/json');
    }

    /**
     * Verificar User-Agent sospechoso
     */
    protected function isSuspiciousUserAgent(?string $userAgent): bool
    {
        if (empty($userAgent)) {
            return true;
        }

        $suspiciousPatterns = [
            '/bot/i',
            '/crawler/i',
            '/spider/i',
            '/scraper/i',
            '/hack/i',
            '/sql/i',
            '/injection/i',
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detectar intentos de inyección
     */
    protected function hasInjectionAttempts(Request $request): bool
    {
        $allInput = json_encode($request->all());

        $injectionPatterns = [
            '/union\s+select/i',
            '/drop\s+table/i',
            '/<script/i',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/\.\.\//i',
            '/etc\/passwd/i',
        ];

        foreach ($injectionPatterns as $pattern) {
            if (preg_match($pattern, $allInput)) {
                return true;
            }
        }

        return false;
    }
}
