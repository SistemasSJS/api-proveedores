<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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

        if ($providedKey !== $validKey) {
            return response()->json([
                'error' => 'API Key inválida o faltante',
                'message' => 'Se requiere un X-API-KEY válido en los headers',
            ], 401);
        }

        return $next($request);
    }
}
