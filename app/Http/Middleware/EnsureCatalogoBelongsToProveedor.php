<?php

namespace App\Http\Middleware;

use App\Exceptions\Api\Auth\UnauthorizedException;
use App\Exceptions\Api\Custom\NotFoundRelationException;
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

            if ($catalogo->proveedor_id !==  $proveedorIdFromRoute->id) {
                throw new NotFoundRelationException('El catalogo no pertenece al proveedor');
            }

            // Lo pasamos al request por si lo querés usar en el controlador
            $request->attributes->set('catalogo', $catalogo);
        }

        return $next($request);
    }
}
