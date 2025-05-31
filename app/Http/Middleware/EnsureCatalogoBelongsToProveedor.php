<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Catalogo;

class EnsureCatalogoBelongsToProveedor
{
    public function handle(Request $request, Closure $next)
    {
        $proveedorIdFromRoute = $request->route('proveedor');
        $catalogoIdFromRoute = $request->route('catalogo');

        if ($catalogoIdFromRoute) {
            $catalogo = Catalogo::findOrFail($catalogoIdFromRoute);

            if ((int) $catalogo->proveedor_id !== (int) $proveedorIdFromRoute) {
                abort(403, 'Este catálogo no pertenece al proveedor especificado.');
            }

            // Lo pasamos al request por si lo querés usar en el controlador
            $request->attributes->set('catalogo', $catalogo);
        }

        return $next($request);
    }
}
