<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrdenCompraConversionRequest;
use App\Http\Resources\SolicitudPago\SolicitudPagoResource;
use App\Models\OrdenCompra;
use App\Models\Proveedor;
use App\Models\SolicitudPago;
use App\Services\OrdenCompraConversionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrdenCompraSolicitudPagoController extends Controller
{
    protected OrdenCompraConversionService $conversionService;

    public function __construct(OrdenCompraConversionService $conversionService)
    {
        $this->conversionService = $conversionService;
    }

    /**
     * Crear solicitud de pago desde orden de compra
     */
    public function store(OrdenCompraConversionRequest $request, Proveedor $proveedor): JsonResponse
    {
        try {
            $ordenCompra = OrdenCompra::findOrFail($request->orden_compra_id);

            // Datos de la solicitud
            $datosSolicitud = [
                'monto_total' => $request->monto_total,
                'descripcion_concepto' => $request->descripcion_concepto,
                'usuario_id' => $request->usuario_id,
                'usuario_nombre' => $request->usuario_nombre,
                'sucursal_id' => $request->sucursal_id,
                'cotizacion_id' => $request->cotizacion_id,
                'notas_vinculacion' => $request->notas_vinculacion,
            ];

            // Convertir OC a SP
            $solicitudPago = $this->conversionService->convertToSolicitudPago(
                $ordenCompra,
                $datosSolicitud,
                $request->cuentas_bancarias
            );

            return $this->success(
                new SolicitudPagoResource($solicitudPago),
                'Solicitud de pago creada exitosamente desde orden de compra.',
                201
            );
        } catch (\Exception $e) {
            return $this->error('Error al crear solicitud de pago: '.$e->getMessage(), 500);
        }
    }

    /**
     * Pre-validar conversión antes de mostrar formulario
     */
    public function validateConversion(Request $request, Proveedor $proveedor): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'orden_compra_id' => 'required|integer|exists:ordenes_compra,id',
            'monto_total' => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return $this->error('Datos de validación incorrectos', 422, $validator->errors());
        }

        try {
            $ordenCompra = OrdenCompra::where('id', $request->orden_compra_id)
                ->where('proveedor_id', $proveedor->id)
                ->firstOrFail();

            $validacion = $this->conversionService->preValidateConversion(
                $ordenCompra,
                $request->only(['monto_total', 'empresa_construcc_id'])
            );

            return $this->success($validacion);
        } catch (\Exception $e) {
            return $this->error('Error en la validación: '.$e->getMessage(), 500);
        }
    }

    /**
     * Obtener preview de datos pre-llenados para conversión
     */
    public function getConversionPreview(Request $request, Proveedor $proveedor): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'orden_compra_id' => 'required|integer|exists:ordenes_compra,id',
            'monto_sugerido' => 'nullable|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return $this->error('Datos de validación incorrectos', 422, $validator->errors());
        }

        try {
            $ordenCompra = OrdenCompra::where('id', $request->orden_compra_id)
                ->where('proveedor_id', $proveedor->id)
                ->with(['empresaConstrucc', 'detalles'])
                ->firstOrFail();

            $preview = $this->conversionService->getConversionPreview(
                $ordenCompra,
                $request->monto_sugerido
            );

            return $this->success($preview);
        } catch (\Exception $e) {
            return $this->error('Error al obtener preview: '.$e->getMessage(), 500);
        }
    }

    /**
     * Desasociar solicitud de pago de orden de compra
     */
    public function unlinkSolicitudPago(Request $request, Proveedor $proveedor): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'orden_compra_id' => 'required|integer|exists:ordenes_compra,id',
            'solicitud_pago_id' => 'required|integer|exists:solicitudes_pago,id',
        ]);

        if ($validator->fails()) {
            return $this->error('Datos de validación incorrectos', 422, $validator->errors());
        }

        try {
            $ordenCompra = OrdenCompra::where('id', $request->orden_compra_id)
                ->where('proveedor_id', $proveedor->id)
                ->firstOrFail();

            $solicitudPago = SolicitudPago::where('id', $request->solicitud_pago_id)
                ->where('proveedor_id', $proveedor->id)
                ->firstOrFail();

            // Verificar que estén asociadas
            if ($solicitudPago->referencia_oc !== $ordenCompra->numero_orden) {
                return $this->error('La solicitud de pago no está asociada a esta orden de compra', 422);
            }

            // Actualizar campos de tracking en SP para desasociar
            $solicitudPago->update([
                'origen_oc' => false,
                'referencia_oc' => null,
                'monto_oc_original' => null,
            ]);

            // Actualizar contadores de la OC
            $ordenCompra->actualizarContadores();

            return $this->success([
                'mensaje' => 'Solicitud de pago desasociada exitosamente',
                'orden_compra' => [
                    'id' => $ordenCompra->id,
                    'numero_orden' => $ordenCompra->numero_orden,
                    'monto_disponible' => $ordenCompra->fresh()->getMontoDisponible(),
                ],
                'solicitud_pago' => [
                    'id' => $solicitudPago->id,
                    'numero_folio_solicitud' => $solicitudPago->numero_folio_solicitud,
                ],
            ]);
        } catch (\Exception $e) {
            return $this->error('Error al desasociar: '.$e->getMessage(), 500);
        }
    }

    /**
     * Obtener historial de conversiones de una orden de compra
     */
    public function getConversionHistory(Request $request, Proveedor $proveedor, OrdenCompra $ordenCompra): JsonResponse
    {
        if ($ordenCompra->proveedor_id !== $proveedor->id) {
            return $this->error('Orden de compra no pertenece a este proveedor', 403);
        }

        try {
            $historial = $this->conversionService->getConversionHistory($ordenCompra);

            return $this->success($historial);
        } catch (\Exception $e) {
            return $this->error('Error al obtener historial: '.$e->getMessage(), 500);
        }
    }

    /**
     * Obtener métricas de conversión por proveedor
     */
    public function getMetricasConversion(Request $request, Proveedor $proveedor): JsonResponse
    {
        $fechaDesde = $request->input('fecha_desde', now()->subDays(30)->format('Y-m-d'));
        $fechaHasta = $request->input('fecha_hasta', now()->format('Y-m-d'));

        try {
            $ordenesCompra = OrdenCompra::where('proveedor_id', $proveedor->id)
                ->whereBetween('fecha_orden', [$fechaDesde, $fechaHasta])
                ->with('solicitudesPago')
                ->get();

            $totalOC = $ordenesCompra->count();
            $ocConSP = $ordenesCompra->where('sp_count', '>', 0)->count();
            $totalImporteOC = $ordenesCompra->sum('importe_total');
            $totalImporteSP = $ordenesCompra->sum('monto_sp_asociado');

            $metricas = [
                'periodo' => [
                    'fecha_desde' => $fechaDesde,
                    'fecha_hasta' => $fechaHasta,
                ],
                'ordenes_compra' => [
                    'total' => $totalOC,
                    'con_sp' => $ocConSP,
                    'sin_sp' => $totalOC - $ocConSP,
                    'tasa_conversion' => $totalOC > 0 ? ($ocConSP / $totalOC) * 100 : 0,
                ],
                'montos' => [
                    'total_oc' => $totalImporteOC,
                    'total_sp' => $totalImporteSP,
                    'pendiente_conversion' => $totalImporteOC - $totalImporteSP,
                    'porcentaje_convertido' => $totalImporteOC > 0 ? ($totalImporteSP / $totalImporteOC) * 100 : 0,
                ],
                'solicitudes_pago' => [
                    'total_generadas' => $ordenesCompra->sum('sp_count'),
                    'promedio_por_oc' => $ocConSP > 0 ? $ordenesCompra->sum('sp_count') / $ocConSP : 0,
                ],
            ];

            return $this->success($metricas);
        } catch (\Exception $e) {
            return $this->error('Error al calcular métricas: '.$e->getMessage(), 500);
        }
    }

    /**
     * Listar conversiones recientes
     */
    public function getConversionesRecientes(Request $request, Proveedor $proveedor): JsonResponse
    {
        $limite = $request->input('limite', 10);
        $dias = $request->input('dias', 7);

        try {
            $solicitudesRecientes = SolicitudPago::where('proveedor_id', $proveedor->id)
                ->where('origen_oc', true)
                ->where('created_at', '>=', now()->subDays($dias))
                ->with(['ordenCompra', 'empresaConstrucc'])
                ->orderBy('created_at', 'desc')
                ->limit($limite)
                ->get();

            $conversiones = $solicitudesRecientes->map(function ($sp) {
                $ordenCompra = $sp->ordenCompra;

                return [
                    'solicitud_pago' => [
                        'id' => $sp->id,
                        'numero_folio_solicitud' => $sp->numero_folio_solicitud,
                        'monto_total' => $sp->monto_total,
                        'estado_solicitud' => $sp->estado_solicitud,
                        'fecha_creacion' => $sp->created_at,
                    ],
                    'orden_compra' => $ordenCompra ? [
                        'id' => $ordenCompra->id,
                        'numero_orden' => $ordenCompra->numero_orden,
                        'importe_total' => $ordenCompra->importe_total,
                        'monto_asociado' => $sp->monto_total,
                    ] : null,
                    'empresa' => $sp->empresaConstrucc->nombre ?? null,
                ];
            });

            return $this->success([
                'conversiones' => $conversiones,
                'total' => $conversiones->count(),
                'periodo_dias' => $dias,
            ]);
        } catch (\Exception $e) {
            return $this->error('Error al obtener conversiones recientes: '.$e->getMessage(), 500);
        }
    }
}
