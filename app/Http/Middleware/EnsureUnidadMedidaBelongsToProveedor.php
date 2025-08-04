<?php

namespace App\Http\Middleware;

use App\Exceptions\Api\Custom\NotFoundRelationException;
use Closure;
use Illuminate\Http\Request;
use App\Models\Proveedor;
use App\Models\UnidadMedida;

class EnsureUnidadMedidaBelongsToProveedor
{
    public function handle(Request $request, Closure $next)
    {

        $proveedor = $request->route('proveedor');
        $unidadMedida = $request->route('unidad');


        if (is_numeric($proveedor)) {
        }
        $proveedor = Proveedor::findOrFail($proveedor);

        if (is_numeric($unidadMedida)) {
            $unidadMedida = UnidadMedida::findOrFail($unidadMedida);
        }


        if ($unidadMedida->proveedor_id !== $proveedor->id) {
            throw new NotFoundRelationException('El categoria no pertenece al proveedor.');
        }


        $request->attributes->set('unidades', $proveedor->unidades);

        return $next($request);
    }
}
