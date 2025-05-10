<?php


namespace App\Http\Middleware;

use App\Exceptions\Api\Auth\UnauthorizedException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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
        $user = $request->user();

        if (!$user || !$user->isUserAdmin()) {
            throw new UnauthorizedException('El usuario no tiene el rol requerido.');
        }

        return $next($request);
    }
}
