<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use App\Enums\EstadoSP;

use App\Http\Requests\Construcc\ConstruccPagosSPPRegistrarPagoRequest;
use App\Http\Resources\Construcc\ConstruccPagoEnSppResource;
use App\Http\Resources\Construcc\ConstruccPagoIndexResource;
use App\Http\Resources\Construcc\ConstruccPagoProveedorResource;
use App\Http\Resources\Construcc\ConstruccPagoResource;
use App\Http\Resources\Construcc\ConstruccPagoSPPResource;
use App\Models\CuentaBancaria;
use App\Models\EmpresaConstrucc;
use App\Models\PagoSPP;
use App\Models\Proveedor;
use App\Models\SolicitudPago;
use Carbon\Carbon;

use function Laravel\Prompts\info;

/**
 * Controlador para gestionar los pagos de solicitudes de pago (SPP).
 * Maneja la relación muchos a muchos entre pagos y solicitudes de pago.
 */
class ConstruccPagosSPPController extends Controller
{


    /**
     * Lista de pagos con filtros y paginación.
     * 
     * @param Request $request
     * @return JsonResponse
     */

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(PagoSPP::getFilters());
            $sortBy  = $request->input('sort_by', 'fecha_pago');
            $order   = $request->input('order', 'desc');
            $perPage = $request->input('per_page', 10000);

            $query = PagoSPP::query()
                ->with([
                    'proveedor',
                    'empresaConstrucc',
                    // Opcional: si quieres ver las solicitudes relacionadas en el index
                    'solicitudesPago',
                ])
                ->withCount('solicitudesPago') // 👈 agrega el conteo
                ->filter($filters)
                ->orderBy($sortBy, $order);

            $paginator = $query->paginate($perPage);

            return $this->paginated(
                $paginator->setCollection(
                    ConstruccPagoIndexResource::collection($paginator)->collection
                ),
                'Pagos SPP obtenidos exitosamente.'
            );
        } catch (\Throwable $e) {
            Log::error('Error al listar pagos SPP', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'No se pudieron obtener los pagos SPP. Por favor, intente nuevamente.',
                null,
                500
            );
        }
    }

    /**
     * Mostrar un pago
     */
    public function show(PagoSPP $pago): JsonResponse
    {
        $pago->load([
            'empresaConstrucc',
            'proveedor',
            'solicitudesPago', // esta relación ya tiene pivot + orden definido
        ]);

        return $this->success([
            'pago' => new ConstruccPagoResource($pago),
        ], 'Pago obtenido correctamente.');
        // return response()->json([
        //     'success' => true,
        //     'message' => 'Pago obtenido correctamente',
        //     'data' => new ConstruccPagoResource($pago),
        // ]);
    }

    /**
     * Registrar un nuevo pago SPP
     */
    // public function store(ConstruccPagosSPPRegistrarPagoRequest $request): JsonResponse
    // {
    //     $validated = $request->validated();

    //     // 1. Validar suma de montos contra monto_total
    //     $montoTotalAplicado = (float) collect($validated['solicitudes'])->sum(fn($s) => (float) $s['monto_pago']);
    //     $montoTotal = (float) $validated['monto_total'];

    //     if ($montoTotalAplicado > $montoTotal) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'El monto total aplicado a las solicitudes excede el monto del pago.',
    //             'errors' => [
    //                 'monto_total' => $montoTotal,
    //                 'monto_aplicado' => $montoTotalAplicado,
    //             ],
    //         ], 422);
    //     }

    //     $pago = DB::transaction(function () use ($validated) {
    //         $pago = PagoSPP::create([
    //             'fecha_pago' => $validated['fecha_pago'],
    //             'fecha_registro' => now(),
    //             'referencia_pago' => $validated['referencia_pago'] ?? null,
    //             'banco_pago' => $validated['banco_pago'] ?? null,
    //             'cuenta_origen' => $validated['cuenta_origen'] ?? null,
    //             'tipo_cuenta_origen' => $validated['tipo_cuenta_origen'] ?? null,
    //             'banco_destino' => $validated['banco_destino'] ?? null,
    //             'cuenta_destino' => $validated['cuenta_destino'] ?? null,
    //             'tipo_cuenta_destino' => $validated['tipo_cuenta_destino'] ?? null,
    //             'clabe_interbancaria_destino' => $validated['clabe_interbancaria_destino'] ?? null,
    //             'titular_cuenta_destino' => $validated['titular_cuenta_destino'] ?? null,
    //             'monto_total' => (float) $validated['monto_total'],
    //             'observaciones' => $validated['observaciones'] ?? null,
    //             'usuario_registro_id' => auth()->id(),
    //             'usuario_registro_nombre' => auth()->user()->name ?? null,
    //             'empresa_construcc_id' => $validated['empresa_construcc_id'],
    //             'proveedor_id' => $validated['proveedor_id'],
    //         ]);

    //         foreach ($validated['solicitudes'] as $item) {
    //             $pago->solicitudesPago()->attach($item['solicitud_pago_id'], [
    //                 'monto_aplicado' => (float) $item['monto_pago'],
    //                 'estado_pago' => $item['estado_pago'] ?? 'aplicado',
    //                 'notas' => $item['notas'] ?? null,
    //                 'fecha_aplicacion' => now(),
    //             ]);
    //         }

    //         return $pago;
    //     });

    //     $pago->load(['empresaConstrucc', 'proveedor', 'solicitudesPago']);

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Pago registrado correctamente',
    //         'data' => new ConstruccPagoResource($pago),
    //     ], 201);
    // }

    /**
     * Actualizar pago
     */
    public function update(ConstruccPagosSPPRegistrarPagoRequest $request, PagoSPP $pago): JsonResponse
    {
        $validated = $request->validated();

        $pago->update($validated);

        $pago->load(['empresaConstrucc', 'proveedor', 'solicitudesPago']);

        return response()->json([
            'success' => true,
            'message' => 'Pago actualizado correctamente',
            'data' => new ConstruccPagoResource($pago),
        ]);
    }

    /**
     * Eliminar pago
     */
    public function destroy(PagoSPP $pago): JsonResponse
    {
        $pago->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pago eliminado correctamente',
        ]);
    }

    /**
     * Agregar una solicitud de pago al pago
     */
    public function agregarSolicitudPago(Request $request, PagoSPP $pago): JsonResponse
    {
        $data = $request->validate([
            'solicitud_pago_id' => ['required', 'exists:solicitudes_pago,id'],
            'monto_aplicado' => ['required', 'numeric', 'min:0.01'],
            'estado_pago' => ['nullable', 'string'],
            'notas' => ['nullable', 'string'],
        ]);

        $pago->solicitudesPago()->attach($data['solicitud_pago_id'], [
            'monto_aplicado' => (float) $data['monto_aplicado'],
            'estado_pago' => $data['estado_pago'] ?? 'aplicado',
            'notas' => $data['notas'] ?? null,
            'fecha_aplicacion' => now(),
        ]);

        $pago->load(['empresaConstrucc', 'proveedor', 'solicitudesPago']);

        return response()->json([
            'success' => true,
            'message' => 'Solicitud de pago agregada al pago.',
            'data' => new ConstruccPagoResource($pago),
        ]);
    }

    /**
     * Actualizar datos del pivot
     */
    public function actualizarSolicitudPago(Request $request, PagoSPP $pago, SolicitudPago $solicitudPago): JsonResponse
    {
        $data = $request->validate([
            'monto_aplicado' => ['required', 'numeric', 'min:0.01'],
            'estado_pago' => ['nullable', 'string'],
            'notas' => ['nullable', 'string'],
        ]);

        $pago->solicitudesPago()->updateExistingPivot($solicitudPago->id, [
            'monto_aplicado' => (float) $data['monto_aplicado'],
            'estado_pago' => $data['estado_pago'] ?? 'aplicado',
            'notas' => $data['notas'] ?? null,
        ]);

        $pago->load(['empresaConstrucc', 'proveedor', 'solicitudesPago']);

        return response()->json([
            'success' => true,
            'message' => 'Solicitud de pago actualizada.',
            'data' => new ConstruccPagoResource($pago),
        ]);
    }

    /**
     * Eliminar relación pago - solicitud de pago
     */
    public function eliminarSolicitudPago(PagoSPP $pago, SolicitudPago $solicitudPago): JsonResponse
    {
        $pago->solicitudesPago()->detach($solicitudPago->id);

        $pago->load(['empresaConstrucc', 'proveedor', 'solicitudesPago']);

        return response()->json([
            'success' => true,
            'message' => 'Solicitud de pago eliminada del pago.',
            'data' => new ConstruccPagoResource($pago),
        ]);
    }

    /**
     * Estadísticas de pagos
     */
    public function estadisticas(): JsonResponse
    {
        $totalPagos = PagoSPP::sum('monto_total');
        $conteo = PagoSPP::count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_pagado' => (float) $totalPagos,
                'total_pagos' => $conteo,
            ],
        ]);
    }

    /**
     * listado de proveedores con informacion de las SPP activas.
     * El listado se realiza en base a las SPP autorizadas y se agrupan por proveedor.
     * 
     *  GET /api/construcc/pagos-spp/proveedores?empresas_construcc={id_empresa_construcc}
     * 
     * @param Request $request
     * @return ConstruccPagoProveedorResource[]
     */
    public function indexProveedor(Request $request): JsonResponse
    {
        try {
            $empresaConstruccId = $request->integer('empresa_construcc_id');
            $perPage = $request->input('per_page', 1000);

            $query = SolicitudPago::query()
                ->selectRaw('proveedor_id, COUNT(*) as spp_autorizadas_count')
                ->where('estado_solicitud', EstadoSP::AUTORIZADA)
                ->when($empresaConstruccId, function ($q) use ($empresaConstruccId) {
                    $q->where('empresa_construcc_id', $empresaConstruccId);
                })
                ->groupBy('proveedor_id')
                ->with([
                    'proveedor:id,nombre_comercial',
                    'proveedor.cuentasBancarias',
                ]);

            $paginator = $query->paginate($perPage);

            // 🔥 Adaptamos la colección para que el resource reciba el proveedor
            $paginator->getCollection()->transform(function ($row) {
                /** @var Proveedor */
                $proveedor = $row->proveedor;
                $proveedor->spp_autorizadas_count = $row->spp_autorizadas_count;
                return $proveedor;
            });


            $data = ConstruccPagoProveedorResource::collection($paginator)->resolve();

            return $this->paginated(
                $paginator->setCollection(collect($data))
            );
        } catch (\Exception $e) {
            Log::error('Error al listar proveedores con SPP autorizadas', [
                'error' => $e->getMessage(),
            ]);

            return $this->error(
                'No se pudieron obtener los proveedores.',
                null,
                500
            );
        }
    }

    /**
     * Lista todas las SPP de un proveedor específico con filtros y paginación.
     *
     * GET /api/construcc/pagos-spp/proveedor/{proveedor}/spp?empresas_construcc={id_empresa_construcc}
     */
    public function sppPorProveedor(Request $request, int $proveedorId): JsonResponse
    {
        try {
            $proveedor = Proveedor::findOrFail($proveedorId);

            $empresaConstruccId = $request->integer('empresa_construcc_id');
            $perPage = $request->input('per_page', 1000);

            $query = SolicitudPago::query()
                ->where('proveedor_id', $proveedor->id)
                ->where('estado_solicitud', EstadoSP::AUTORIZADA)
                ->when($empresaConstruccId, function ($q) use ($empresaConstruccId) {
                    $q->where('empresa_construcc_id', $empresaConstruccId);
                })
                ->withSum('pagos as total_pagado', 'pago_solicitud_pago.monto_aplicado')
                ->with(array_merge(
                    SolicitudPago::eagerLodable(),
                    [
                        'pagos' => function ($q) {
                            $q->orderBy('pago_solicitud_pago.fecha_aplicacion', 'desc');
                        }
                    ]
                ));

            $paginator = $query->paginate($perPage);

            return $this->paginated(
                $paginator->setCollection(ConstruccPagoSPPResource::collection($paginator)->collection),
                'Solicitudes de pago obtenidas exitosamente.'
            );
        } catch (\Exception $e) {
            Log::error('Error al listar SPP del proveedor', [
                'proveedor_id' => $proveedorId,
                'error'        => $e->getMessage(),
            ]);

            return $this->error(
                'No se pudieron obtener las solicitudes de pago.',
                null,
                500
            );
        }
    }

    /**
     * Muestra una SPP específica de un proveedor con todos sus pagos parciales.
     * 
     * GET /api/construcc/pagos-spp/proveedor/{proveedor}/spp/{spp}
     */
    public function showSppProveedor(Request $request, Proveedor $proveedor, SolicitudPago $spp): JsonResponse
    {
        try {
            // 🔐 Validación de pertenencia
            if ($spp->proveedor_id !== $proveedor->id) {
                return $this->error(
                    'La solicitud de pago no pertenece a este proveedor.',
                    null,
                    403
                );
            }

            // 📦 Cargar relaciones necesarias para el resource
            $spp->load([
                'proveedor',
                'empresaConstrucc',
                'cuentasBancarias',
                'pagos' => function ($query) {
                    $query->with([
                        'empresaConstrucc',
                        'proveedor',
                    ])
                        ->withPivot([
                            'solicitud_pago_id',
                            'monto_aplicado',
                            'fecha_aplicacion',
                        ])
                        ->orderByPivot('fecha_aplicacion', 'desc');
                },
            ]);

            // ✅ Respuesta con resource específico de SPP (que ya incluye pagos)
            return $this->success(
                [
                    'solicitud_pago' => new ConstruccPagoSPPResource($spp),
                    'pagos' => ConstruccPagoResource::collection($spp->pagos),
                ],
                'Solicitud de pago obtenida exitosamente.'
            );
        } catch (\Throwable $e) {
            Log::error('Error al obtener SPP del proveedor', [
                'proveedor_id' => $proveedor->id,
                'spp_id'       => $spp->id,
                'error'        => $e->getMessage(),
            ]);

            return $this->error(
                'No se pudo obtener el detalle de la solicitud de pago. Por favor, intente nuevamente.',
                null,
                500
            );
        }
    }


    /**
     * Lista todos los pagos parciales de una SPP específica.
     * 
     * GET /api/construcc/pagos-spp/proveedor/{proveedor}/spp/{spp}/pagos
     * 
     * @param Proveedor $proveedor
     * @param SolicitudPago $spp
     * @return JsonResponse
     */
    public function pagosDeSpp(Proveedor $proveedor, SolicitudPago $spp): JsonResponse
    {
        try {
            // Verificar que la SPP pertenece al proveedor
            if ($spp->proveedor_id !== $proveedor->id) {
                return $this->error(
                    'La solicitud de pago no pertenece a este proveedor.',
                    null,
                    403
                );
            }

            $pagos = $spp->pagos()
                ->with([
                    'empresaConstrucc',
                    'proveedor', // 👈 NECESARIO para armar la URL
                ])
                ->withPivot([
                    'solicitud_pago_id', // 👈 NECESARIO para el parámetro {spp}
                    'monto_aplicado',
                    'estado_pago',
                    'notas',
                    'fecha_aplicacion'
                ])
                ->orderBy('pago_solicitud_pago.fecha_aplicacion', 'desc')
                ->get();

            return $this->success([
                'solicitud_pago' => ConstruccPagoSPPResource::make($spp),
                'pagos' => ConstruccPagoResource::collection($pagos),
            ], 'Pagos obtenidos exitosamente.');
        } catch (\Throwable $e) {
            Log::error('Error al listar pagos de SPP', [
                'proveedor_id' => $proveedor->id,
                'spp_id' => $spp->id,
                ]);
                'error' => $e->getMessage(),

            return $this->error(
                'No se pudieron obtener los pagos. Por favor, intente nuevamente.',
                null,
                500
            );
        }
    }

    /**
     * Lista todas las SPP asociadas a un pago específico de un proveedor.
     * 
     * GET /api/construcc/pagos-spp/proveedor/{proveedor}/pagos/{pago}/spps
     * 
     * @param Proveedor $proveedor
     * @param ConstruccPago $pago
     * @return JsonResponse
     */
    public function sppDePago(Proveedor $proveedor, PagoSPP $pago): JsonResponse
    {
        try {
            // Obtener solo las SPP del proveedor
            $spps = $pago->solicitudesPago()
                ->where('proveedor_id', $proveedor->id)
                ->with(['empresaConstrucc', 'proveedor'])
                ->withPivot([
                    'monto_aplicado',
                    'estado_pago',
                    'notas',
                    'fecha_aplicacion'
                ])
                ->orderBy('pago_solicitud_pago.fecha_aplicacion', 'desc')
                ->get();

            return $this->success([
                'pago' => ConstruccPagoResource::make($pago),
                'solicitudes_pago' => ConstruccPagoSPPResource::collection($spps),
            ], 'Solicitudes de pago obtenidas exitosamente.');
        } catch (\Throwable $e) {
            Log::error('Error al listar SPP de un pago', [
                'proveedor_id' => $proveedor->id,
                'pago_id' => $pago->id,
                'error' => $e->getMessage(),
            ]);

            return $this->error(
                'No se pudieron obtener las solicitudes de pago del pago.',
                null,
                500
            );
        }
    }


    /**
     * Muestra un pago específico de una SPP.
     * 
     * GET /api/construcc/pagos-spp/proveedor/{proveedor}/spp/{spp}/pagos/{pago}
     * 
     * @param Proveedor $proveedor
     * @param SolicitudPago $spp
     * @param PagoSPP $pago
     * @return JsonResponse
     */
    // public function showPagoDeSpp(Proveedor $proveedor, SolicitudPago $spp, PagoSPP $pago): JsonResponse
    // {
    //     try {
    //         // Verificar que la SPP pertenece al proveedor
    //         if ($spp->proveedor_id !== $proveedor->id) {
    //             return $this->error(
    //                 'La solicitud de pago no pertenece a este proveedor.',
    //                 null,
    //                 403
    //             );
    //         }

    //         // Verificar que el pago está asociado a esta SPP
    //         $pagoAsociado = $pago->solicitudesPago()
    //             ->where('solicitud_pago_id', $spp->id)
    //             ->exists();

    //         if (!$pagoAsociado) {
    //             return $this->error(
    //                 'El pago no está asociado a esta solicitud de pago.',
    //                 null,
    //                 404
    //             );
    //         }

    //         $pago->load(['empresaConstrucc']);

    //         // Obtener datos del pivot
    //         $pivotData = DB::connection('mysql5')
    //             ->table('pago_solicitud_pago')
    //             ->where('pago_spp_id', $pago->id)
    //             ->where('solicitud_pago_id', $spp->id)
    //             ->first();

    //         return $this->success([
    //             'pago' => $pago,
    //             'relacion' => [
    //                 'monto_aplicado' => (float) $pivotData->monto_aplicado,
    //                 'estado_pago' => $pivotData->estado_pago,
    //                 'notas' => $pivotData->notas,
    //                 'fecha_aplicacion' => $pivotData->fecha_aplicacion,
    //             ],
    //             'solicitud_pago' => [
    //                 'id' => $spp->id,
    //                 'numero_folio_solicitud' => $spp->numero_folio_solicitud,
    //                 'monto_total' => (float) $spp->monto_total,
    //                 'monto_abonado' => (float) $spp->monto_abonado,
    //                 'saldo_pendiente' => (float) $spp->saldo_pendiente,
    //             ],
    //         ], 'Detalle del pago obtenido exitosamente.');
    //     } catch (\Exception $e) {
    //         Log::error('Error al obtener pago de SPP', [
    //             'proveedor_id' => $proveedor->id,
    //             'spp_id' => $spp->id,
    //             'pago_id' => $pago->id,
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString(),
    //         ]);

    //         return $this->error(
    //             'No se pudo obtener el detalle del pago. Por favor, intente nuevamente.',
    //             null,
    //             500
    //         );
    //     }
    // }

    /**
     * Sube o actualiza el comprobante de pago para un pago específico.
     * 
     * POST /api/construcc/pagos-spp/proveedor/{proveedor}/spp/{spp}/pagos/{pago}/subir-comprobante
     * 
     * @param Request $request
     * @param Proveedor $proveedor
     * @param SolicitudPago $spp
     * @param PagoSPP $pago
     * @return JsonResponse
     */
    public function subirComprobanteSpp(Request $request, Proveedor $proveedor, SolicitudPago $spp, PagoSPP $pago): JsonResponse
    {
        try {
            // Verificar que la SPP pertenece al proveedor
            if ($spp->proveedor_id !== $proveedor->id) {
                return $this->error(
                    'La solicitud de pago no pertenece a este proveedor.',
                    null,
                    403
                );
            }

            // Verificar que el pago está asociado a esta SPP
            $pagoAsociado = $pago->solicitudesPago()
                ->where('solicitud_pago_id', $spp->id)
                ->exists();

            if (!$pagoAsociado) {
                return $this->error(
                    'El pago no está asociado a esta solicitud de pago.',
                    null,
                    404
                );
            }

            // Validación
            $validated = $request->validate([
                'comprobante_pago' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            ], [
                'comprobante_pago.required' => 'El comprobante de pago es obligatorio.',
                'comprobante_pago.file' => 'El comprobante debe ser un archivo válido.',
                'comprobante_pago.mimes' => 'El comprobante debe ser PDF, JPG o PNG.',
                'comprobante_pago.max' => 'El comprobante no debe superar los 10MB.',
            ]);

            DB::beginTransaction();

            // Eliminar el comprobante anterior si existe
            if ($pago->comprobante_pago && Storage::disk('public')->exists($pago->comprobante_pago)) {
                Storage::disk('public')->delete($pago->comprobante_pago);
            }

            // Guardar el nuevo comprobante
            $comprobantePath = $request->file('comprobante_pago')->store(
                'comprobantes_pago',
                'public'
            );

            $pago->update([
                'comprobante_pago' => $comprobantePath,
            ]);

            DB::commit();

            return $this->success([
                'comprobante_pago' => $comprobantePath,
                'comprobante_pago_url' => asset('storage/' . $comprobantePath),
            ], 'Comprobante de pago guardado exitosamente.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return $this->error(
                'Error de validación en el archivo.',
                $e->errors(),
                422
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al subir comprobante de pago', [
                'proveedor_id' => $proveedor->id,
                'spp_id' => $spp->id,
                'pago_id' => $pago->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error('No se pudo guardar el comprobante. Por favor, intente nuevamente.', null, 500);
        }
    }

    /**
     * Registra un pago y lo aplica a una o varias SPP de un proveedor.
     * Este endpoint permite crear un pago con comprobante y asociarlo a múltiples SPP.
     * 
     * POST /api/construcc/pagos-spp/proveedor/{proveedor}/pagos?empresas_construcc={id_empresa_construcc}
     * 
     * @param Request $request
     * @param Proveedor $proveedor
     * @return JsonResponse
     */
    public function registrarPagoProveedor(ConstruccPagosSPPRegistrarPagoRequest $request, Proveedor $proveedor): JsonResponse
    {
        $validated = $request->validated();
        $empresaConstruccId = $validated['empresa_id'];

        /************************************************************
         * Validar que las SPP pertenecen al proveedor y a la empresa
         ************************************************************/
        $listSPPIds = collect($validated['solicitudes'])->pluck('solicitud_id'); // --> [{solicitud_pago_id, ...}]

        // This method genera: [ {id}, ...] con las SPP que no pertenecen al Proveedor ni a la empresa
        $invalidSPPs = SolicitudPago::whereIn('id', $listSPPIds)
            ->where(function ($q) use ($proveedor, $empresaConstruccId) {
                $q->where('proveedor_id', '!=', $proveedor->id)
                    ->orWhere('empresa_construcc_id', '!=', $empresaConstruccId)
                    ->orWhere('estado_solicitud', '!=', EstadoSP::AUTORIZADA);
            })
            ->pluck('id');

        // TODO: Generar excepción personalizada para este caso quie retrone una respuesta estandar Api Rest Full
        if ($invalidSPPs->isNotEmpty()) {
            return $this->error(
                'Una o más solicitudes de pago no pertenecen al proveedor o a la empresa indicada.',
                [
                    'solicitudes_invalidas' => $invalidSPPs,
                ],
                422
            );
        }

        /************************************************************
         * Validar montos
         ************************************************************/
        /**
         * existen varios montos 
         *  - info_comprobante.monto: monto extraído del comprobante (OCR)
         *  - solicitudes.*.monto_pago: Es el monto abonado a cada SPP
         * 
         *  1. Se debe validar que la suma de los montos aplicados a las SPP no exceda el monto_total del pago.
         *  2. El monto_total debe ser igual al monto extraído del comprobante (si se proporcionó).
         */

        // 1. Validar que la suma de los montos aplicados a las SPP no exceda el monto_total
        $montoTotalPago = (float) $validated['info_comprobante']['monto'];
        $sumaMontoSPPs = (float) collect($validated['solicitudes'])->sum(function ($item) {
            return (float) $item['monto_pago'];
        });


        // if (round($sumaMontoSPPs, 2) > round($montoTotalPago, 2)) {
        //     return $this->error(
        //         'El monto total aplicado a las solicitudes excede el monto del pago registrado.',
        //         ['monto_total' => $montoTotalPago, 'monto_aplicado' => $sumaMontoSPPs,],
        //         422
        //     );
        // }

        // if (round($sumaMontoSPPs, 2) !== round($montoTotalPago, 2)) {
        //     return $this->error(
        //         'El monto total aplicado a las solicitudes no coincide con el monto del pago registrado.',
        //         ['monto_total' => $montoTotalPago, 'monto_aplicado' => $sumaMontoSPPs,],
        //         422
        //     );
        // }

        try {
            DB::beginTransaction();

            /************************************************************
             * Guardar comprobante
             ************************************************************/
            $file = $request->file('comprobante_pago');
            $comprobantePath = $file->store('comprobantes', 'private');

            /************************************************************
             * Crear el pago
             ************************************************************/
            $infoComprobante = $validated['info_comprobante'] ?? [];

            $folio_consecutivo_construcc = null;
            if ($empresaConstruccId) {
                $empresaConstrucc = EmpresaConstrucc::find($empresaConstruccId);

                if ($empresaConstrucc) {
                    $folio_consecutivo_construcc = $empresaConstrucc->obtenerFolioSiguientePagoSPP();
                }
            }


            $cuentaBancaria = CuentaBancaria::findOrFail($validated['cuenta_destino_id']);
            $campo = preg_replace('/\D+/', '', (string) $cuentaBancaria->campo_dependiente); // solo dígitos
            $ultimos4 = substr($campo, -4);

            $pago = PagoSPP::create([
                // Comprobante File
                'comprobante_pago' => $comprobantePath,

                // Datos de la cuenta de origen
                'cuenta_bancaria_empresa_construcc_id' => $validated['cuenta_bancaria_empresa_construcc_id'] ?? null,
                'cuenta_destino_id' => $validated['cuenta_destino_id'] ?? null,
                'cuenta_destino_terminacion' => $ultimos4,

                // Informacion basica del pago
                'empresa_construcc_id' => $validated['empresa_id'], // ← mapeo explícito
                'folio_pago_spp_consecutivo' => $folio_consecutivo_construcc,
                'proveedor_id' => $validated['proveedor_id'],
                'usuario_registro_id' => $validated['usuario_id'],
                'usuario_registro_nombre' => $validated['usuario_nombre'],


                // Informacion del comprobante de pago OCR
                'monto_total'      => $validated['monto_total'],
                'fecha_pago' => Carbon::parse(trim($validated['info_comprobante']['fecha']) . ' ' . trim($validated['info_comprobante']['hora']))->format('Y-m-d H:i:s'),
                'referencia_pago'  => $validated['info_comprobante']['referencia'] ?? null,
                'banco_destino'    => $infoComprobante['bancoDestino'] ?? null,
                'titular_cuenta_destino' => $infoComprobante['nombreBeneficiario'] ?? null,
                'clave_rastreo'    => $infoComprobante['claveRastreo'] ?? null,

                'fecha_registro'   => now(),
            ]);

            /************************************************************
             * Aplicar el pago a las SPP
             ************************************************************/

            foreach ($validated['solicitudes'] as $solicitudData) {
                /** @var SolicitudPago */
                $solicitudPago = SolicitudPago::where('id', $solicitudData['solicitud_id'])
                    ->where('proveedor_id', $proveedor->id)
                    ->where('empresa_construcc_id', $empresaConstruccId)
                    ->firstOrFail();

                $pago->solicitudesPago()->attach($solicitudPago->id, [
                    'monto_aplicado'   => $solicitudData['monto_pago'],
                    'fecha_aplicacion' => now(),
                ]);

                $solicitudPago->actualizarSaldos($solicitudData['monto_pago']);
            }

            DB::commit();

            $pago->load([
                'empresaConstrucc',
                'proveedor',
                'solicitudesPago' => function ($query) {
                    $query->withPivot([
                        'solicitud_pago_id',
                        'monto_aplicado',
                        'estado_pago',
                        'notas',
                        'fecha_aplicacion'
                    ]);
                }
            ]);


            // return $this->success($pago, 'Pago registrado y aplicado exitosamente.', 201);
            return $this->success(
                new ConstruccPagoResource($pago),
                'Pago registrado y aplicado exitosamente.',
                201
            );
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Error al registrar pago del proveedor', [
                'proveedor_id' => $proveedor->id,
                'error' => $e->getMessage(),
            ]);

            return $this->error(
                'No se pudo registrar el pago.',
                null,
                500
            );
        }
    }


    /**
     * Descargar comprobante de pago
     */
    public function descargarComprobantePago(Request $request, PagoSPP $pago)
    {
        // Verificar que el pago tiene comprobante
        if (! $pago->comprobante_pago || ! Storage::disk('private')->exists($pago->comprobante_pago)) {
            return $this->error('Comprobante de pago no disponible.', null, 404);
        }
        return response()->download(
            Storage::disk('private')->path($pago->comprobante_pago)
        );
    }

    public function cuentasPorProveedor(Proveedor $proveedor): JsonResponse
    {
        $cuentas = $proveedor->cuentasBancarias()->get();

        return $this->success([
            'proveedor' => [
                'id' => $proveedor->id,
                'nombre_comercial' => $proveedor->nombre_comercial,
            ],
            'cuentas_bancarias' => $cuentas,
        ], 'Cuentas bancarias del proveedor obtenidas exitosamente.');
    }
}
