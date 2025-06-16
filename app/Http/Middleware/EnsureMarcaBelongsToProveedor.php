<?php

namespace App\Http\Middleware;

use App\Exceptions\Api\Custom\NotFoundRelationException;
use App\Models\Marca;
use Closure;
use Illuminate\Http\Request;
use App\Models\Proveedor;

class EnsureMarcaBelongsToProveedor
{
    public function handle(Request $request, Closure $next)
    {

        $proveedor = $request->route('proveedor');
        $marca = $request->route('marca');


        if (is_numeric($proveedor)) {
            $proveedor = Proveedor::findOrFail($proveedor);
        }

        if (is_numeric($marca)) {
            $marca = Marca::findOrFail($marca);
        }


        if ($marca->proveedor_id !== $proveedor->id) {
            throw new NotFoundRelationException('La categoria no pertenece al proveedor.');
        }


        $request->attributes->set('marca', $proveedor->marcas);

        return $next($request);
    }
}
