<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use App\Exceptions\Api\Custom\NotFoundRelationException;

class EnsureUserBelongsToProveedor
{
    public function handle(Request $request, Closure $next)
    {
        $proveedor = $request->route('proveedor');
        $userParam = $request->route('user');

        if ($userParam) {
            // Si es string o int, hacemos find
            if (is_string($userParam) || is_int($userParam)) {
                $user = User::findOrFail($userParam);
            } else {
                // Ya es un modelo User (binding implícito)
                $user = $userParam;
            }

            if (!$proveedor->users()->where('users.id', $user->id)->exists()) {
                throw new NotFoundRelationException('El user no pertenece al proveedor');
            }

            $request->attributes->set('user', $user);
        }


        return $next($request);
    }
}
