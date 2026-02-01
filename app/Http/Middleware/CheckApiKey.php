<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class CheckApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $validKey = config('api-access.registration_key');
        $providedKey = $request->header('X-API-KEY');

        // 🔍 Registrar las claves para depuración
        // Log::info('🔑 Verificación de API Key', [
        //     'provided_key' => $providedKey ?? 'N/A',
        //     'valid_key' => $validKey ?? 'N/A',
        //     'ip' => $request->ip(),
        //     'route' => $request->path(),
        // ]);

        if ($providedKey !== $validKey) {
            // Log::warning('❌ API Key inválida o faltante', [
            //     'provided_key' => $providedKey,
            //     'ip' => $request->ip(),
            // ]);

            return response()->json([
                'error' => 'API Key inválida o faltante',
                'message' => 'Se requiere un X-API-KEY válido en los headers',
            ], 401);
        }

        // Log::info('✅ API Key válida', [
        //     'ip' => $request->ip(),
        //     'route' => $request->path(),
        // ]);

        return $next($request);
    }
}
