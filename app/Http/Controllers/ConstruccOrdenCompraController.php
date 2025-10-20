<?php

namespace App\Http\Controllers;

use App\Http\Requests\Construcc\StoreConstruccOrdenCompraRequest;
use App\Http\Requests\Construcc\UpdateConstruccOrdenCompraRequest;
use App\Http\Requests\Construcc\CambiarEstadoConstruccOrdenCompraRequest;
use App\Models\OrdenCompra;
use App\Models\OrdenCompraDetalle;
use App\Models\Proveedor;
use App\Models\EmpresaConstrucc;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConstruccOrdenCompraController extends Controller
{
    /**
     * Listar órdenes de compra desde el segmento construcción
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(OrdenCompra::getFilters());
        $sortBy = $request->input('sort_by', 'created_at');
        $order = $request->input('order', 'desc');
        $perPage = $request->input('per_page', 15);

        // Filtros específicos del segmento construcción
        $proveedorId = $request->input('proveedor_id');
        $empresaId = $request->input('empresa_construcc_id');

        $query = OrdenCompra::query()
            ->with(['proveedor', 'empresaConstrucc', 'detalles'])
            ->filter($filters)
            ->orderBy($sortBy, $order);

        // Aplicar filtros específicos si se proporcionan
        if ($proveedorId) {
            $query->where('proveedor_id', $proveedorId);
        }

        if ($empresaId) {
            $query->where('empresa_construcc_id', $empresaId);
        }

        // Agregar datos calculados
        $query->selectRaw('ordenes_compra.*, 
            CASE 
                WHEN sp_count > 0 THEN 0
                ELSE DATEDIFF(NOW(), COALESCE(fecha_aprobacion, created_at))
            END as dias_sin_sp');

        $originalPaginator = $query->paginate($perPage);

        // Enriquecer datos
        $data = $originalPaginator->getCollection()->map(function ($oc) {
            $oc->nivel_alerta = $oc->getNivelAlerta();
            $oc->monto_disponible = $oc->getMontoDisponible();
            $oc->puede_generar_sp = $oc->puedeGenerarSolicitudPago();
            return $oc;
        });

        return $this->paginated($originalPaginator->setCollection($data));
    }

    /**
     * Crear una nueva orden de compra desde construcción
     */
    public function store(StoreConstruccOrdenCompraRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // ✅ Validar y preparar los datos
            $data = $request->validated();

            // ✅ Verificar que la empresa constructora existe
            $empresa = EmpresaConstrucc::findOrFail($data['empresa_construcc_id']);

            // ✅ Crear la orden de compra
            $ordenCompra = OrdenCompra::create([
                'numero_orden' => $data['numero_orden'],
                'fecha_orden' => $data['fecha_orden'],
                'proveedor_id' => $data['proveedor_id'],
                'empresa_construcc_id' => $data['empresa_construcc_id'],
                'importe_total' => $data['importe_total'],
                'estado' => $data['estado'] ?? 'pendiente',
                'fecha_aprobacion' => $data['fecha_aprobacion'] ?? null,
                'observaciones' => $data['observaciones'] ?? null,
                'metadata_json' => $data['metadata_json'] ?? null,
                'monto_sp_asociado' => 0,
                'sp_count' => 0,
            ]);

            // ✅ Crear los detalles si existen
            if (isset($data['detalles']) && is_array($data['detalles'])) {
                foreach ($data['detalles'] as $detalleData) {
                    OrdenCompraDetalle::create([
                        'orden_compra_id' => $ordenCompra->id,
                        'producto' => $detalleData['producto'],
                        'descripcion' => $detalleData['descripcion'] ?? null,
                        'cantidad' => $detalleData['cantidad'],
                        'unidad_medida' => $detalleData['unidad_medida'] ?? null,
                        'precio_unitario' => $detalleData['precio_unitario'],
                    ]);
                }
            }

            DB::commit();

            return $this->success(
                $ordenCompra->load(['detalles', 'proveedor', 'empresaConstrucc']),
                'Orden de compra creada exitosamente.',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Error al crear la orden de compra: ' . $e->getMessage(), 500);
        }
    }


    /**
     * Mostrar detalle de una orden de compra
     */
    public function show(OrdenCompra $ordenCompra): JsonResponse
    {
        $ordenCompra->load([
            'detalles',
            'proveedor',
            'empresaConstrucc',
            'solicitudesPago' => function ($query) {
                $query->with(['proveedor', 'empresaConstrucc']);
            }
        ]);

        // Agregar datos calculados
        $ordenCompra->nivel_alerta = $ordenCompra->getNivelAlerta();
        $ordenCompra->dias_sin_sp = $ordenCompra->getDiasSinSolicitudPago();
        $ordenCompra->monto_disponible = $ordenCompra->getMontoDisponible();
        $ordenCompra->puede_generar_sp = $ordenCompra->puedeGenerarSolicitudPago();

        return $this->success($ordenCompra);
    }

    /**
     * Actualizar una orden de compra
     */
    public function update(UpdateConstruccOrdenCompraRequest $request, OrdenCompra $ordenCompra): JsonResponse
    {

        try {
            DB::beginTransaction();

            // Actualizar la orden
            $updateData = [];
            if (isset($request['fecha_orden'])) $updateData['fecha_orden'] = $request['fecha_orden'];
            if (isset($request['empresa_construcc_id'])) $updateData['empresa_construcc_id'] = $request['empresa_construcc_id'];
            if (isset($request['importe_total'])) $updateData['importe_total'] = $request['importe_total'];
            if (isset($request['estado'])) $updateData['estado'] = $request['estado'];
            if (isset($request['fecha_aprobacion'])) $updateData['fecha_aprobacion'] = $request['fecha_aprobacion'];
            if (isset($request['observaciones'])) $updateData['observaciones'] = $request['observaciones'];
            if (isset($request['metadata_json'])) $updateData['metadata_json'] = $request['metadata_json'];

            $ordenCompra->update($updateData);

            // Sincronizar detalles si se proporcionan
            if (isset($request['detalles']) && is_array($request['detalles'])) {
                $this->syncDetalles($ordenCompra, $request['detalles']);
            }

            DB::commit();

            return $this->success(
                $ordenCompra->load(['detalles', 'proveedor', 'empresaConstrucc']),
                'Orden de compra actualizada exitosamente.'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Error al actualizar la orden de compra: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Eliminar una orden de compra
     */
    public function destroy(OrdenCompra $ordenCompra): JsonResponse
    {
        try {
            // Verificar que no tenga SP asociadas
            if ($ordenCompra->sp_count > 0) {
                return $this->error('No se puede eliminar una orden de compra con solicitudes de pago asociadas', 422);
            }

            DB::beginTransaction();

            // Eliminar detalles
            $ordenCompra->detalles()->delete();

            // Eliminar orden
            $ordenCompra->delete();

            DB::commit();

            return $this->success(null, 'Orden de compra eliminada exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Error al eliminar la orden de compra: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Cambiar estado de una orden de compra
     */
    public function cambiarEstado(CambiarEstadoConstruccOrdenCompraRequest $request, OrdenCompra $ordenCompra): JsonResponse
    {

        $estadoAnterior = $ordenCompra->estado;

        $ordenCompra->update([
            'estado' => $request['estado'],
            'fecha_aprobacion' => $request['estado'] === 'aprobada' ? now() : $ordenCompra->fecha_aprobacion,
            'observaciones' => $request['observaciones'] ?? $ordenCompra->observaciones
        ]);

        return $this->success([
            'orden_compra' => $ordenCompra->fresh(),
            'estado_anterior' => $estadoAnterior,
            'estado_actual' => $request['estado']
        ], "Estado cambiado de '{$estadoAnterior}' a '{$request['estado']}' exitosamente.");
    }

    /**
     * Obtener órdenes de compra por proveedor
     */
    public function porProveedor(Request $request, Proveedor $proveedor): JsonResponse
    {
        $filters = $request->only(OrdenCompra::getFilters());
        $perPage = $request->input('per_page', 15);

        $originalPaginator = OrdenCompra::query()
            ->with(['empresaConstrucc', 'detalles'])
            ->where('proveedor_id', $proveedor->id)
            ->filter($filters)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return $this->paginated($originalPaginator);
    }

    /**
     * Obtener órdenes de compra por empresa constructora
     */
    public function porEmpresa(Request $request, EmpresaConstrucc $empresa): JsonResponse
    {
        $filters = $request->only(OrdenCompra::getFilters());
        $perPage = $request->input('per_page', 15);

        $originalPaginator = OrdenCompra::query()
            ->with(['proveedor', 'detalles'])
            ->where('empresa_construcc_id', $empresa->id)
            ->filter($filters)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return $this->paginated($originalPaginator);
    }

    /**
     * Estadísticas generales para construcción
     */
    public function estadisticas(Request $request): JsonResponse
    {
        $fechaDesde = $request->input('fecha_desde', now()->subDays(30)->format('Y-m-d'));
        $fechaHasta = $request->input('fecha_hasta', now()->format('Y-m-d'));

        $query = OrdenCompra::whereBetween('fecha_orden', [$fechaDesde, $fechaHasta]);

        $stats = [
            'total_ordenes' => $query->count(),
            'importe_total' => $query->sum('importe_total'),
            'por_estado' => $query->select('estado', DB::raw('count(*) as cantidad'))
                ->groupBy('estado')->get()->pluck('cantidad', 'estado'),
            'por_proveedor' => $query->join('proveedores', 'ordenes_compra.proveedor_id', '=', 'proveedores.id')
                ->select('proveedores.razon_social', DB::raw('count(*) as cantidad'))
                ->groupBy('proveedores.id', 'proveedores.razon_social')
                ->orderBy('cantidad', 'desc')
                ->limit(10)->get(),
            'por_empresa' => $query->join('empresas_construcc', 'ordenes_compra.empresa_construcc_id', '=', 'empresas_construcc.id')
                ->select('empresas_construcc.nombre', DB::raw('count(*) as cantidad'))
                ->groupBy('empresas_construcc.id', 'empresas_construcc.nombre')
                ->orderBy('cantidad', 'desc')
                ->limit(10)->get()
        ];

        return $this->success($stats);
    }

    // Métodos privados

    /**
     * Sincronizar detalles de una orden de compra
     */
    private function syncDetalles(OrdenCompra $ordenCompra, array $nuevosDetalles): void
    {
        // Eliminar detalles existentes
        $ordenCompra->detalles()->delete();

        // Crear nuevos detalles
        foreach ($nuevosDetalles as $detalleData) {
            OrdenCompraDetalle::create([
                'orden_compra_id' => $ordenCompra->id,
                'producto' => $detalleData['producto'],
                'descripcion' => $detalleData['descripcion'] ?? null,
                'cantidad' => $detalleData['cantidad'],
                'unidad_medida' => $detalleData['unidad_medida'] ?? null,
                'precio_unitario' => $detalleData['precio_unitario'],
            ]);
        }
    }
}
