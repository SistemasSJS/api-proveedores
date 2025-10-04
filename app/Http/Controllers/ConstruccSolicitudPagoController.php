<?php

namespace App\Http\Controllers;

use App\Enums\EstadoSP;
use App\Enums\EstadoSolicitud;
use App\Http\Resources\Construcc\ConstruccSolicitudPagoResource;
use App\Models\SolicitudPago;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Tymon\JWTAuth\Claims\JwtId;

class ConstruccSolicitudPagoController extends Controller
{
    use ApiResponse;

    /**
     * Roles que pueden autorizar solicitudes de pago
     */
    private const ROLES_AUTORIZACION = ['DG', 'DT', 'CO', 'SI'];
    
    /**
     * Roles que pueden rechazar solicitudes
     */
    private const ROLES_RECHAZO = ['DG', 'DT', 'CO', 'SI', 'DA'];
    
    /**
     * Rol que puede confirmar pagos
     */
    private const ROL_PAGO = 'DA';

    /**
     * Listado paginado filtrado por empresa de construcción
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(SolicitudPago::getFilters());
        $sortBy  = $request->input('sort_by', 'created_at');
        $order   = $request->input('order', 'desc');
        $perPage = $request->input('per_page', 10);

        $query = SolicitudPago::query()
            ->with(SolicitudPago::eagerLodable())
            ->filter($filters)
            ->orderBy($sortBy, $order);

        // Aquí debería limitar por la empresa del usuario ConstruccApp
        // if ($request->user()->empresa_construcc_id) {
        //     $query->where('empresa_construcc_id', $request->user()->empresa_construcc_id);
        // }

        $paginator = $query->paginate($perPage);

        return $this->paginated(
            $paginator->setCollection(
                ConstruccSolicitudPagoResource::collection($paginator)->collection
            )
        );
    }

    /**
     * Mostrar detalle de una solicitud
     */
    public function show(SolicitudPago $solicitudPago): JsonResponse
    {
        return $this->success(
            new ConstruccSolicitudPagoResource($solicitudPago->load(SolicitudPago::eagerLodable()))
        );
    }

    /**
     * Autorizar una solicitud de pago por rol específico
     * Roles: DG, DT, CO, SI
     * 
     * Reglas:
     * - Solo se puede autorizar si la SP está PENDIENTE
     * - DG puede autorizar directamente (pasa a estado compuesto)
     * - DT, CO, SI autorizan individualmente
     * - Si todos los roles [DG, DT, CO, SI] autorizan, pasa a AUTORIZADA
     */
    public function autorizar(Request $request, SolicitudPago $solicitudPago): JsonResponse
    {
        $request->validate([
            'rol' => ['required', 'string', Rule::in(self::ROLES_AUTORIZACION)]
        ]);

        $rol = strtoupper($request->rol);

        // Validar que la SP esté en estado PENDIENTE
        if ($solicitudPago->estado_solicitud !== EstadoSP::PENDIENTE->value) {
            return $this->error('Solo se pueden autorizar solicitudes en estado PENDIENTE.', null, 400);
        }

        // Validar que el rol no haya autorizado previamente
        $rolField = strtolower($rol === 'CO' ? 'pc' : $rol);
        if ($solicitudPago->$rolField === EstadoSolicitud::AUTORIZADA->value) {
            return $this->error('Este rol ya ha autorizado la solicitud.', null, 400);
        }

        // Actualizar el campo del rol y fecha correspondiente
        $fechaField = $rolField . '_fecha';
        $solicitudPago->update([
            $rolField => EstadoSolicitud::AUTORIZADA->value,
            $fechaField => now(),
        ]);

        // Verificar si todos los roles han autorizado para cambiar estado general
        $solicitudPago->refresh();
        // TODO: Esti esta aun en discusion si se agregar o no
        // $todosAutorizan = $this->verificarAutorizacionCompleta($solicitudPago);
        
        // if ($todosAutorizan) {
        //     $solicitudPago->update([
        //         'estado_solicitud' => EstadoSP::AUTORIZADA->value,
        //     ]);
        // }

        return $this->success(
            new ConstruccSolicitudPagoResource($solicitudPago->fresh()->load(SolicitudPago::eagerLodable())),
            "Solicitud autorizada correctamente por {$rol}."
        );
    }

    /**
     * Rechazar una solicitud de pago por rol específico
     * 
     * Reglas:
     * - DT, CO, SI, DA solo pueden rechazar si está PENDIENTE
     * - DG puede rechazar en cualquier momento antes de PAGADO
     */
    public function rechazar(Request $request, SolicitudPago $solicitudPago): JsonResponse
    {
        $request->validate([
            'rol' => ['required', 'string', Rule::in(self::ROLES_RECHAZO)],
            'motivo_rechazo' => 'required|string|max:500'
        ]);

        $rol = strtoupper($request->rol);
        $estadoActual = $solicitudPago->estado_solicitud;

        // Validaciones según el rol
        if ($rol !== 'DG') {
            // DT, CO, SI, DA solo pueden rechazar si está PENDIENTE
            if ($estadoActual !== EstadoSP::PENDIENTE->value) {
                return $this->error('Solo se pueden rechazar solicitudes en estado PENDIENTE.', null, 400);
            }
        } else {
            // DG puede rechazar antes de PAGADO
            if ($estadoActual === EstadoSP::PAGADO->value) {
                return $this->error('No se pueden rechazar solicitudes ya pagadas.', null, 400);
            }
        }

        // Actualizar estado y registrar quién rechazó
        $rolField = strtolower($rol === 'CO' ? 'pc' : $rol);
        $fechaField = $rolField . '_fecha';
        
        $solicitudPago->update([
            'estado_solicitud' => EstadoSP::RECHAZADA->value,
            'motivo_rechazo' => $request->motivo_rechazo,
            $rolField => EstadoSolicitud::RECHAZADA->value,
            $fechaField => now(),
        ]);

        return $this->success(
            new ConstruccSolicitudPagoResource($solicitudPago->fresh()->load(SolicitudPago::eagerLodable())),
            "Solicitud rechazada correctamente por {$rol}."
        );
    }

    /**
     * Confirmar pago de una solicitud (completo o parcial)
     * Solo DA puede confirmar pagos y debe subir comprobante
     * Solo es posible si la SP está AUTORIZADA
     */
    public function confirmarPago(Request $request, SolicitudPago $solicitudPago): JsonResponse
    {
        $request->validate([
            'comprobante' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'monto_abono' => 'required|numeric|min:0.01',
            'notas_abono' => 'nullable|string|max:500'
        ]);

        // Validar que esté en estado AUTORIZADA o ya tenga pagos parciales
        if (!in_array($solicitudPago->estado_solicitud, [EstadoSP::AUTORIZADA->value, EstadoSP::PAGADO->value])) {
            return $this->error('Solo se pueden confirmar pagos de solicitudes AUTORIZADAS.', null, 400);
        }

        // Inicializar saldos si es el primer abono
        $solicitudPago->inicializarSaldos();
        $solicitudPago->refresh();

        // Validar que el monto del abono no exceda el saldo pendiente
        $montoAbono = $request->monto_abono;
        if ($montoAbono > $solicitudPago->saldo_pendiente) {
            return $this->error(
                "El monto del abono ({$montoAbono}) no puede ser mayor al saldo pendiente ({$solicitudPago->saldo_pendiente}).",
                null, 
                400
            );
        }

        // Guardar comprobante
        $path = $request->file('comprobante')->store('comprobantes', 'private');

        // Actualizar saldos
        $pagoCompleto = $solicitudPago->actualizarSaldos($montoAbono);
        
        // Determinar el estado final
        $estadoFinal = $pagoCompleto ? EstadoSP::PAGADO->value : EstadoSP::AUTORIZADA->value;
        $estadoDA = $pagoCompleto ? EstadoSolicitud::PAGADO->value : EstadoSolicitud::AUTORIZADA->value;
        
        // Actualizar solicitud
        $solicitudPago->update([
            'estado_solicitud' => $estadoFinal,
            'ruta_archivo_comprobante_pago' => $path,
            'notas_abono' => $request->notas_abono,
            'da' => $estadoDA,
            'da_fecha' => now(),
        ]);

        $mensaje = $pagoCompleto 
            ? 'Pago completado correctamente. La solicitud ha sido pagada en su totalidad.'
            : "Abono registrado correctamente. Saldo pendiente: {$solicitudPago->fresh()->saldo_pendiente}";

        return $this->success(
            new ConstruccSolicitudPagoResource($solicitudPago->fresh()->load(SolicitudPago::eagerLodable())),
            $mensaje
        );
    }

    /**
     * Descargar comprobante de pago
     */
    public function descargarComprobante(SolicitudPago $solicitudPago)
    {
        if (
            !$solicitudPago->ruta_archivo_comprobante_pago ||
            !Storage::disk('private')->exists($solicitudPago->ruta_archivo_comprobante_pago)
        ) {
            return $this->error('Comprobante no disponible', null, 404);
        }

        return response()->download(
            Storage::disk('private')->path($solicitudPago->ruta_archivo_comprobante_pago)
        );
    }

    /**
     * Descargar factura PDF
     */
    public function descargarFacturaPdf(SolicitudPago $solicitudPago)
    {
        if (
            !$solicitudPago->ruta_archivo_factura_pdf ||
            !Storage::disk('private')->exists($solicitudPago->ruta_archivo_factura_pdf)
        ) {
            return $this->error('Factura PDF no disponible', null, 404);
        }

        return response()->download(
            Storage::disk('private')->path($solicitudPago->ruta_archivo_factura_pdf)
        );
    }

    /**
     * Descargar factura XML
     */
    public function descargarFacturaXml(SolicitudPago $solicitudPago)
    {
        if (
            !$solicitudPago->ruta_archivo_factura_xml ||
            !Storage::disk('private')->exists($solicitudPago->ruta_archivo_factura_xml)
        ) {
            return $this->error('Factura XML no disponible', null, 404);
        }

        return response()->download(
            Storage::disk('private')->path($solicitudPago->ruta_archivo_factura_xml)
        );
    }

    /**
     * Descargar cotización
     */
    public function descargarCotizacion(SolicitudPago $solicitudPago)
    {
        if (
            !$solicitudPago->ruta_archivo_cotizacion ||
            !Storage::disk('private')->exists($solicitudPago->ruta_archivo_cotizacion)
        ) {
            return $this->error('Cotización no disponible', null, 404);
        }

        return response()->download(
            Storage::disk('private')->path($solicitudPago->ruta_archivo_cotizacion)
        );
    }

    /**
     * Listar solicitudes pendientes para un rol específico
     * Muestra solo las SP que necesitan acción de ese rol
     */
    public function listarPorRol(Request $request): JsonResponse
    {
        $request->validate([
            'rol' => ['required', 'string', Rule::in(['DG', 'DT', 'CO', 'SI', 'DA'])]
        ]);

        $rol = strtoupper($request->rol);
        $rolField = strtolower($rol === 'CO' ? 'pc' : $rol);

        $filters = $request->only(SolicitudPago::getFilters());
        $sortBy = $request->input('sort_by', 'created_at');
        $order = $request->input('order', 'desc');
        $perPage = $request->input('per_page', 10);

        $query = SolicitudPago::query()->with(SolicitudPago::eagerLodable());

        // Filtrar según el rol
        if ($rol === 'DA') {
            // DA ve las solicitudes AUTORIZADAS para confirmar pago
            // O las que ya tienen pagos parciales (estado PAGADO pero no pago_completo)
            $query->where(function($q) {
                $q->where('estado_solicitud', EstadoSP::AUTORIZADA->value)
                  ->orWhere(function($subQ) {
                      $subQ->where('estado_solicitud', EstadoSP::PAGADO->value)
                           ->where('pago_completo', false);
                  });
            });
        } else {
            // Otros roles: SP en estado PENDIENTE que no han autorizado aún
            $query->where('estado_solicitud', EstadoSP::PENDIENTE->value)
                  ->where(function($q) use ($rolField) {
                      $q->where($rolField, EstadoSolicitud::PENDIENTE->value)
                        ->orWhereNull($rolField);
                  });
                  
            // Si es DG, mostrar todas las pendientes independientemente de otros roles
            // Si es DT, CO, SI - no mostrar las que ya autorizó DG (para evitar duplicados)
            if ($rol !== 'DG') {
                $query->where(function($q) {
                    $q->where('dg', '!=', EstadoSolicitud::AUTORIZADA->value)
                      ->orWhereNull('dg')
                      ->orWhere('dg', EstadoSolicitud::PENDIENTE->value);
                });
            }
        }

        $query->filter($filters)->orderBy($sortBy, $order);
        $paginator = $query->paginate($perPage);

        return $this->paginated(
            $paginator->setCollection(
                ConstruccSolicitudPagoResource::collection($paginator)->collection
            ),
            "Solicitudes para rol {$rol}"
        );
    }

    /**
     * Listar solicitudes por estado específico
     */
    public function listarPorEstado(Request $request): JsonResponse
    {
        $request->validate([
            'estado' => ['required', 'string', Rule::in(['PENDIENTE', 'AUTORIZADA', 'RECHAZADA', 'PAGADO'])]
        ]);

        $estado = strtoupper($request->estado);
        $estadoEnum = EstadoSP::from(strtolower($estado));

        $filters = $request->only(SolicitudPago::getFilters());
        $sortBy = $request->input('sort_by', 'created_at');
        $order = $request->input('order', 'desc');
        $perPage = $request->input('per_page', 10);

        $query = SolicitudPago::query()
            ->with(SolicitudPago::eagerLodable())
            ->where('estado_solicitud', $estadoEnum->value)
            ->filter($filters)
            ->orderBy($sortBy, $order);

        $paginator = $query->paginate($perPage);

        return $this->paginated(
            $paginator->setCollection(
                ConstruccSolicitudPagoResource::collection($paginator)->collection
            ),
            "Solicitudes en estado {$estado}"
        );
    }

    /**
     * Dashboard con estadísticas por rol
     */
    public function estadisticasPorRol(Request $request): JsonResponse
    {
        $request->validate([
            'rol' => ['nullable', 'string', Rule::in(['DG', 'DT', 'CO', 'SI', 'DA'])]
        ]);

        $rol = $request->rol ? strtoupper($request->rol) : null;

        $stats = [
            'pendientes' => 0,
            'autorizadas' => 0,
            'rechazadas' => 0,
            'pagadas_completas' => 0,
            'con_pagos_parciales' => 0,
            'monto_total_pendiente' => 0,
            'monto_total_autorizado' => 0,
            'monto_total_pagado' => 0
        ];

        if ($rol) {
            $rolField = strtolower($rol === 'CO' ? 'pc' : $rol);
            
            if ($rol === 'DA') {
                $stats['pendientes'] = SolicitudPago::where('estado_solicitud', EstadoSP::AUTORIZADA->value)->count();
                $stats['con_pagos_parciales'] = SolicitudPago::where('estado_solicitud', EstadoSP::PAGADO->value)
                    ->where('pago_completo', false)->count();
            } else {
                $stats['pendientes'] = SolicitudPago::where('estado_solicitud', EstadoSP::PENDIENTE->value)
                    ->where(function($q) use ($rolField) {
                        $q->where($rolField, EstadoSolicitud::PENDIENTE->value)
                          ->orWhereNull($rolField);
                    })->count();
            }
        }

        // Estadísticas generales
        $stats['pendientes'] = SolicitudPago::where('estado_solicitud', EstadoSP::PENDIENTE->value)->count();
        $stats['autorizadas'] = SolicitudPago::where('estado_solicitud', EstadoSP::AUTORIZADA->value)->count();
        $stats['rechazadas'] = SolicitudPago::where('estado_solicitud', EstadoSP::RECHAZADA->value)->count();
        $stats['pagadas_completas'] = SolicitudPago::where('estado_solicitud', EstadoSP::PAGADO->value)
            ->where('pago_completo', true)->count();
        $stats['con_pagos_parciales'] = SolicitudPago::where('estado_solicitud', EstadoSP::PAGADO->value)
            ->where('pago_completo', false)->count();

        // Montos
        $stats['monto_total_pendiente'] = SolicitudPago::where('estado_solicitud', EstadoSP::PENDIENTE->value)
            ->sum('monto_total');
        $stats['monto_total_autorizado'] = SolicitudPago::where('estado_solicitud', EstadoSP::AUTORIZADA->value)
            ->sum('saldo_pendiente');
        $stats['monto_total_pagado'] = SolicitudPago::sum('monto_abonado');

        return $this->success($stats, 'Estadísticas de solicitudes de pago');
    }

    /**
     * Verificar si todos los roles han autorizado la solicitud
     */
    private function verificarAutorizacionCompleta(SolicitudPago $solicitudPago): bool
    {
        return $solicitudPago->dg === EstadoSolicitud::AUTORIZADA->value &&
               $solicitudPago->dt === EstadoSolicitud::AUTORIZADA->value &&
               $solicitudPago->pc === EstadoSolicitud::AUTORIZADA->value &&
               $solicitudPago->si === EstadoSolicitud::AUTORIZADA->value;
    }
}
