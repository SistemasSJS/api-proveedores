<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProveedorCuentaBancariaAccess
{
    /**
     * Verifica que la cuenta bancaria pertenezca al proveedor de la ruta.
     *
     * @return Response
     */
    public function handle(Request $request, Closure $next)
    {
        $proveedor = $request->route('proveedor'); // Proveedor de la ruta
        $cuenta = $request->route('cuenta_bancaria'); // Cuenta de la ruta

        if ($cuenta && $cuenta->proveedor_id !== $proveedor->id) {
            return response()->json([
                'error' => 'La cuenta bancaria no pertenece a este proveedor',
            ], 403);
        }

        return $next($request);
    }
}
