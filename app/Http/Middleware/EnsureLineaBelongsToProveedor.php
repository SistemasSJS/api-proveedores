<?php

namespace App\Http\Middleware;

use App\Exceptions\Api\Custom\NotFoundRelationException;
use App\Models\linea;
use Closure;
use Illuminate\Http\Request;
use App\Models\Proveedor;

class EnsureLineaBelongsToProveedor
{
    public function handle(Request $request, Closure $next)
    {

        $proveedor = $request->route('proveedor');
        $linea = $request->route('linea');


        if (is_numeric($proveedor)) {
            $proveedor = Proveedor::findOrFail($proveedor);
        }

        if (is_numeric($linea)) {
            $linea = Linea::findOrFail($linea);
        }


        if ($linea->proveedor_id !== $proveedor->id) {
            throw new NotFoundRelationException('La linea no pertenece al proveedor.');
        }


        $request->attributes->set('linea', $linea);

        return $next($request);
    }
}
