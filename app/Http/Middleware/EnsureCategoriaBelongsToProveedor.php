<?php

namespace App\Http\Middleware;

use App\Exceptions\Api\Custom\NotFoundRelationException;
use App\Models\Categoria;
use Closure;
use Illuminate\Http\Request;
use App\Models\Proveedor;

class EnsureCategoriaBelongsToProveedor
{
    public function handle(Request $request, Closure $next)
    {

        $proveedor = $request->route('proveedor');
        $categoria = $request->route('categoria');


        if (is_numeric($proveedor)) {
            $proveedor = Proveedor::findOrFail($proveedor);
        }

        if (is_numeric($categoria)) {
            $categoria = Categoria::findOrFail($categoria);
        }


        if ($categoria->proveedor_id !== $proveedor->id) {
            throw new NotFoundRelationException('El categoria no pertenece al proveedor.');
        }


        $request->attributes->set('categorias', $proveedor->categorias);

        return $next($request);
    }
}
