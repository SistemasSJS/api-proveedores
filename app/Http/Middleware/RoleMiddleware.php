<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\UnauthorizedRoleException;
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
        $user = Auth::user();
        if (!$user) {
            throw new UnauthorizedRoleException('El usuario no está autenticado.L:::' . join(" ", $roles));
        }

        // Asegura que el rol del usuario está en la lista que viene desde la ruta
        if (!in_array($user->role, $roles)) {
            throw new UnauthorizedRoleException('El usuario no tiene el rol requerido.');
        }

        return $next($request);
    }
}
