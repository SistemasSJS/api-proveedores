<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuditService
{
    /**
     * Registrar acción de usuario
     */
    public static function logAction(string $action, string $model, int $modelId, ?array $data = null): void
    {
        Log::info('User Action', [
            'action' => $action,
            'model' => $model,
            'model_id' => $modelId,
            'user_id' => Auth::check() ? Auth::id() : null,
            'user_email' => Auth::check() ? Auth::user()->email : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Registrar error del sistema
     */
    public static function logError(string $error, string $context, ?array $data = null): void
    {
        Log::error('System Error', [
            'error' => $error,
            'context' => $context,
            'user_id' => Auth::check() ? Auth::id() : null,
            'ip_address' => request()->ip(),
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Registrar cambio de datos sensibles
     */
    public static function logSensitiveChange(string $model, int $modelId, array $changes): void
    {
        Log::warning('Sensitive Data Change', [
            'model' => $model,
            'model_id' => $modelId,
            'changes' => $changes,
            'user_id' => Auth::check() ? Auth::id() : null,
            'timestamp' => now()->toISOString(),
        ]);
    }
}
