<?php

namespace App\Http\Middleware;

use App\Exceptions\Api\Custom\NotFoundRelationException;
use App\Models\Producto;
use App\Models\Proveedor;
use Closure;
use Illuminate\Http\Request;

class EnsureProductoBelongsToProveedor
{
    public function handle(Request $request, Closure $next)
    {
        // Aquí asumo que tienes route model binding configurado para 'catalogo' y 'producto'
        $proveedor = $request->route('proveedor'); // modelo Catalogo o id
        $producto = $request->route('producto'); // modelo Producto o id

        // Si no tienes model binding, usa findOrFail para obtener instancias:
        if (is_numeric($proveedor)) {
            $proveedor = Proveedor::findOrFail($proveedor);
        }

        if (is_numeric($producto)) {
            $producto = Producto::findOrFail($producto);
        }

        // Verificamos que el producto pertenezca al catálogo
        if ($producto->proveedor_id !== $proveedor->id) {
            throw new NotFoundRelationException('El producto no pertenece al proveedor.');
        }

        // Opcional: pasar el producto al request para usarlo en el controlador sin buscarlo de nuevo
        $request->attributes->set('producto', $producto);

        return $next($request);
    }
}
