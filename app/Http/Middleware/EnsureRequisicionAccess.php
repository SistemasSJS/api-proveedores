<?php

namespace App\Http\Middleware;

use App\Models\Requisicion;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRequisicionAccess
{
    public function handle(Request $request, Closure $next)
    {
        $requisicion = $request->route('requisicion');

        if (!$requisicion instanceof Requisicion) {
            return response()->json(['error' => 'Requisición no encontrada'], 404);
        }

        $user = $request->user();

        // Verificar si el usuario puede acceder a la requisición
        if ($user->id === $requisicion->usuario_id) {
            return $next($request);
        }

        // Si es usuario de proveedor, verificar que la requisición sea para su proveedor
        if ($user->proveedores()->where('proveedor_id', $requisicion->proveedor_id)->exists()) {
            return $next($request);
        }

        // Administradores tienen acceso total
        if ($user->role?->name === 'ADMINISTRADOR') {
            return $next($request);
        }

        return response()->json(['error' => 'No tienes acceso a esta requisición'], 403);
    }
}
