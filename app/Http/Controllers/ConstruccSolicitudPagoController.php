<?php

namespace App\Http\Controllers;

use App\Enums\EstadoSolicitud;
use App\Enums\EstadoSP;
use App\Http\Requests\Construcc\SolicitudPagoAutorizarRequest;
use App\Http\Requests\Construcc\SolicitudPagoConfirmarPagoRequest;
use App\Http\Requests\Construcc\SolicitudPagoRechazarRequest;
use App\Http\Resources\Construcc\ConstruccSolicitudPagoResource;
use App\Models\SolicitudPago;
use App\Notifications\SolicitudPago\SolicitudPagoPagada;
use App\Notifications\SolicitudPago\SolicitudPagoRechazada;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ConstruccSolicitudPagoController extends Controller
{
    use ApiResponse;

    /**
     * Roles que pueden autorizar solicitudes de pago
     */
    private const ROLES_AUTORIZACION = ['DG', 'DT', 'PC', 'SI'];

    /**
     * Roles que pueden rechazar solicitudes
     */
    private const ROLES_RECHAZO = ['DG', 'DT', 'PC', 'SI', 'DA'];

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
        $sortBy = $request->input('sort_by', 'created_at');
        $order = $request->input('order', 'desc');
        $perPage = $request->input('per_page', 10000);

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
     * Roles: DG, DT, PC, SI
     *
     * Reglas:
     * - Solo se puede autorizar si la SP está PENDIENTE
     * - DG puede autorizar directamente (pasa a estado compuesto)
     * - DT, PC, SI autorizan individualmente
     * - Si todos los roles [DG, DT, PC, SI] autorizan, pasa a AUTORIZADA
     */
    public function autorizar(SolicitudPagoAutorizarRequest $request, SolicitudPago $solicitudPago): JsonResponse
    {
        $data = $request->validated();
        $rol = strtoupper($data['rol']);

        // Validar que la SP esté en estado PENDIENTE
        if ($solicitudPago->estado_solicitud !== EstadoSP::PENDIENTE->value) {
            return $this->error('Solo se pueden autorizar solicitudes en estado PENDIENTE.', null, 400);
        }

        // Validar que el rol no haya autorizado previamente
        $rolField = strtolower($rol === 'PC' ? 'pc' : $rol);
        if (EstadoSolicitud::AUTORIZADA->value === $solicitudPago->$rolField) {
            return $this->error('Este rol ya ha autorizado la solicitud.', null, 400);
        }

        // Actualizar el campo del rol y fecha correspondiente
        $fechaField = $rolField.'_fecha';
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
     * - DT, PC, SI, DA solo pueden rechazar si está PENDIENTE
     * - DG puede rechazar en cualquier momento antes de PAGADO
     */
    public function rechazar(SolicitudPagoRechazarRequest $request, SolicitudPago $solicitudPago): JsonResponse
    {
        $data = $request->validated();

        $rol = strtoupper($data['rol']);
        $estadoActual = $solicitudPago->estado_solicitud;

        // Validaciones según el rol
        if ($rol !== 'DG') {
            // DT, PC, SI, DA solo pueden rechazar si está PENDIENTE
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
        $rolField = strtolower($rol === 'PC' ? 'pc' : $rol);
        $fechaField = $rolField.'_fecha';

        $solicitudPago->update([
            'estado_solicitud' => EstadoSP::RECHAZADA->value,
            'motivo_rechazo' => $data['motivo_rechazo'],
            'fecha_rechazo' => now(),
            $rolField => EstadoSolicitud::RECHAZADA->value,
            $fechaField => now(),
        ]);

        // Enviar notificación al proveedor
        try {
            $proveedor = $solicitudPago->proveedor;
            $usuarioPrincipal = $proveedor->usuarioPrincipal();

            if ($usuarioPrincipal) {
                $usuarioPrincipal->notify(new SolicitudPagoRechazada(
                    $solicitudPago->folio,
                    $proveedor->id,
                    $solicitudPago->empresa_construcc_id,
                    $data['motivo_rechazo']
                ));

                Log::info('✅ Notificación de SP Rechazada enviada', [
                    'solicitud_pago_id' => $solicitudPago->id,
                    'folio' => $solicitudPago->folio,
                    'proveedor_id' => $proveedor->id,
                    'usuario_id' => $usuarioPrincipal->id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('❌ Error al enviar notificación de SP Rechazada', [
                'solicitud_pago_id' => $solicitudPago->id,
                'error' => $e->getMessage(),
            ]);
            // No fallar la operación si la notificación falla
        }

        return $this->success(
            new ConstruccSolicitudPagoResource($solicitudPago->fresh()->load(SolicitudPago::eagerLodable())),
            "Solicitud rechazada correctamente por {$rol}."
        );
    }

    /**
     * Confirmar pago de una solicitud (completo o parcial)
     * Solo DA puede confirmar pagos y debe subir comprobante
     * Solo es posible si la SP cumple con las reglas de autorización:
     *  - DG aprobó (tiene fuerza mayor) O al menos uno de DT/PC/SI aprobó
     *  - No debe tener roles rechazados
     *  - Debe estar en estado AUTORIZADA o PAGADO (pagos parciales)
     *  - No debe estar completamente pagada
     */
    public function confirmarPago(SolicitudPagoConfirmarPagoRequest $request, SolicitudPago $solicitudPago): JsonResponse
    {
        $data = $request->validated();
        // 1. Verificar que tenga autorización suficiente (DG o al menos uno de DT/PC/SI)
        // if (!$this->verificarAutorizacionDeAlmenosUno($solicitudPago)) {
        //     return $this->error(
        //         'La solicitud no tiene autorización suficiente. Requiere aprobación de DG o al menos uno de DT/PC/SI.',
        //         null,
        //         400
        //     );
        // }

        // 2. Verificar que no tenga roles rechazados
        if ($solicitudPago->estado_solicitud === EstadoSP::RECHAZADA->value) {
            return $this->error(
                'No se puede confirmar el pago porque uno o más roles han rechazado la solicitud.',
                null,
                400
            );
        }

        // 3. Verificar que esté en estado válido (AUTORIZADA o PAGADO con pagos parciales)
        if (! in_array($solicitudPago->estado_solicitud, [EstadoSP::PENDIENTE->value, EstadoSP::PAGADO->value])) {
            return $this->error(
                'Solo se pueden confirmar pagos de solicitudes AUTORIZADAS o con pagos parciales.',
                null,
                400
            );
        }

        // 4. Verificar que no esté completamente pagada
        if ($solicitudPago->estado_solicitud === EstadoSP::PAGADO->value && $solicitudPago->pago_completo === true) {
            return $this->error(
                'Esta solicitud ya ha sido pagada completamente.',
                null,
                400
            );
        }

        // Inicializar saldos si es el primer abono
        $solicitudPago->inicializarSaldos();
        $solicitudPago->refresh();

        // Validar que el monto del abono no exceda el saldo pendiente
        $montoAbono = $data['monto_pagado'];
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

        // para no dejar comentadas las lineas anteriores se agrega esta linea con el proposito
        //  de parchar la actul funcionalidad. dado un pago ya se parcial o completo el estatus se asumira
        //  como pagada.
        //
        //  Falta realizar revision del tema.
        $estadoFinal = EstadoSP::PAGADO->value;
        $estadoDA = EstadoSolicitud::PAGADO->value;

        // Actualizar solicitud
        $solicitudPago->update([
            'estado_solicitud' => $estadoFinal,
            'ruta_archivo_comprobante_pago' => $path,
            'notas_abono' => $request->notas_abono,
            'fecha_pago' => now(),
            'da' => $estadoDA,
            'da_fecha' => now(),
        ]);

        $mensaje = $pagoCompleto
            ? 'Pago completado correctamente. La solicitud ha sido pagada en su totalidad.'
            : "Abono registrado correctamente. Saldo pendiente: {$solicitudPago->fresh()->saldo_pendiente}";

        // Enviar notificación al proveedor
        try {
            $proveedor = $solicitudPago->proveedor;
            $usuarioPrincipal = $proveedor->usuarioPrincipal();

            if ($usuarioPrincipal) {
                $usuarioPrincipal->notify(new SolicitudPagoPagada(
                    $solicitudPago->folio,
                    $proveedor->id,
                    $solicitudPago->empresa_construcc_id,
                    $montoAbono
                ));

                Log::info('✅ Notificación de SP Pagada enviada', [
                    'solicitud_pago_id' => $solicitudPago->id,
                    'folio' => $solicitudPago->folio,
                    'proveedor_id' => $proveedor->id,
                    'usuario_id' => $usuarioPrincipal->id,
                    'monto' => $montoAbono,
                    'pago_completo' => $pagoCompleto,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('❌ Error al enviar notificación de SP Pagada', [
                'solicitud_pago_id' => $solicitudPago->id,
                'error' => $e->getMessage(),
            ]);
            // No fallar la operación si la notificación falla
        }

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
            ! $solicitudPago->ruta_archivo_comprobante_pago ||
            ! Storage::disk('private')->exists($solicitudPago->ruta_archivo_comprobante_pago)
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
            ! $solicitudPago->ruta_archivo_factura_pdf ||
            ! Storage::disk('private')->exists($solicitudPago->ruta_archivo_factura_pdf)
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
            ! $solicitudPago->ruta_archivo_factura_xml ||
            ! Storage::disk('private')->exists($solicitudPago->ruta_archivo_factura_xml)
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
            ! $solicitudPago->ruta_archivo_cotizacion ||
            ! Storage::disk('private')->exists($solicitudPago->ruta_archivo_cotizacion)
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
            'rol' => ['required', 'string', Rule::in(['DG', 'DT', 'PC', 'SI', 'DA', 'RO'])],
        ]);

        $rol = strtoupper($request->rol);
        $rolField = strtolower($rol === 'PC' ? 'pc' : $rol);

        $filters = $request->only(SolicitudPago::getFilters());
        $sortBy = $request->input('sort_by', 'created_at');
        $order = $request->input('order', 'desc');
        $perPage = $request->input('per_page', 10);

        $query = SolicitudPago::query()->with(SolicitudPago::eagerLodable());

        // Filtrar según el rol
        if ($rol === 'DA') {
            // DA ve las solicitudes AUTORIZADAS para confirmar pago
            // O las que ya tienen pagos parciales (estado PAGADO pero no pago_completo)
            $query->where('estado_solicitud', '!=', EstadoSP::AUTORIZADA->value)
                ->where(function ($q) {
                    $q->where('dg', EstadoSolicitud::AUTORIZADA->value)
                        ->orWhere('dt', EstadoSolicitud::AUTORIZADA->value)
                        ->orWhere('pc', EstadoSolicitud::AUTORIZADA->value)
                        ->orWhere('si', EstadoSolicitud::AUTORIZADA->value);
                })
                ->where('pago_completo', false);
        } elseif ($rol === 'RO') {
            $query->where(function ($q) {
                $q->where('estado_solicitud', EstadoSP::PENDIENTE->value);
                //  TODO : Filtar las que peretenencen a ese RO
            });
        } elseif ($rol === 'DG') {
            // Si es DG, mostrar todas las pendientes independientemente de otros roles
            // Si es DT, PC, SI - no mostrar las que ya autorizó DG (para evitar duplicados)
            $query->where(function ($q) {
                $q->where('dg', '!=', EstadoSolicitud::AUTORIZADA->value)
                    ->orWhereNull('dg')
                    ->orWhere('dg', EstadoSolicitud::PENDIENTE->value);
            });
        } elseif ($rol === 'DT') {
            // Si es DG, mostrar todas las pendientes independientemente de otros roles
            // Si es DT, PC, SI - no mostrar las que ya autorizó DG (para evitar duplicados)
            $query->where(function ($q) {
                $q->where('dt', '!=', EstadoSolicitud::AUTORIZADA->value)
                    ->orWhereNull('dt')
                    ->orWhere('dt', EstadoSolicitud::PENDIENTE->value);
            });
        } elseif ($rol === 'PC') {
            // Si es DG, mostrar todas las pendientes independientemente de otros roles
            // Si es DT, PC, SI - no mostrar las que ya autorizó DG (para evitar duplicados)
            $query->where(function ($q) {
                $q->where('pc', '!=', EstadoSolicitud::AUTORIZADA->value)
                    ->orWhereNull('pc')
                    ->orWhere('pc', EstadoSolicitud::PENDIENTE->value);
            });
        } elseif ($rol === 'SI') {
            // Si es DG, mostrar todas las pendientes independientemente de otros roles
            // Si es DT, PC, SI - no mostrar las que ya autorizó DG (para evitar duplicados)
            $query->where(function ($q) {
                $q->where('si', '!=', EstadoSolicitud::AUTORIZADA->value)
                    ->orWhereNull('si')
                    ->orWhere('si', EstadoSolicitud::PENDIENTE->value);
            });
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
            'estado' => ['required', 'string', Rule::in(['PENDIENTE', 'AUTORIZADA', 'RECHAZADA', 'PAGADO'])],
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
            'rol' => ['nullable', 'string', Rule::in(['DG', 'DT', 'PC', 'SI', 'DA'])],
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
            'monto_total_pagado' => 0,
        ];

        if ($rol) {
            $rolField = strtolower($rol === 'PC' ? 'pc' : $rol);

            if ($rol === 'DA') {
                $stats['pendientes'] = SolicitudPago::where('estado_solicitud', EstadoSP::AUTORIZADA->value)->count();
                $stats['con_pagos_parciales'] = SolicitudPago::where('estado_solicitud', EstadoSP::PAGADO->value)
                    ->where('pago_completo', false)->count();
            } else {
                $stats['pendientes'] = SolicitudPago::where('estado_solicitud', EstadoSP::PENDIENTE->value)
                    ->where(function ($q) use ($rolField) {
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

    private function verificarAutorizacionDeAlmenosUno(SolicitudPago $solicitudPago): bool
    {
        $autorizado = EstadoSolicitud::AUTORIZADA->value;

        // DG tiene fuerza mayor - si DG autoriza, es suficiente
        if ($solicitudPago->dg === $autorizado) {
            return true;
        }

        // Al menos uno de DT, PC o SI debe haber autorizado
        return $solicitudPago->dt === $autorizado ||
            $solicitudPago->pc === $autorizado ||
            $solicitudPago->si === $autorizado;
    }

    /**
     * Listar proveedores asociados a una empresa constructora
     */
    public function proveedoresPorEmpresa($empresaId): JsonResponse
    {
        $proveedores = \App\Models\Proveedor::query()
            ->whereHas('empresasConstrucc', function ($q) use ($empresaId) {
                $q->where('empresa_construcc_id', $empresaId);
            })
            ->select('id', 'nombre_comercial', 'razon_social', 'rfc')
            ->orderBy('nombre_comercial')
            ->get();

        if ($proveedores->isEmpty()) {
            return $this->error('No se encontraron proveedores asociados a esta empresa.', null, 200);
        }

        return $this->success($proveedores, 'Proveedores asociados a la empresa constructora.');
    }

    /**
     * Listar proveedores NO asociados a una empresa constructora
     */
    public function proveedoresNoAsociadosPorEmpresa($empresaId): JsonResponse
    {
        $proveedores = \App\Models\Proveedor::query()
            ->whereDoesntHave('empresasConstrucc', function ($q) use ($empresaId) {
                $q->where('empresa_construcc_id', $empresaId);
            })
            ->select('id', 'nombre_comercial', 'razon_social', 'rfc')
            ->orderBy('nombre_comercial')
            ->get();

        if ($proveedores->isEmpty()) {
            return $this->error('No se encontraron proveedores disponibles para asociar a esta empresa.', null, 200);
        }

        return $this->success($proveedores, 'Proveedores disponibles para asociar a la empresa constructora.');
    }

    /**
     * Asociar un proveedor a una empresa constructora
     */
    public function asociarProveedorAEmpresa(Request $request, $empresaId): JsonResponse
    {
        $request->validate([
            'proveedor_id' => ['required', 'integer', 'exists:proveedores,id'],
        ]);

        $proveedorId = $request->input('proveedor_id');

        // Verificar que la empresa constructora exista
        $empresa = \App\Models\EmpresaConstrucc::find($empresaId);
        if (! $empresa) {
            return $this->error('La empresa constructora no existe.', null, 200);
        }

        // Verificar que el proveedor exista
        $proveedor = \App\Models\Proveedor::find($proveedorId);
        if (! $proveedor) {
            return $this->error('El proveedor no existe.', null, 200);
        }

        // Verificar si ya existe la asociación
        $existeAsociacion = $proveedor->empresasConstrucc()
            ->where('empresa_construcc_id', $empresaId)
            ->exists();

        if ($existeAsociacion) {
            return $this->error('El proveedor ya está asociado a esta empresa constructora.', null, 200);
        }

        // Crear la asociación
        $proveedor->empresasConstrucc()->attach($empresaId);

        // Recargar el proveedor con la nueva asociación
        $proveedorActualizado = $proveedor->fresh([
            'empresasConstrucc' => function ($query) {
                $query->select('id', 'nombre', 'rfc');
            },
        ]);

        return $this->success(
            $proveedorActualizado,
            'Proveedor asociado exitosamente a la empresa constructora.'
        );
    }

    /**
     * Buscar empresas constructoras
     */
    public function empresasConstructoras(Request $request): JsonResponse
    {
        $buscar = $request->input('search', '');
        $limit = min($request->input('limit', 20), 50); // Máximo 50 resultados

        $query = \App\Models\EmpresaConstrucc::query()
            ->select('id', 'nombre', 'rfc', 'razon_social');

        if ($buscar) {
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('rfc', 'like', "%{$buscar}%")
                    ->orWhere('razon_social', 'like', "%{$buscar}%");
            });
        }

        $empresas = $query->orderBy('nombre')
            ->limit($limit)
            ->get();

        if ($empresas->isEmpty()) {
            return $this->error('No se encontraron empresas constructoras.', null, 200);
        }

        return $this->success($empresas, 'Empresas constructoras encontradas.');
    }

    /**
     * Obtener estadísticas generales
     */
    public function estadisticas(Request $request): JsonResponse
    {
        $stats = [
            'total_solicitudes' => SolicitudPago::count(),
            'pendientes' => SolicitudPago::where('estado_solicitud', EstadoSP::PENDIENTE->value)->count(),
            'autorizadas' => SolicitudPago::where('estado_solicitud', EstadoSP::AUTORIZADA->value)->count(),
            'rechazadas' => SolicitudPago::where('estado_solicitud', EstadoSP::RECHAZADA->value)->count(),
            'pagadas' => SolicitudPago::where('estado_solicitud', EstadoSP::PAGADO->value)->count(),
            'monto_total' => SolicitudPago::sum('monto_total'),
            'monto_pagado' => SolicitudPago::sum('monto_abonado'),
            'monto_pendiente' => SolicitudPago::sum('saldo_pendiente'),
        ];

        return $this->success($stats, 'Estadísticas generales de solicitudes de pago.');
    }
}
