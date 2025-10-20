<?php

namespace App\Http\Middleware;

use App\Exceptions\Api\Auth\UnauthorizedException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Control de acceso basado en roles.
     *
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            throw new UnauthorizedException('No hay un usuario autenticado.');
        }

        if (! $user->hasRole($roles)) {
            throw new UnauthorizedException('El usuario no tiene un rol asignado. Roles permitidos: '.implode(', ', $roles).'.'.' Rol actual: '.($user->role->nombre ?? 'desconocido'));
        }

        if (! $user->hasRole($roles)) {
            $userRole = $user->role->nombre ?? 'desconocido';
            throw new UnauthorizedException("El usuario no tiene el rol requerido. Rol actual: {$userRole}. Roles permitidos: ".implode(', ', $roles));
        }

        return $next($request);
    }
}
