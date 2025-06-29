<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureSucursalBelongsToProveedor
{
    public function handle(Request $request, Closure $next)
    {
        $proveedor = $request->route('proveedor');
        $sucursal = $request->route('sucursal');

        if ($sucursal && $sucursal->proveedor_id !== $proveedor->id) {
            return response()->json(['error' => 'La sucursal no pertenece a este proveedor'], 403);
        }

        return $next($request);
    }
}
