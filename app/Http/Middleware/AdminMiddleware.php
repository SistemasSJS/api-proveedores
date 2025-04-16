<?php


namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

/**
 * @decrepted
 *      RoleMiddleware validate all roles user permisions
 * 
 *  NOTE: 
 *      Not delete file, referes to middleware.
 */
class AdminMiddleware
{
    /**
     * Manejar la solicitud entrante.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verifica si el usuario autenticado es administrador
        if (Auth::user()->role !== 'admin') {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        return $next($request);
    }
}
