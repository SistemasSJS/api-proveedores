<?php

namespace App\Http\Middleware;

use App\Exceptions\Api\Custom\NotFoundRelationException;
use App\Models\Catalogo;
use Closure;
use Illuminate\Http\Request;
use App\Models\Producto;

class EnsureProductoBelongsToCatalogo
{
    public function handle(Request $request, Closure $next)
    {
        // Aquí asumo que tienes route model binding configurado para 'catalogo' y 'producto'
        $catalogo = $request->route('catalogo'); // modelo Catalogo o id
        $producto = $request->route('producto'); // modelo Producto o id

        // Si no tienes model binding, usa findOrFail para obtener instancias:
        if (is_numeric($catalogo)) {
            $catalogo = Catalogo::findOrFail($catalogo);
        }

        if (is_numeric($producto)) {
            $producto = Producto::findOrFail($producto);
        }

        // Verificamos que el producto pertenezca al catálogo
        if ($producto->catalogo_id !== $catalogo->id) {
            throw new NotFoundRelationException('El producto no pertenece al catálogo indicado.');
        }

        // Opcional: pasar el producto al request para usarlo en el controlador sin buscarlo de nuevo
        $request->attributes->set('producto', $producto);

        return $next($request);
    }
}
