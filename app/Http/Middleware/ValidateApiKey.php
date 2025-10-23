<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ValidateApiKey
{
  /**
   * Validar ApiKey en el header de la petición
   */
  public function handle(Request $request, Closure $next, $apiName)
  {
    $expectedApiKey = config("services.{$apiName}.apikey");
    $providedApiKey = $request->header('X-API-KEY');
    if (!$providedApiKey || $providedApiKey !== $expectedApiKey) {
      Log::warning('Intento de acceso no autorizado - ApiKey inválida', [
        'ip' => $request->ip(),
        'user_agent' => $request->userAgent(),
        'apikey_recibida' => $providedApiKey ? '***' . substr($providedApiKey, -4) : null,
        'api_esperada' => $apiName
      ]);

      return response()->json([
        'error' => 'Unauthorized',
        'message' => 'Invalid or missing ApiKey'
      ], 401);
    }
    return $next($request);
  }
}
