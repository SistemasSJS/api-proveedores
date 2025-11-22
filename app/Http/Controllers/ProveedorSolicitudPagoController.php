<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudPago\CrearSolicitudPagoRequest;
use App\Http\Resources\SolicitudPago\SolicitudPagoResource;
use App\Models\EmpresaConstrucc;
use App\Models\Proveedor;
use App\Models\SolicitudPago;
use App\Services\InterApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProveedorSolicitudPagoController extends Controller
{
    protected $interApiService;

    public function __construct(InterApiService $interApiService)
    {
        $this->interApiService = $interApiService;
    }
    /**
     * Listado con paginación, filtrando por proveedor
     */
    public function index(Request $request, Proveedor $proveedor): JsonResponse
    {
        $filters = $request->only(SolicitudPago::getFilters());
        $sortBy = $request->input('sort_by', 'created_at');
        $order = $request->input('order', 'desc');
        $perPage = $request->input('per_page', 10);

        $originalPaginator = SolicitudPago::query()
            ->with(SolicitudPago::eagerLodable())
            ->where('proveedor_id', $proveedor->id)
            ->filter($filters)
            ->orderBy($sortBy, $order)
            ->paginate($perPage);

        $data = SolicitudPagoResource::collection($originalPaginator)->resolve();

        return $this->paginated($originalPaginator->setCollection(collect($data)));
    }

    /**
     * Listado sin paginación, filtrando por proveedor
     */
    public function uindex(Request $request, Proveedor $proveedor): JsonResponse
    {
        $filters = $request->only(SolicitudPago::getFilters());
        $sortBy = $request->input('sort_by', 'created_at');
        $order = $request->input('order', 'desc');

        $items = SolicitudPago::query()
            ->with(['proveedor', 'empresaConstrucc', 'cuentasBancarias'])
            ->where('proveedor_id', $proveedor->id)
            ->filter($filters)
            ->orderBy($sortBy, $order)
            ->get();

        return $this->success(
            SolicitudPagoResource::collection($items)
        );
    }

    /**
     * Crear nueva solicitud
     */
    public function store(CrearSolicitudPagoRequest $request, Proveedor $proveedor): JsonResponse
    {
        $validated = $request->validated();

        $facturaPdf = $request->file('factura_pdf');
        $facturaXml = $request->file('factura_xml');
        $cotizacionFile = $request->file('cotizacion');

        if (! $facturaPdf || ! $facturaXml) {
            return response()->json([
                'success' => false,
                'message' => 'Los archivos PDF y XML son obligatorios.',
            ], 422);
        }

        $rutaPdf = $facturaPdf->store('facturas/pdf', 'private');
        $rutaXml = $facturaXml->store('facturas/xml', 'private');

        // Procesar archivo de cotización si existe
        $rutaCotizacion = null;
        if ($cotizacionFile) {
            $rutaCotizacion = $cotizacionFile->store('cotizaciones', 'private');
        }

        $numeroFolio = SolicitudPago::generarNumeroFolio($proveedor);
        $empresaConstructId = $validated['empresa_construcc_id'] ?? null;
        // Datos del usuario de Construcc que genera la SP
        $usuarioId = $validated['usuario_id'] ?? $validated['usuario_construcc_id'] ?? null;
        $usuarioNombre = $validated['usuario_nombre'] ?? $validated['residente'] ?? null;
        $cotizacion_id = $validated['cotizacion_id'] ?? null;
        $montoTotal = $validated['monto_total'];

        $solicitud = SolicitudPago::create([
            'proveedor_id' => $proveedor->id,
            'numero_folio_solicitud' => $numeroFolio,
            'descripcion_concepto' => $validated['descripcion_concepto'] ?? '',
            'observaciones' => $validated['observaciones'] ?? null,
            'ruta_archivo_factura_pdf' => $rutaPdf,
            'ruta_archivo_factura_xml' => $rutaXml,
            'ruta_archivo_cotizacion' => $rutaCotizacion,
            'empresa_construcc_id' => $empresaConstructId,
            'usuario_id' => $usuarioId,
            'usuario_nombre' => $usuarioNombre,
            'cotizacion_id' => $cotizacion_id,
            'estado_solicitud' => 'pendiente',
            'fecha_registro_pendiente' => now(),
            'monto_total' => $montoTotal,
            'saldo_pendiente' => $montoTotal,
            'monto_abonado' => 0,
            'pago_completo' => false,
        ]);

        // $this->interApiService->notifyNewSolicitudCompra($solicitud);

        // Sincronizar cuentas bancarias si se enviaron
        if (array_key_exists('cuentas_bancarias', $validated) && is_array($validated['cuentas_bancarias'])) {
            $solicitud->sincronizarCuentasBancarias($validated['cuentas_bancarias']);
        }


        return $this->success(
            new SolicitudPagoResource($solicitud->load(['proveedor', 'empresaConstrucc', 'cuentasBancarias'])),
            'Solicitud de pago creada correctamente.',
            201
        );
    }

    /**
     * Mostrar detalle
     */
    public function show(Proveedor $proveedor, SolicitudPago $solicitudPago): JsonResponse
    {
        if ($solicitudPago->proveedor_id !== $proveedor->id) {
            return $this->error('Solicitud no pertenece a este proveedor', 403);
        }

        return $this->success(
            new SolicitudPagoResource($solicitudPago->load(SolicitudPago::eagerLodable()))
        );
    }

    /**
     * Actualizar solicitud de pago
     */
    public function update(Request $request, Proveedor $proveedor, SolicitudPago $solicitudPago): JsonResponse
    {
        if ($solicitudPago->proveedor_id !== $proveedor->id) {
            return $this->error('Solicitud no pertenece a este proveedor', 403);
        }

        // Validar que se puede actualizar según el estado
        if (! in_array($solicitudPago->estado_solicitud, ['pendiente', 'procesando'])) {
            return $this->error('No se puede actualizar una solicitud en este estado', 422);
        }

        $solicitudPago->update($request->only([
            'descripcion_concepto',
            'observaciones',
            'usuario_id',
            'usuario_nombre',
            'monto_total',
        ]));

        // Si se actualiza el monto, recalcular saldos
        if ($request->has('monto_total')) {
            $solicitudPago->update([
                'saldo_pendiente' => $request->monto_total - $solicitudPago->monto_abonado,
            ]);
        }

        return $this->success(
            new SolicitudPagoResource($solicitudPago->load(SolicitudPago::eagerLodable())),
            'Solicitud de pago actualizada correctamente.'
        );
    }

    /**
     * Actualizar cuentas bancarias de la solicitud de pago
     */
    public function actualizarCuentasBancarias(Request $request, Proveedor $proveedor, SolicitudPago $solicitudPago): JsonResponse
    {
        if ($solicitudPago->proveedor_id !== $proveedor->id) {
            return $this->error('Solicitud no pertenece a este proveedor', 403);
        }

        $request->validate([
            'cuentas_bancarias' => 'required|array',
            'cuentas_bancarias.*.cuenta_bancaria_id' => 'required|integer|exists:cuentas_bancarias,id',
            'cuentas_bancarias.*.datos_especificos' => 'nullable|array',
            'cuentas_bancarias.*.datos_especificos.alias' => 'nullable|string|max:255',
            'cuentas_bancarias.*.datos_especificos.banco_clave' => 'nullable|string|max:10',
            'cuentas_bancarias.*.datos_especificos.banco_nombre' => 'nullable|string|max:255',
            'cuentas_bancarias.*.datos_especificos.tipo_cuenta' => 'nullable|string|max:255',
            'cuentas_bancarias.*.datos_especificos.campo_dependiente' => 'nullable|string|max:255',
            'cuentas_bancarias.*.datos_especificos.titular_cuenta' => 'nullable|string|max:255',
            'cuentas_bancarias.*.datos_especificos.referencia' => 'nullable|string|max:255',
            'cuentas_bancarias.*.datos_especificos.estatus' => 'nullable|integer|min:0|max:2',
            'cuentas_bancarias.*.datos_especificos.sucursal' => 'nullable|string|max:255',
            'cuentas_bancarias.*.datos_especificos.swift' => 'nullable|string|max:255',
            'cuentas_bancarias.*.datos_especificos.preferida' => 'nullable|boolean',
        ]);

        // Sincronizar las cuentas bancarias
        $solicitudPago->sincronizarCuentasBancarias($request->cuentas_bancarias);

        return $this->success(
            new SolicitudPagoResource($solicitudPago->load(['proveedor', 'empresaConstrucc', 'cuentasBancarias'])),
            'Cuentas bancarias actualizadas correctamente.'
        );
    }

    /**
     * Subir comprobante (solo guarda el archivo, no cambia estado)
     */
    public function subirComprobantePago(Request $request, Proveedor $proveedor, SolicitudPago $solicitudPago): JsonResponse
    {
        if ($solicitudPago->proveedor_id !== $proveedor->id) {
            return $this->error('Solicitud no pertenece a este proveedor', 403);
        }

        $request->validate([
            'comprobante' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $file = $request->file('comprobante');
        $path = $file->store('comprobantes', 'private');

        $solicitudPago->update([
            'ruta_archivo_comprobante_pago' => $path,
            'fecha_con_comprobante' => now(),
        ]);

        return $this->success(
            new SolicitudPagoResource($solicitudPago->load(['proveedor', 'empresaConstrucc', 'cuentasBancarias'])),
            'Comprobante de pago subido correctamente.'
        );
    }

    /**
     * Descargar factura PDF
     */
    public function descargarFacturaPdf(Proveedor $proveedor, SolicitudPago $solicitudPago)
    {
        if ($solicitudPago->proveedor_id !== $proveedor->id) {
            return $this->error('Solicitud no pertenece a este proveedor', 403);
        }

        if (! $solicitudPago->ruta_archivo_factura_pdf || ! Storage::disk('private')->exists($solicitudPago->ruta_archivo_factura_pdf)) {
            return $this->error('Factura PDF no disponible', 404);
        }

        return response()->download(
            Storage::disk('private')->path($solicitudPago->ruta_archivo_factura_pdf)
        );
    }

    /**
     * Descargar factura XML
     */
    public function descargarFacturaXml(Proveedor $proveedor, SolicitudPago $solicitudPago)
    {
        if ($solicitudPago->proveedor_id !== $proveedor->id) {
            return $this->error('Solicitud no pertenece a este proveedor', 403);
        }

        if (! $solicitudPago->ruta_archivo_factura_xml || ! Storage::disk('private')->exists($solicitudPago->ruta_archivo_factura_xml)) {
            return $this->error('Factura XML no disponible', 404);
        }

        return response()->download(
            Storage::disk('private')->path($solicitudPago->ruta_archivo_factura_xml)
        );
    }

    /**
     * Descargar comprobante de pago
     */
    public function descargarComprobantePago(Proveedor $proveedor, SolicitudPago $solicitudPago)
    {
        if ($solicitudPago->proveedor_id !== $proveedor->id) {
            return $this->error('Solicitud no pertenece a este proveedor', 403);
        }

        if (! $solicitudPago->ruta_archivo_comprobante_pago || ! Storage::disk('private')->exists($solicitudPago->ruta_archivo_comprobante_pago)) {
            return $this->error('Comprobante no disponible', 404);
        }

        return response()->download(
            Storage::disk('private')->path($solicitudPago->ruta_archivo_comprobante_pago)
        );
    }

    /**
     * Descargar cotización
     */
    public function descargarCotizacion(Proveedor $proveedor, SolicitudPago $solicitudPago)
    {
        if ($solicitudPago->proveedor_id !== $proveedor->id) {
            return $this->error('Solicitud no pertenece a este proveedor', 403);
        }

        if (! $solicitudPago->ruta_archivo_cotizacion || ! Storage::disk('private')->exists($solicitudPago->ruta_archivo_cotizacion)) {
            return $this->error('Cotización no disponible', 404);
        }

        return response()->download(
            Storage::disk('private')->path($solicitudPago->ruta_archivo_cotizacion)
        );
    }

    /**
     * Autorizar
     */
    public function autorizar(Request $request, Proveedor $proveedor, SolicitudPago $solicitudPago): JsonResponse
    {
        if ($solicitudPago->proveedor_id !== $proveedor->id) {
            return $this->error('Solicitud no pertenece a este proveedor', 403);
        }

        $solicitudPago->update([
            'estado_solicitud' => 'autorizada',
            'fecha_aprobado' => now(),
        ]);

        return $this->success(
            new SolicitudPagoResource($solicitudPago->load(['proveedor', 'empresaConstrucc', 'cuentasBancarias'])),
            'Solicitud autorizada correctamente.'
        );
    }

    /**
     * Rechazar
     */
    public function rechazar(Request $request, Proveedor $proveedor, SolicitudPago $solicitudPago): JsonResponse
    {
        if ($solicitudPago->proveedor_id !== $proveedor->id) {
            return $this->error('Solicitud no pertenece a este proveedor', 403);
        }

        $request->validate([
            'motivo_rechazo' => 'required|string|max:500',
        ]);

        $solicitudPago->update([
            'estado_solicitud' => 'rechazada',
            'fecha_rechazado' => now(),
            'motivo_rechazo' => $request->motivo_rechazo,
        ]);

        return $this->success(
            new SolicitudPagoResource($solicitudPago->load(['proveedor', 'empresaConstrucc', 'cuentasBancarias'])),
            'Solicitud rechazada correctamente.'
        );
    }

    /**
     * Confirmar pago de solicitud (desde el proveedor)
     */
    public function confirmarPagoSP(Request $request, Proveedor $proveedor, SolicitudPago $solicitudPago): JsonResponse
    {
        if ($solicitudPago->proveedor_id !== $proveedor->id) {
            return $this->error('Solicitud no pertenece a este proveedor', 403);
        }

        if ($solicitudPago->estado_solicitud !== 'autorizada') {
            return $this->error('Solo se pueden confirmar solicitudes autorizadas', 422);
        }

        $solicitudPago->update([
            'estado_solicitud' => 'pagada',
            'fecha_confirmacion_pago' => now(),
            'pago_completo' => true,
        ]);

        return $this->success(
            new SolicitudPagoResource($solicitudPago->load(SolicitudPago::eagerLodable())),
            'Pago confirmado correctamente.'
        );
    }

    /**
     * Cambiar estado a procesando
     */
    public function procesando(Request $request, Proveedor $proveedor, SolicitudPago $solicitudPago): JsonResponse
    {
        if ($solicitudPago->proveedor_id !== $proveedor->id) {
            return $this->error('Solicitud no pertenece a este proveedor', 403);
        }

        if ($solicitudPago->estado_solicitud !== 'pendiente') {
            return $this->error('Solo se pueden marcar como procesando las solicitudes pendientes', 422);
        }

        $solicitudPago->update([
            'estado_solicitud' => 'procesando',
            'fecha_procesando' => now(),
        ]);

        return $this->success(
            new SolicitudPagoResource($solicitudPago->load(SolicitudPago::eagerLodable())),
            'Solicitud marcada como procesando.'
        );
    }

    /**
     * Obtener histórico de OC y SP del proveedor
     */
    public function historico(Request $request, Proveedor $proveedor): JsonResponse
    {
        $fechaDesde = $request->input('fecha_desde', now()->subMonths(3)->format('Y-m-d'));
        $fechaHasta = $request->input('fecha_hasta', now()->format('Y-m-d'));
        $perPage = $request->input('per_page', 15);

        // Obtener SP con OC vinculadas
        $solicitudes = SolicitudPago::where('proveedor_id', $proveedor->id)
            ->whereBetween('created_at', [$fechaDesde, $fechaHasta])
            ->with(['empresaConstrucc', 'ordenesCompra'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        // Mapear datos con información de OC vinculada
        $data = $solicitudes->getCollection()->map(function ($sp) {
            $ordenCompra = $sp->ordenesCompra->first();

            return [
                'solicitud_pago' => [
                    'id' => $sp->id,
                    'numero_folio_solicitud' => $sp->numero_folio_solicitud,
                    'monto_total' => $sp->monto_total,
                    'estado_solicitud' => $sp->estado_solicitud,
                    'fecha_creacion' => $sp->created_at,
                    'descripcion_concepto' => $sp->descripcion_concepto,
                    'usuario_id' => $sp->usuario_id,
                    'usuario_nombre' => $sp->usuario_nombre,
                    'origen_oc' => $sp->origen_oc ?? false,
                ],
                'orden_compra' => $ordenCompra ? [
                    'id' => $ordenCompra->id,
                    'numero_orden' => $ordenCompra->numero_orden,
                    'importe_total' => $ordenCompra->importe_total,
                    'estado' => $ordenCompra->estado,
                    'fecha_orden' => $ordenCompra->fecha_orden,
                    'monto_asociado' => $ordenCompra->pivot->monto_asociado ?? 0,
                    'fecha_vinculacion' => $ordenCompra->pivot->fecha_vinculacion,
                ] : null,
                'empresa' => [
                    'id' => $sp->empresaConstrucc->id,
                    'nombre' => $sp->empresaConstrucc->nombre,
                    'rfc' => $sp->empresaConstrucc->rfc,
                ],
                'timeline' => $this->getTimelineSP($sp),
            ];
        });

        return $this->paginated($solicitudes->setCollection($data));
    }

    /**
     * Generar timeline de estados de una SP
     */
    private function getTimelineSP(SolicitudPago $sp): array
    {
        $timeline = [];

        if ($sp->fecha_registro_pendiente) {
            $timeline[] = [
                'estado' => 'pendiente',
                'fecha' => $sp->fecha_registro_pendiente,
                'descripcion' => 'Solicitud creada',
            ];
        }

        if ($sp->fecha_procesando) {
            $timeline[] = [
                'estado' => 'procesando',
                'fecha' => $sp->fecha_procesando,
                'descripcion' => 'En proceso de revisión',
            ];
        }

        if ($sp->fecha_aprobado) {
            $timeline[] = [
                'estado' => 'autorizada',
                'fecha' => $sp->fecha_aprobado,
                'descripcion' => 'Solicitud autorizada',
            ];
        }

        if ($sp->fecha_rechazado) {
            $timeline[] = [
                'estado' => 'rechazada',
                'fecha' => $sp->fecha_rechazado,
                'descripcion' => 'Solicitud rechazada: ' . ($sp->motivo_rechazo ?? 'Sin motivo especificado'),
            ];
        }

        if ($sp->fecha_confirmacion_pago) {
            $timeline[] = [
                'estado' => 'pagada',
                'fecha' => $sp->fecha_confirmacion_pago,
                'descripcion' => 'Pago confirmado',
            ];
        }

        return $timeline;
    }

    /**
     * Obtener conteo de solicitudes por estado
     */
    public function conteoPorEstado(Request $request, Proveedor $proveedor): JsonResponse
    {
        $filters = $request->only(['fecha_registro_pendiente_desde', 'fecha_registro_pendiente_hasta', 'empresa_construcc_id']);

        // Base query con filtros opcionales
        $baseQuery = SolicitudPago::query()
            ->where('proveedor_id', $proveedor->id);

        // Aplicar filtros si existen
        if (!empty($filters)) {
            $baseQuery->filter($filters);
        }

        // Conteos por estado (usando STRINGS - el campo estado_solicitud NO usa el enum)
        $conteos = [
            'total' => (clone $baseQuery)->count(),
            'pendientes' => (clone $baseQuery)->where('estado_solicitud', 'pendiente')->count(),
            'autorizadas' => (clone $baseQuery)->where('estado_solicitud', 'autorizada')->count(),
            'rechazadas' => (clone $baseQuery)->where('estado_solicitud', 'rechazada')->count(),
            'pagadas' => (clone $baseQuery)->where('estado_solicitud', 'pagado')->count(),
        ];

        return $this->success($conteos, 'Conteo por estado obtenido correctamente');
    }

    /**
     * Eliminar
     */
    public function destroy(Proveedor $proveedor, SolicitudPago $solicitudPago): JsonResponse
    {
        if ($solicitudPago->proveedor_id !== $proveedor->id) {
            return $this->error('Solicitud no pertenece a este proveedor', 403);
        }

        $solicitudPago->delete();

        return $this->success(null, 'Solicitud de pago eliminada correctamente.');
    }

    /**
     * Empresas de construcción
     */
    public function empresasConstructoras(Request $request): JsonResponse
    {
        $search = $request->input('search', '');
        $limit = $request->input('limit', 20);

        $query = EmpresaConstrucc::activo();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'LIKE', "%{$search}%")
                    ->orWhere('razon_social', 'LIKE', "%{$search}%")
                    ->orWhere('rfc', 'LIKE', "%{$search}%");
            });
        }

        $empresas = $query->limit($limit)->get();

        return $this->success(
            $empresas->map(function ($empresa) {
                return [
                    'id' => $empresa->id,
                    'nombre' => $empresa->nombre,
                    'rfc' => $empresa->rfc,
                    'razon_social' => $empresa->razon_social,
                    'representante_legal' => $empresa->representante_legal,
                ];
            })
        );
    }

    /**
     * Métricas del dashboard de SP
     * Retorna conteo de solicitudes por estado
     */
    public function getDashboardMetrics(Request $request, Proveedor $proveedor): JsonResponse
    {
        $filters = $request->only(['fecha_registro_pendiente_desde', 'fecha_registro_pendiente_hasta', 'empresa_construcc_id']);

        // Base query con filtros opcionales
        $baseQuery = SolicitudPago::query()
            ->where('proveedor_id', $proveedor->id);

        // Aplicar filtros si existen
        if (!empty($filters)) {
            $baseQuery->filter($filters);
        }

        // Conteos por estado
        $conteos = [
            'total_sp' => (clone $baseQuery)->count(),
            'sp_pendientes' => (clone $baseQuery)->where('estado_solicitud', 'pendiente')->count(),
            'sp_autorizadas' => (clone $baseQuery)->where('estado_solicitud', 'autorizada')->count(),
            'sp_en_proceso' => (clone $baseQuery)->where('estado_solicitud', 'autorizada')->count(),
            'sp_rechazadas' => (clone $baseQuery)->where('estado_solicitud', 'rechazada')->count(),
            'sp_pagadas' => (clone $baseQuery)->where('estado_solicitud', 'pagado')->count(),
        ];

        return $this->success($conteos, 'Métricas del dashboard obtenidas correctamente');
    }
}
