<?php

namespace App\Http\Controllers;

use App\Services\ConstruccionesApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProveedorOrdenCompraController extends Controller
{
    protected $construccionesApi;

    public function __construct(ConstruccionesApiService $construccionesApi)
    {
        $this->construccionesApi = $construccionesApi;
    }

    /**
     * Obtener las órdenes de compra del proveedor autenticado
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Verificar que el usuario tenga un proveedor asociado
        if (!$user->proveedor_id) {
            return $this->error('Usuario no tiene un proveedor asociado', 403);
        }

        // Parámetros de filtrado y paginación
        $params = $request->only(['page', 'per_page', 'estatus', 'fecha_desde', 'fecha_hasta', 'sort_by', 'order']);

        // Obtener órdenes de compra desde la API de Construcciones
        $result = $this->construccionesApi->getOrdenesCompraByProveedor($user->proveedor_id, $params);

        if (!$result['success']) {
            return $this->error(
                'Error al obtener órdenes de compra: ' . ($result['error'] ?? 'Error desconocido'),
                $result['status'] ?? 500
            );
        }

        return response()->json($result['data']);
    }

    /**
     * Obtener el detalle de una orden de compra específica
     */
    public function show(Request $request, string $ordenCompraId): JsonResponse
    {
        $user = $request->user();

        // Verificar que el usuario tenga un proveedor asociado
        if (!$user->proveedor_id) {
            return $this->error('Usuario no tiene un proveedor asociado', 403);
        }

        // Obtener detalle de la orden de compra desde la API de Construcciones
        $result = $this->construccionesApi->getOrdenCompraById($ordenCompraId);

        if (!$result['success']) {
            return $this->error(
                'Error al obtener orden de compra: ' . ($result['error'] ?? 'Error desconocido'),
                $result['status'] ?? 500
            );
        }

        $ordenCompra = $result['data']['data'] ?? $result['data'];

        // Verificar que la orden pertenezca al proveedor
        if (isset($ordenCompra['proveedor_id']) && $ordenCompra['proveedor_id'] != $user->proveedor_id) {
            return $this->error('No tiene permisos para ver esta orden de compra', 403);
        }

        return response()->json($result['data']);
    }
}
