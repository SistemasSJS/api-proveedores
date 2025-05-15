<?php

namespace App\Http\Middleware;

use App\Exceptions\Api\Auth\UnauthorizedException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Maneja el control de acceso basado en roles dinámicos desde la ruta.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();
        
        if (!$user || !$user->hasRole($roles)) {
            throw new UnauthorizedException('El usuario no tiene el rol requerido.' + $user->role);
        }

        return $next($request);
    }
}
