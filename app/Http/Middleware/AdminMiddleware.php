<?php


namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Manejar la solicitud entrante.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verifica si el usuario autenticado es administrador
        if (auth()->user()->rol !== 'admin') {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        return $next($request);
    }
}
