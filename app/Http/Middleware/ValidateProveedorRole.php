<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ValidateProveedorRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        // Administradores siempre pasan
        if ($user->role?->name === 'ADMINISTRADOR') {
            return $next($request);
        }

        // Verificar si el usuario tiene uno de los roles requeridos
        if (! in_array($user->role?->name, $roles)) {
            return response()->json(['error' => 'No tienes permisos suficientes'], 403);
        }

        return $next($request);
    }
}
