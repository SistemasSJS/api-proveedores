<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Proveedor;
use App\Exceptions\Api\Custom\NotFoundRelationException;

class EnsureUserBelongsToProveedor
{
    public function handle(Request $request, Closure $next)
    {
        $proveedor = $request->route('proveedor');
        $userParam = $request->route('user');

        // Validar que $proveedor sea un modelo válido
        if (!$proveedor || !method_exists($proveedor, 'users')) {
            throw new NotFoundRelationException('Proveedor no válido.');
        }

        if ($userParam) {
            // Si es string o numérico, hacemos find
            if (is_numeric($userParam)) {
                $user = User::findOrFail($userParam);
            } else {
                // Ya es un modelo User (binding implícito)
                $user = $userParam;
            }

            // Validamos la relación
            if (!$proveedor->users()->where('users.id', $user->id)->exists()) {
                throw new NotFoundRelationException('El user no pertenece al proveedor');
            }

            // Opcional: forzar que el parámetro 'user' sea el modelo ya cargado
            $request->attributes->set('user', $user);
        }

        return $next($request);
    }
}
