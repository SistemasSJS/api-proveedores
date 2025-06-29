<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProveedorProductAccess
{
    public function handle(Request $request, Closure $next)
    {
        $proveedor = $request->route('proveedor');
        $producto = $request->route('producto');

        if ($producto && $producto->proveedor_id !== $proveedor->id) {
            return response()->json(['error' => 'El producto no pertenece a este proveedor'], 403);
        }

        return $next($request);
    }
}
