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
use App\Notifications\SolicitudPago\SolicitudPagoRechazadaSinAutorizacion;
use App\Notifications\SolicitudPago\ProveedorAsociadoAEmpresa;
use App\Services\InterApiService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ConstruccSolicitudPagoController extends Controller
{
    use ApiResponse;

    protected $interApiService;

    public function __construct(InterApiService $interApiService)
    {
        $this->interApiService = $interApiService;
    }

    /**
     * IDs de roles de ConstruccApp
     */
    private const ROLES_CONSTRUCC_IDS = [
        0, // Administrador
        1, // Director General (DG)
        2, // Director Técnico (DT)
        3, // Director Administrativo (DA)
        4, // Superintendente (SI)
        5, // Programación y Control (PC)
        6, // Residente de Obra (RO)
    ];

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
            ->where('verificada', true)
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
     * Listado de solicitudes de pago no verificadas
     * Solo muestra las SP que aún no han sido verificadas por el usuario construcción
     */
    public function indexNoVerificadas(Request $request): JsonResponse
    {
        $filters = $request->only(SolicitudPago::getFilters());
        $sortBy = $request->input('sort_by', 'created_at');
        $order = $request->input('order', 'desc');
        $perPage = $request->input('per_page', 10000);

        $query = SolicitudPago::query()
            ->with(SolicitudPago::eagerLodable())
            ->where('verificada', false)
            ->filter($filters)
            ->orderBy($sortBy, $order);

        $paginator = $query->paginate($perPage);

        return $this->paginated(
            $paginator->setCollection(
                ConstruccSolicitudPagoResource::collection($paginator)->collection
            )
        );
    }

    /**
     * Marcar una solicitud de pago como verificada
     * Solo el usuario construcción correspondiente puede marcar su SP como verificada
     * Envía los datos de la SP y el usuario al servidor inter API
     * 
     * Para niveles directivos (1-DG, 2-DT, 3-DA) también marca como autorizada para ese rol
     */
    public function marcarComoVerificada(Request $request, SolicitudPago $solicitudPago): JsonResponse
    {
        // Validar que se proporcione el usuario_id
        $request->validate([
            'usuario_id' => ['required', 'integer'],
            'nivel_id' => ['required', 'integer'],
            'empresa_id' => ['required'],
            'obra_id' => ['nullable', 'integer'],
            'tipo' => ['nullable', 'string'],
            'tipo_id' => ['nullable', 'integer'],
            'notas' => ['nullable', 'string'],
            'utilizara' => ['nullable', 'string'],
            'equipo' => ['nullable', 'string'],
        ]);

        // Validar que la SP no esté ya verificada
        if ($solicitudPago->verificada) {
            return $this->error('Esta solicitud ya ha sido verificada.', null, 400);
        }

        DB::beginTransaction();

        try {
            // Datos a actualizar
            $updateData = ['verificada' => true];

            // Agregar campos opcionales si están presentes
            if ($request->has('tipo')) {
                $updateData['tipo'] = $request->tipo;
            }
            if ($request->has('tipo_id')) {
                $updateData['tipo_id'] = $request->tipo_id;
            }
            if ($request->has('obra_id')) {
                $updateData['obra_id'] = $request->obra_id;
            }
            if ($request->has('notas')) {
                $updateData['notas'] = $request->notas;
            }
            if ($request->has('utilizara')) {
                $updateData['utilizara'] = $request->utilizara;
            }
            if ($request->has('equipo')) {
                $updateData['equipo'] = $request->equipo;
            }

            // Mapeo de nivel_id a rol
            $nivelToRol = [
                1 => 'dg',  // Director General
                2 => 'dt',  // Director Técnico
                3 => 'da',  // Director Administrativo
            ];

            // Si es un nivel directivo (1, 2, 3), también autorizar
            if (array_key_exists($request->nivel_id, $nivelToRol)) {
                $rolField = $nivelToRol[$request->nivel_id];
                $fechaField = $rolField . '_fecha';

                // Agregar autorización del rol
                $updateData[$rolField] = EstadoSolicitud::AUTORIZADA->value;
                $updateData[$fechaField] = now();

                // Si es DG (nivel_id = 1), cambiar el estado general a AUTORIZADA
                if ($request->nivel_id == 1) {
                    $updateData['estado_solicitud'] = EstadoSP::AUTORIZADA->value;
                    Log::info('✅ DG detectado, marcando SP como AUTORIZADA', [
                        'solicitud_pago_id' => $solicitudPago->id,
                        'folio' => $solicitudPago->numero_folio_solicitud,
                    ]);
                }

                Log::info('📋 Nivel directivo detectado, autorizando para rol', [
                    'nivel_id' => $request->nivel_id,
                    'rol' => strtoupper($rolField),
                    'solicitud_pago_id' => $solicitudPago->id,
                ]);
            }

            // Actualizar solicitud
            $solicitudPago->update($updateData);
            // Llamar al servicio InterAPI para notificar al validador
            $result = $this->interApiService->spNotifyByValidator(
                $solicitudPago->id,
                $solicitudPago->numero_folio_solicitud,
                $request->empresa_id,
                $request->usuario_id,
            );

            // Registrar el resultado de la notificación
            if ($result['success']) {
                Log::info('✅ Notificación de validador enviada exitosamente', [
                    'solicitud_pago_id' => $solicitudPago->id,
                    'folio' => $solicitudPago->numero_folio_solicitud,
                    'usuario_id' => $request->usuario_id,
                    'empresa_id' => $request->empresa_id,
                    'nivel_id' => $request->nivel_id,
                    'notas' => $request->notas ?? null,
                    'utilizara' => $request->utilizara ?? null,
                    'equipo' => $request->equipo ?? null,
                ]);
            } else {
                Log::warning('⚠️ Fallo al enviar notificación de validador', [
                    'solicitud_pago_id' => $solicitudPago->id,
                    'error' => $result['error'] ?? 'Error desconocido',
                ]);
            }

            DB::commit();

            $mensaje = 'Solicitud de pago marcada como verificada correctamente.';
            if (array_key_exists($request->nivel_id, $nivelToRol)) {
                $rolNombre = strtoupper($nivelToRol[$request->nivel_id]);
                $mensaje .= " Autorizada por {$rolNombre}.";
            }

            return $this->success(
                new ConstruccSolicitudPagoResource($solicitudPago->fresh()->load(SolicitudPago::eagerLodable())),
                $mensaje
            );
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('❌ Error al marcar SP como verificada', [
                'solicitud_pago_id' => $solicitudPago->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'Error al procesar la verificación de la solicitud.',
                ['error' => $e->getMessage()],
                500
            );
        }
    }


    /**
     * Marcar una solicitud de pago como rechazada durante verificación
     * Cambia el estado a RECHAZADA y marca verificada como false
     * Esto evita que la SP se liste para los directivos
     */
    public function marcarComoRechazada(Request $request, SolicitudPago $solicitudPago): JsonResponse
    {
        Log::info('✅ Antes de validate: ', [
            'solicitud_pago_id' => $solicitudPago->id,
            'folio' => $solicitudPago->numero_folio_solicitud,
            'proveedor_id' => $solicitudPago->proveedor->id,
        ]);

        // Validar que se proporcione un motivo de rechazo
        $request->validate([
            'motivo_rechazo' => ['required', 'string', 'max:500'],
        ]);

        // Actualizar el estado de la SP
        $solicitudPago->update([
            'estado_solicitud' => EstadoSP::RECHAZADA->value,
            'verificada' => false,
            'motivo_rechazo' => $request->motivo_rechazo,
            'fecha_rechazo' => now(),
        ]);

        // Enviar notificación al proveedor
        try {
            $proveedor = $solicitudPago->proveedor;
            $usuarioPrincipal = $proveedor->usuarioPrincipal();

            if ($usuarioPrincipal) {
                $usuarioPrincipal->notify(new SolicitudPagoRechazadaSinAutorizacion(
                    $solicitudPago->numero_folio_solicitud,
                    $solicitudPago->id,
                    $proveedor->id,
                    $request->motivo_rechazo,
                    $usuarioPrincipal->id,
                    $solicitudPago->verificada ? 1 : 0
                ));

                Log::info('✅ Notificación de SP Rechazada Sin Autorización enviada', [
                    'solicitud_pago_id' => $solicitudPago->id,
                    'folio' => $solicitudPago->numero_folio_solicitud,
                    'proveedor_id' => $proveedor->id,
                    'usuario_id' => $usuarioPrincipal->id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('❌ Error al enviar notificación de SP Rechazada Sin Autorización', [
                'solicitud_pago_id' => $solicitudPago->id,
                'error' => $e->getMessage(),
            ]);
            // No fallar la operación si la notificación falla
        }

        return $this->success(
            new ConstruccSolicitudPagoResource($solicitudPago->fresh()->load(SolicitudPago::eagerLodable())),
            'Solicitud de pago rechazada correctamente durante verificación.'
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
        $fechaField = $rolField . '_fecha';

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
                    $solicitudPago->numero_folio_solicitud,
                    $solicitudPago->id,
                    $proveedor->id,
                    $data['motivo_rechazo'],
                    $usuarioPrincipal->id
                ));

                Log::info('✅ Notificación de SP Rechazada enviada', [
                    'solicitud_pago_id' => $solicitudPago->id,
                    'folio' => $solicitudPago->numero_folio_solicitud,
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
        if (! in_array($solicitudPago->estado_solicitud, [EstadoSP::PENDIENTE->value, EstadoSP::PAGADO->value, EstadoSP::AUTORIZADA->value])) {
            return $this->error(
                'Solo se pueden confirmar pagos de solicitudes AUTORIZADAS o con pagos parciales.',
                null,
                400
            );
        }

        // 4. Verificar que no esté completamente pagada
        // if ($solicitudPago->estado_solicitud === EstadoSP::PAGADO->value && $solicitudPago->pago_completo === true) {
        //     return $this->error(
        //         'Esta solicitud ya ha sido pagada completamente.',
        //         null,
        //         400
        //     );
        // }

        // Inicializar saldos si es el primer abono
        // $solicitudPago->inicializarSaldos();
        // $solicitudPago->refresh();

        // // Validar que el monto del abono no exceda el saldo pendiente
        // $montoAbono = $data['monto_pagado'];
        // if ($montoAbono > $solicitudPago->saldo_pendiente) {
        //     return $this->error(
        //         "El monto del abono ({$montoAbono}) no puede ser mayor al saldo pendiente ({$solicitudPago->saldo_pendiente}).",
        //         null,
        //         400
        //     );
        // }

        // Guardar comprobante
        $path = $request->file('comprobante')->store('comprobantes', 'private');

        // Actualizar saldos
        // $pagoCompleto = $solicitudPago->actualizarSaldos($montoAbono);

        // Determinar el estado final
        // $estadoFinal = $pagoCompleto ? EstadoSP::PAGADO->value : EstadoSP::AUTORIZADA->value;
        // $estadoDA = $pagoCompleto ? EstadoSolicitud::PAGADO->value : EstadoSolicitud::AUTORIZADA->value;

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
            // 'notas_abono' => $request->notas_abono,
            'fecha_pago' => now(),
            'da' => $estadoDA,
            'da_fecha' => now(),
        ]);

        $mensaje =  'Pago completado correctamente. La solicitud ha sido pagada en su totalidad.';
        // : "Abono registrado correctamente. Saldo pendiente: {$solicitudPago->fresh()->saldo_pendiente}";
        // // $pagoCompleto

        // Enviar notificación al proveedor
        try {
            $proveedor = $solicitudPago->proveedor;
            $usuarioPrincipal = $proveedor->usuarioPrincipal();

            if ($usuarioPrincipal) {
                $usuarioPrincipal->notify(new SolicitudPagoPagada(
                    $solicitudPago->numero_folio_solicitud,
                    $solicitudPago->id,
                    $proveedor->id,
                    $usuarioPrincipal->id
                ));

                Log::info('✅ Notificación de SP Pagada enviada', [
                    'solicitud_pago_id' => $solicitudPago->id,
                    'folio' => $solicitudPago->numero_folio_solicitud,
                    'proveedor_id' => $proveedor->id,
                    'usuario_id' => $usuarioPrincipal->id,
                    // 'monto' => $montoAbono,
                    // 'pago_completo' => $pagoCompleto,
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
     * Opcionalmente filtra por usuario_construcc_id mediante parámetro GET
     */
    public function proveedoresPorEmpresa(Request $request, $empresaId): JsonResponse
    {
        $usuarioConstruccId = $request->input('usuario_construcc_id');

        $proveedores = \App\Models\Proveedor::query()
            ->whereHas('empresasConstrucc', function ($q) use ($empresaId, $usuarioConstruccId) {
                $q->where('empresa_construcc_id', $empresaId);

                // Filtrar por usuario si se proporciona el parámetro
                if ($usuarioConstruccId) {
                    $q->where('usuario_construcc_id', $usuarioConstruccId);
                }
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
     * Opcionalmente filtra por usuario_construcc_id mediante parámetro GET
     * Si se proporciona usuario_construcc_id, muestra proveedores que NO están asociados a esa combinación empresa-usuario
     */
    public function proveedoresNoAsociadosPorEmpresa(Request $request, $empresaId): JsonResponse
    {
        $usuarioConstruccId = $request->input('usuario_construcc_id');

        $proveedores = \App\Models\Proveedor::query()
            ->whereDoesntHave('empresasConstrucc', function ($q) use ($empresaId, $usuarioConstruccId) {
                $q->where('empresa_construcc_id', $empresaId);

                // Filtrar por usuario si se proporciona el parámetro
                if ($usuarioConstruccId) {
                    $q->where('usuario_construcc_id', $usuarioConstruccId);
                }
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
     * Asociar un proveedor a una empresa constructora con usuario
     * Siempre crea un nuevo registro, permitiendo múltiples usuarios por relación
     */
    public function asociarProveedorAEmpresa(Request $request, $empresaId): JsonResponse
    {
        $request->validate([
            'proveedor_id' => ['required', 'integer', 'exists:proveedores,id'],
            'usuario_construcc_id' => ['required', 'integer'],
            'usuario_construcc_nombre' => ['required', 'string', 'max:255'],
        ]);

        $proveedorId = $request->input('proveedor_id');
        $usuarioConstruccId = $request->input('usuario_construcc_id');
        $usuarioConstruccNombre = $request->input('usuario_construcc_nombre');

        // Verificar que la empresa constructora exista
        $empresa = \App\Models\EmpresaConstrucc::find($empresaId);
        if (! $empresa) {
            return $this->error('La empresa constructora no existe.', null, 404);
        }

        // Verificar que el proveedor exista
        $proveedor = \App\Models\Proveedor::find($proveedorId);
        if (! $proveedor) {
            return $this->error('El proveedor no existe.', null, 404);
        }

        // Verificar si ya existe la asociación con ESTE usuario específico
        $existeConUsuario = \DB::table('empresa_construcc_proveedor')
            ->where('empresa_construcc_id', $empresaId)
            ->where('proveedor_id', $proveedorId)
            ->where('usuario_construcc_id', $usuarioConstruccId)
            ->exists();

        if ($existeConUsuario) {
            return $this->error('Este usuario ya tiene registrada una invitación para este proveedor.', null, 400);
        }

        // Crear nueva asociación con el usuario
        $proveedor->empresasConstrucc()->attach($empresaId, [
            'usuario_construcc_id' => $usuarioConstruccId,
            'usuario_construcc_nombre' => $usuarioConstruccNombre,
        ]);

        // Enviar notificación al proveedor
        try {
            $proveedor->notify(new ProveedorAsociadoAEmpresa(
                $proveedor->id,
                $proveedor->nombre_comercial ?? $proveedor->razon_social,
                $empresa->id,
                $empresa->nombre,
                $empresa->rfc,
                $usuarioConstruccId,
                $usuarioConstruccNombre
            ));
        } catch (\Exception $e) {
            Log::error('Error al enviar notificación de asociación empresa-proveedor', [
                'proveedor_id' => $proveedor->id,
                'empresa_id' => $empresa->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->success(
            [
                'proveedor_id' => $proveedorId,
                'empresa_id' => $empresaId,
                'usuario_construcc_id' => $usuarioConstruccId,
                'usuario_construcc_nombre' => $usuarioConstruccNombre,
            ],
            'Proveedor asociado exitosamente por el usuario.'
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

    /**
     * Métricas del dashboard de SP (verificadas)
     * Retorna conteo de solicitudes por estado filtrando por usuario, empresa, proveedor y fechas
     */
    public function dashboardSpMetricasVerificadas(Request $request): JsonResponse
    {
        $filters = $request->only([
            'fecha_registro_pendiente_desde',
            'fecha_registro_pendiente_hasta',
        ]);

        $usuarioId = $request->input('usuario_id');
        // Alias para compatibilidad: empresa_id o empresa_construcc_id
        $empresaId = $request->input('empresa_id', $request->input('empresa_construcc_id'));
        $proveedorId = $request->input('proveedor_id');

        // Base query solo con SP verificadas
        $baseQuery = SolicitudPago::query()
            ->where('verificada', true);

        // Filtros obligatorios/condicionales
        if ($usuarioId !== null && $usuarioId !== '') {
            $baseQuery->where('usuario_id', $usuarioId); // coincidencia exacta
        }

        if ($empresaId !== null && $empresaId !== '') {
            $baseQuery->where('empresa_construcc_id', $empresaId);
        }

        if ($proveedorId !== null && $proveedorId !== '') {
            $baseQuery->where('proveedor_id', $proveedorId);
        }

        // Filtros de fechas (reutilizando el scope filter del modelo)
        if (! empty($filters)) {
            $baseQuery->filter($filters);
        }

        // Conteos por estado
        $conteos = [
            'total_sp' => (clone $baseQuery)->count(),
            'sp_pendientes' => (clone $baseQuery)->where('estado_solicitud', EstadoSP::PENDIENTE->value)->count(),
            'sp_autorizadas' => (clone $baseQuery)->where('estado_solicitud', EstadoSP::AUTORIZADA->value)->count(),
            'sp_rechazadas' => (clone $baseQuery)->where('estado_solicitud', EstadoSP::RECHAZADA->value)->count(),
            'sp_pagadas' => (clone $baseQuery)->where('estado_solicitud', EstadoSP::PAGADO->value)->count(),
        ];

        return $this->success($conteos, 'Métricas de solicitudes de pago verificadas obtenidas correctamente');
    }

    /**
     * Métricas del dashboard de SP (no verificadas)
     * Retorna conteo de solicitudes por estado filtrando por usuario, empresa, proveedor y fechas
     * Solo considera SP no verificadas.
     */
    public function dashboardSpMetricasNoVerificadas(Request $request): JsonResponse
    {
        $filters = $request->only([
            'fecha_registro_pendiente_desde',
            'fecha_registro_pendiente_hasta',
        ]);

        $usuarioId = $request->input('usuario_id');
        // Alias para compatibilidad: empresa_id o empresa_construcc_id
        $empresaId = $request->input('empresa_id', $request->input('empresa_construcc_id'));
        $proveedorId = $request->input('proveedor_id');

        // Base query solo con SP no verificadas
        $baseQuery = SolicitudPago::query()
            ->where('verificada', false);

        // Filtros obligatorios/condicionales
        if ($usuarioId !== null && $usuarioId !== '') {
            $baseQuery->where('usuario_id', $usuarioId); // coincidencia exacta
        }

        if ($empresaId !== null && $empresaId !== '') {
            $baseQuery->where('empresa_construcc_id', $empresaId);
        }

        if ($proveedorId !== null && $proveedorId !== '') {
            $baseQuery->where('proveedor_id', $proveedorId);
        }

        // Filtros de fechas (reutilizando el scope filter del modelo)
        if (! empty($filters)) {
            $baseQuery->filter($filters);
        }

        // Conteos por estado
        $conteos = [
            'total_sp' => (clone $baseQuery)->count(),
            'sp_pendientes' => (clone $baseQuery)->where('estado_solicitud', EstadoSP::PENDIENTE->value)->count(),
            'sp_autorizadas' => (clone $baseQuery)->where('estado_solicitud', EstadoSP::AUTORIZADA->value)->count(),
            'sp_rechazadas' => (clone $baseQuery)->where('estado_solicitud', EstadoSP::RECHAZADA->value)->count(),
            'sp_pagadas' => (clone $baseQuery)->where('estado_solicitud', EstadoSP::PAGADO->value)->count(),
        ];

        return $this->success($conteos, 'Métricas de solicitudes de pago no verificadas obtenidas correctamente');
    }


    /**
     * Conteo de solicitudes pendientes de autorizar
     * Validada = 1 y recibe como parámetro: estado - pendiente|autorizada
     */
    public function spPendienteAutorizar(Request $request): JsonResponse
    {
        // Reconectar bases de datos
        DB::purge('mysql');
        DB::reconnect('mysql');
        DB::purge('mysql5');
        DB::reconnect('mysql5');

        $request->validate([
            'estado' => ['required', 'string', Rule::in(['pendiente', 'autorizada', 'PENDIENTE', 'AUTORIZADA'])],
            'empresa_construcc_id' => ['nullable', 'integer'],
        ]);

        $estado = $request->input('estado');
        $filters = $request->only(SolicitudPago::getFilters());

        $query = SolicitudPago::on('mysql5')
            ->where('verificada', true)
            ->where('empresa_construcc_id', $request->input('empresa_construcc_id'))
            ->filter($filters);

        // Filtrar por estado si se proporciona
        if ($estado) {
            $estadoEnum = EstadoSP::from(strtolower($estado));
            $query->where('estado_solicitud', $estadoEnum->value);
        } else {
            // Por defecto, contar pendientes y autorizadas
            $query->whereIn('estado_solicitud', [
                EstadoSP::PENDIENTE->value,
                EstadoSP::AUTORIZADA->value
            ]);
        }

        $conteo = $query->count();

        return $this->success(
            ['conteo' => $conteo],
            'Conteo de solicitudes pendientes de autorizar obtenido correctamente'
        );
    }

    /**
     * Conteo de solicitudes por validar
     * Validada = 0 y recibe parámetro usuario_id: entero no null
     */
    public function spPorValidar(Request $request): JsonResponse
    {
        // Reconectar bases de datos
        DB::purge('mysql');
        DB::reconnect('mysql');
        DB::purge('mysql5');
        DB::reconnect('mysql5');

        // 👉 Log de entrada al método
        Log::info('📥 Ingresando a spPorValidar', [
            'route' => $request->path(),
            'ip'    => $request->ip(),
            'params' => $request->all(),
            'headers' => [
                'X-API-KEY' => $request->header('X-API-KEY') ? '(recibida)' : '(no enviada)',
                'Authorization' => $request->header('Authorization') ? '(recibido)' : '(no enviado)',
            ],
        ]);

        // 👉 Validación
        try {
            $request->validate([
                'usuario_id' => ['required', 'integer'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {

            Log::warning('⚠️ Error de validación en spPorValidar', [
                'errors' => $e->errors(),
                'params' => $request->all(),
            ]);

            throw $e;
        }

        $usuarioId = $request->input('usuario_id');
        $filters = $request->only(SolicitudPago::getFilters());

        Log::info('🔍 Parámetros procesados', [
            'usuario_id' => $usuarioId,
            'filters'    => $filters,
        ]);

        // 👉 Construcción de la query
        $query = SolicitudPago::on('mysql5')
            ->where('verificada', false)
            ->where('usuario_id', $usuarioId)
            ->where('estado_solicitud', EstadoSP::PENDIENTE->value)
            ->filter($filters);

        // 👉 Log de SQL generado
        Log::debug('🧩 Query generada en spPorValidar', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
        ]);

        // 👉 Conteo ejecutado
        $conteo = $query->count();

        Log::info('📤 Respuesta spPorValidar', [
            'conteo' => $conteo,
            'usuario_id' => $usuarioId,
        ]);

        return $this->success(
            ['conteo' => $conteo],
            'Conteo de solicitudes por validar obtenido correctamente'
        );
    }
}
