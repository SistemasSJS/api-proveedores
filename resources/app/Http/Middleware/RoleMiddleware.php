<?php

namespace App\Http\Middleware;

use App\Exceptions\Api\Auth\UnauthorizedException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Control de acceso basado en roles.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            throw new UnauthorizedException('No hay un usuario autenticado.');
        }

        if (!$user->hasRole($roles)) {
            throw new UnauthorizedException("El usuario no tiene un rol asignado.");
        }

        if (!$user->hasRole($roles)) {
            $userRole = $user->role->nombre ?? 'desconocido';
            throw new UnauthorizedException("El usuario no tiene el rol requerido. Rol actual: {$userRole}. Roles permitidos: " . implode(', ', $roles));
        }

        return $next($request);
    }
}
