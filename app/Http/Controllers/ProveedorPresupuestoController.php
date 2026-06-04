<?php

namespace App\Http\Controllers;

use App\Http\Requests\Presupuesto\StorePresupuestoRequest;
use App\Http\Requests\Presupuesto\UpdatePresupuestoRequest;
use App\Http\Resources\Presupuesto\PresupuestoResource;
use App\Http\Resources\ProveedorResource;
use App\Services\Presupuesto\PresupuestoThemeService;
use App\Support\PresupuestoPdf;
use App\Support\PresupuestoPdfTemplate;
use App\Models\CarteraCliente;
use App\Models\Presupuesto;
use App\Models\PresupuestoConcepto;
use App\Models\Proveedor;
use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Mail\PresupuestoEnviadoMail;
use App\Models\User;
use App\Notifications\Presupuesto\PresupuestoEnviadoNotification;
use App\Notifications\Presupuesto\PresupuestoRecibidoClienteProveedorNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Controlador de administración para el módulo Presupuesto Básico.
 */
class ProveedorPresupuestoController extends Controller
{
    private bool $logEnabled = true;

    public function __construct(
        private readonly PresupuestoThemeService $presupuestoThemeService,
    ) {}

    /**
     * Catálogo de temas visuales para PDF / vista previa de presupuestos.
     */
    public function listPdfThemes(Request $request, Proveedor $proveedor): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->tieneAccesoAProveedor((int) $proveedor->id)) {
            return $this->error('El usuario autenticado no tiene acceso a la empresa en GestionPlus.', null, 403);
        }

        return $this->success([
            'themes' => $this->presupuestoThemeService->getThemes(),
            'default_theme' => $this->presupuestoThemeService->getDefaultThemeKey(),
        ], 'Temas de presupuesto obtenidos correctamente.');
    }

    /**
     * Obtiene el siguiente folio de presupuesto para el proveedor autenticado.
     * Este endpoint no consume el consecutivo, solo lo consulta para previsualización.
     */
    public function nextFolio(Request $request): JsonResponse
    {
        $user = $request->user();
        $proveedor = $user?->proveedorPrincipal();

        if (! $user || ! $proveedor) {
            return $this->error('No fue posible resolver la empresa en GestionPlus.', null, 422);
            // return $this->error('No fue posible resolver la empresa del usuario autenticado en GestionPlus.', null, 422);
        }

        if (! $user->tieneAccesoAProveedor((int) $proveedor->id)) {
            return $this->error('El usuario autenticado no tiene acceso a la empresa en GestionPlus.', null, 403);
        }

        return $this->success([
            'folio' => $this->formatearFolioSiguiente($proveedor),
            'proveedor_id' => (int) $proveedor->id,
        ]);
    }

    /**
     * Obtiene el siguiente folio de presupuesto para un proveedor específico.
     * No incrementa el consecutivo en base de datos.
     */
    public function nextFolioByProveedor(Request $request, Proveedor $proveedor): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->tieneAccesoAProveedor((int) $proveedor->id)) {
            return $this->error('El usuario autenticado no tiene acceso a la empresa en GestionPlus.', null, 403);
        }

        return $this->success([
            'folio' => $this->formatearFolioSiguiente($proveedor),
            'proveedor_id' => (int) $proveedor->id,
        ]);
    }

    /**
     * Lista proveedores registrados con filtros (search) para uso en selector de clientes de catálogo.
     */
    public function proveedoresRegistrados(Request $request, Proveedor $proveedor): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->tieneAccesoAProveedor((int) $proveedor->id)) {
            return $this->error('El usuario autenticado no tiene acceso a la empresa en GestionPlus.', null, 403);
        }

        $filters = $request->only(Proveedor::getFilters());
//        $perPage = min((int) $request->input('per_page', 50), 100);

        $proveedores = Proveedor::with(Proveedor::eagerLodable())
            ->where('id', '!=', $proveedor->id)
            ->filter($filters)
            ->orderBy('nombre_comercial', 'asc')
            ->get();

        return $this->success(ProveedorResource::collection($proveedores));
    }

    public function index(Request $request, Proveedor $proveedor): JsonResponse
    {
        Presupuesto::actualizarVencidos();

        $filters = $request->only(Presupuesto::getFilters());
        $listado = $request->input('listado', 'enviados');
        if (! in_array($listado, ['enviados', 'recibidos'], true)) {
            $listado = 'enviados';
        }

        if ($listado === 'recibidos') {
            unset($filters['proveedor_id']);
            $filters['proveedor_receptor_id'] = $proveedor->id;
        } else {
            $filters['proveedor_id'] = $proveedor->id;
            unset($filters['proveedor_receptor_id']);
        }

        // Si hay segmento observados/rechazados, usar ese filtro y no estado
        $segmento = $filters['segmento'] ?? null;
        if (in_array($segmento, ['observados', 'rechazados'], true)) {
            unset($filters['estado']);
        } else {
            unset($filters['segmento']);
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $order = $request->input('order', 'desc');
        $perPage = $request->input('per_page', 10);

        $ultimasN = isset($filters['ultimas_presupuestos']) ? (int) $filters['ultimas_presupuestos'] : 0;
        $hasUltimas = $ultimasN > 0;

        // Pool: últimas N por fecha, sin filtrar por estado/segmento (el segmento se aplica después)
        $poolFilters = array_diff_key($filters, array_flip(['estado', 'segmento']));
        $baseQuery = $listado === 'recibidos'
            ? Presupuesto::query()->where('proveedor_receptor_id', $proveedor->id)
            : Presupuesto::query()->where('proveedor_id', $proveedor->id);
        if (! empty($poolFilters)) {
            $baseQuery->filter($poolFilters);
        }

        $segmentCountsFormatted = [
            'borrador' => (int) (clone $baseQuery)->where('estado', Presupuesto::ESTADO_BORRADOR)->count(),
            'enviados' => (int) (clone $baseQuery)->where('estado', Presupuesto::ESTADO_ENVIADO)->count(),
            'observados' => (int) (clone $baseQuery)
                ->whereIn('estado', [Presupuesto::ESTADO_RECHAZADO, Presupuesto::ESTADO_RECHAZADO_CON_OBSERVACION])
                ->whereNotNull('motivo_rechazo')
                ->whereRaw('TRIM(motivo_rechazo) != ?', [''])
                ->where(function ($q) {
                    $q->where('item_visto', false)->orWhereNull('item_visto');
                })
                ->count(),
            'rechazados' => (int) (clone $baseQuery)
                ->whereIn('estado', [Presupuesto::ESTADO_RECHAZADO, Presupuesto::ESTADO_RECHAZADO_CON_OBSERVACION, Presupuesto::ESTADO_VENCIDO])
                ->where(function ($q) {
                    $q->whereNull('motivo_rechazo')
                        ->orWhereRaw('TRIM(COALESCE(motivo_rechazo, "")) = ?', [''])
                        ->orWhere('item_visto', true);
                })
                ->count(),
            'aceptados' => (int) (clone $baseQuery)->where('estado', Presupuesto::ESTADO_ACEPTADO)->count(),
        ];

        $unreadSegmentCountsFormatted = [
            'borrador' => (int) (clone $baseQuery)
                ->where('estado', Presupuesto::ESTADO_BORRADOR)
                ->where(function ($q) {
                    $q->where('item_visto', false)->orWhereNull('item_visto');
                })
                ->count(),
            'enviados' => (int) (clone $baseQuery)
                ->where('estado', Presupuesto::ESTADO_ENVIADO)
                ->where(function ($q) {
                    $q->where('item_visto', false)->orWhereNull('item_visto');
                })
                ->count(),
            'observados' => (int) (clone $baseQuery)
                ->whereIn('estado', [Presupuesto::ESTADO_RECHAZADO, Presupuesto::ESTADO_RECHAZADO_CON_OBSERVACION])
                ->whereNotNull('motivo_rechazo')
                ->whereRaw('TRIM(motivo_rechazo) != ?', [''])
                ->where(function ($q) {
                    $q->where('item_visto', false)->orWhereNull('item_visto');
                })
                ->count(),
            'rechazados' => (int) (clone $baseQuery)
                ->whereIn('estado', [Presupuesto::ESTADO_RECHAZADO, Presupuesto::ESTADO_RECHAZADO_CON_OBSERVACION, Presupuesto::ESTADO_VENCIDO])
                ->where(function ($q) {
                    $q->whereNull('motivo_rechazo')
                        ->orWhereRaw('TRIM(COALESCE(motivo_rechazo, "")) = ?', ['']);
                })
                ->where(function ($q) {
                    $q->where('item_visto', false)->orWhereNull('item_visto');
                })
                ->count(),
            'aceptados' => (int) (clone $baseQuery)
                ->where('estado', Presupuesto::ESTADO_ACEPTADO)
                ->where(function ($q) {
                    $q->where('item_visto', false)->orWhereNull('item_visto');
                })
                ->count(),
        ];

        if ($hasUltimas) {
            $ids = (clone $baseQuery)->pluck('id');
            $listQuery = Presupuesto::query()
                ->with(Presupuesto::eagerLodable())
                ->whereIn('id', $ids);

            $statusFilters = array_intersect_key($filters, array_flip(['estado', 'segmento']));
            if (! empty($statusFilters)) {
                $listQuery->filter($statusFilters);
            }

            $originalPaginator = $listQuery->orderBy($sortBy, $order)->paginate($perPage);
        } else {
            $originalPaginator = Presupuesto::query()
                ->with(Presupuesto::eagerLodable())
                ->filter($filters)
                ->orderBy($sortBy, $order)
                ->paginate($perPage);
        }

        $data = PresupuestoResource::collection($originalPaginator)->resolve();

        return $this->paginated(
            $originalPaginator->setCollection(collect($data)),
            'Datos paginados.',
            200,
            [
                'segment_counts' => $segmentCountsFormatted,
                'unread_segment_counts' => $unreadSegmentCountsFormatted,
            ]
        );
    }

    public function store(StorePresupuestoRequest $request, Proveedor $proveedor): JsonResponse
    {
        try {
            $validated = $request->validated();
            $user = $request->user();

            Log::info('Validación de presupuesto', [
                'payload' => $validated,
            ]);

            if (! $user || ! method_exists($user, 'tieneAccesoAProveedor') || ! $user->tieneAccesoAProveedor((int) $proveedor->id)) {
                return $this->error('El usuario autenticado no tiene acceso a la empresa en GestionPlus.', null, 403);
            }

            if ((int) $validated['proveedor_id'] !== (int) $proveedor->id) {
                return $this->error('El proveedor del payload no coincide con el proveedor de la ruta.', null, 422);
            }

            $validated = $this->resolverReceptorEmpresaParaValidacion($validated, $proveedor);
            $validated = $this->normalizarTerminosPayload($validated);

            Log::info('Modificacion Validación de presupuesto', [
                'payload' => $validated,
            ]);

            if (! empty($validated['empresa_receptora_id'])) {
                $idReceptor = (int) $validated['empresa_receptora_id'];
                $esProveedorReceptor = ! empty($validated['es_proveedor_receptor']) && filter_var($validated['es_proveedor_receptor'], FILTER_VALIDATE_BOOLEAN);
                if ($esProveedorReceptor) {
                    if (! Proveedor::query()->whereKey($idReceptor)->exists()) {
                        return $this->error('El proveedor no existe.', null, 422);
                    }
                } elseif (! CarteraCliente::query()
                    ->where('proveedor_id', $proveedor->id)
                    ->whereKey($idReceptor)
                    ->exists()) {
                    return $this->error('El cliente de cartera no pertenece al proveedor indicado.', null, 422);
                }
            }

            $presupuesto = DB::transaction(function () use ($request, $validated) {
                $payload = collect($validated)->except(['conceptos'])->toArray();
                $payload['user_id'] = $request->user()->id;
                $payload['proveedor_id'] = (int) $validated['proveedor_id'];
                $payload['numero_presupuesto'] = Presupuesto::generarNumeroPresupuesto((int) $payload['proveedor_id']);
                $payload['con_iva'] = $payload['con_iva'] ?? true;
                $payload['iva_porcentaje'] = $payload['iva_porcentaje'] ?? 16.00;
                $payload['estado'] = $payload['estado'] ?? Presupuesto::ESTADO_BORRADOR;
                $payload = $this->normalizarEmpresaReceptora($payload, (int) $payload['proveedor_id']);

                $presupuesto = Presupuesto::create($payload);
                $presupuesto->asegurarTokenPublico();

                $this->sincronizarConceptos($presupuesto, $validated['conceptos']);
                $presupuesto->recalcularDesdeConceptos();
                $presupuesto->save();

                return $presupuesto->fresh(Presupuesto::eagerLodable());
            });

            $this->log('Presupuesto creado', ['presupuesto_id' => $presupuesto->id]);
            $this->log('Presupuesto creado', ['presupuesto_id' => $presupuesto]);

            return $this->success(
                new PresupuestoResource($presupuesto),
                'Presupuesto creado correctamente.',
                201
            );
        } catch (Throwable $e) {
            $this->log('Error al crear presupuesto', ['error' => $e->getMessage()]);

            return $this->error('No fue posible crear el presupuesto.', [$e->getMessage()], 500);
        }
    }

    public function show(Proveedor $proveedor, Presupuesto $presupuesto): JsonResponse
    {
        if (! $this->presupuestoAccesiblePorProveedor($proveedor, $presupuesto)) {
            return $this->error('La empresa no tiene acceso a este presupuesto en GestionPlus.', null, 403);
        }

        $user = auth()->user();
        // Emisor: item_visto + notificación ligada al presupuesto (dashboard enviados).
        // Receptor (catálogo): no tocar item_visto; solo marcar como leídas las notificaciones
        // PresupuestoRecibidoClienteProveedorNotification del usuario (badge recibidos / campana).
        if ($this->presupuestoEsEmisor($proveedor, $presupuesto)) {
            $presupuesto->markRead($user);
        } else {
            $this->marcarNotificacionesPresupuestoRecibidoLeidas($user, (int) $presupuesto->id);
        }

        // $presupuesto->load(array_merge(Presupuesto::eagerLodable(), ['estadoLogs.user']));
        $presupuesto->load(Presupuesto::eagerLodable());
        $presupuesto->asegurarTokenPublico();

        return $this->success(new PresupuestoResource($presupuesto));
    }

    public function update(UpdatePresupuestoRequest $request, Proveedor $proveedor, Presupuesto $presupuesto): JsonResponse
    {
        try {
            if (! $this->presupuestoEsEmisor($proveedor, $presupuesto)) {
                return $this->error('La empresa no tiene acceso a este presupuesto en GestionPlus.', null, 403);
            }

            if (! $this->puedeEditarPresupuesto($presupuesto)) {
                return $this->error(
                    'No se puede modificar este presupuesto en GestionPlus. Solo se editan borradores o presupuestos con observaciones del cliente.',
                    ['estado_actual' => $presupuesto->estado],
                    422
                );
            }

            $validated = $request->validated();
            if ((int) $validated['proveedor_id'] !== (int) $proveedor->id) {
                return $this->error('La empresa del payload no coincide con la empresa de la ruta en GestionPlus.', null, 422);
            }

            $validated = $this->resolverReceptorEmpresaParaValidacion($validated, $proveedor);
            $validated = $this->normalizarTerminosPayload($validated);

            if (! empty($validated['empresa_receptora_id'])) {
                $idReceptor = (int) $validated['empresa_receptora_id'];
                $esProveedorReceptor = ! empty($validated['es_proveedor_receptor']) && filter_var($validated['es_proveedor_receptor'], FILTER_VALIDATE_BOOLEAN);
                if ($esProveedorReceptor) {
                    if (! Proveedor::query()->whereKey($idReceptor)->exists()) {
                        return $this->error('El proveedor no existe.', null, 422);
                    }
                } elseif (! CarteraCliente::query()
                    ->where('proveedor_id', $proveedor->id)
                    ->whereKey($idReceptor)
                    ->exists()) {
                    return $this->error('El cliente de cartera no pertenece al proveedor indicado.', null, 422);
                }
            }

            $presupuesto = DB::transaction(function () use ($validated, $presupuesto) {
                $payload = collect($validated)->except(['conceptos'])->toArray();
                $payload['proveedor_id'] = (int) $validated['proveedor_id'];
                $payload['numero_presupuesto'] = $payload['numero_presupuesto'] ?? $presupuesto->numero_presupuesto;
                $payload = $this->normalizarEmpresaReceptora($payload, (int) $payload['proveedor_id']);

                $presupuesto->update($payload);
                if ($presupuesto->estado === Presupuesto::ESTADO_BORRADOR) {
                    $presupuesto->motivo_rechazo = null;
                    $presupuesto->save();
                }
                $this->sincronizarConceptos($presupuesto, $validated['conceptos']);
                $presupuesto->recalcularDesdeConceptos();

                // Recalcular fecha_vencimiento cuando cambian term_cond_dias_vigencia (solo borradores)
                if ($presupuesto->estado === Presupuesto::ESTADO_BORRADOR && array_key_exists('term_cond_dias_vigencia', $payload)) {
                    $presupuesto->fecha_vencimiento = $this->calcularFechaVencimiento($presupuesto);
                }

                $presupuesto->save();

                return $presupuesto->fresh(Presupuesto::eagerLodable());
            });

            $this->log('Presupuesto actualizado', ['presupuesto_id' => $presupuesto->id]);

            return $this->success(
                new PresupuestoResource($presupuesto),
                'Presupuesto actualizado correctamente.'
            );
        } catch (Throwable $e) {
            $this->log('Error al actualizar presupuesto', [
                'presupuesto_id' => $presupuesto->id,
                'error' => $e->getMessage(),
            ]);

            return $this->error('No fue posible actualizar el presupuesto.', [$e->getMessage()], 500);
        }
    }

    public function destroy(Proveedor $proveedor, Presupuesto $presupuesto): JsonResponse
    {
        try {
            if (! $this->presupuestoEsEmisor($proveedor, $presupuesto)) {
                return $this->error('La empresa no tiene acceso a este presupuesto en GestionPlus.', null, 403);
            }

            if ($presupuesto->estado !== Presupuesto::ESTADO_BORRADOR) {
                return $this->error(
                    'Solo se pueden eliminar presupuestos en borrador en GestionPlus.',
                    ['estado_actual' => $presupuesto->estado],
                    422
                );
            }

            $presupuesto->delete();

            $this->log('Presupuesto eliminado', [
                'presupuesto_id' => $presupuesto->id,
                'numero_presupuesto' => $presupuesto->numero_presupuesto,
            ]);

            return $this->success(null, 'Presupuesto eliminado correctamente.');
        } catch (Throwable $e) {
            $this->log('Error al eliminar presupuesto', [
                'presupuesto_id' => $presupuesto->id,
                'error' => $e->getMessage(),
            ]);

            return $this->error('No fue posible eliminar el presupuesto en GestionPlus.', [$e->getMessage()], 500);
        }
    }

    /**
     * Genera PDF desde datos del formulario (para borradores).
     */
    public function generarPdfDesdeFormulario(StorePresupuestoRequest $request, Proveedor $proveedor): Response
    {
        try {
            $user = $request->user();

            if (! $user || ! method_exists($user, 'tieneAccesoAProveedor') || ! $user->tieneAccesoAProveedor((int) $proveedor->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El usuario autenticado no tiene acceso al proveedor indicado.',
                ], 403);
            }

            $validated = $request->validated();

            if ((int) $validated['proveedor_id'] !== (int) $proveedor->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'El proveedor del payload no coincide con el proveedor de la ruta.',
                ], 422);
            }

            $validated = $this->resolverReceptorEmpresaParaValidacion($validated, $proveedor);
            $validated = $this->normalizarTerminosPayload($validated);
            $normalized = $this->normalizarEmpresaReceptora($validated, (int) $proveedor->id);
            $presupuestoGuardado = $this->guardarBorradorParaPreview($request, $proveedor, $validated);
            $presupuestoGuardado->loadMissing('anexos');

            // Convertir logo del proveedor a base64
            $logoProveedorBase64 = $this->convertirLogoProveedorABase64($proveedor);

            $df = $proveedor->direccion_fiscal ?? null;
            $estado = \Illuminate\Support\Arr::get((array) ($df ?? []), 'estado', $proveedor->estado ?? 'México');
            $lugar = $proveedor->ciudad ? ($proveedor->ciudad . ', ' . $estado) : null;

            $formData = array_merge($normalized, [
                'con_iva' => $normalized['con_iva'] ?? true,
                'iva_porcentaje' => $normalized['iva_porcentaje'] ?? 16,
            ]);

            // «Dirigido a:» = mismas columnas y orden que PDF guardado (ver datosVistaPdfPresupuestoGuardado).
            $datosPresupuesto = [
                'proveedor' => $proveedor,
                'logo_proveedor_base64' => $logoProveedorBase64,
                'numero_presupuesto' => $presupuestoGuardado->numero_presupuesto,
                'uuid' => $presupuestoGuardado->uuid,
                'fecha_emision' => $presupuestoGuardado->fecha_emision,
                'lugar' => $lugar,
                'concepto_general' => $normalized['concepto_general'],
                'con_iva' => $normalized['con_iva'] ?? true,
                'iva_porcentaje' => $normalized['iva_porcentaje'] ?? 16.00,
                'porcentaje_descuento' => $normalized['porcentaje_descuento'] ?? null,
                'cantidad_descuento' => $normalized['cantidad_descuento'] ?? null,
                'term_cond_moneda' => $normalized['term_cond_moneda'] ?? 'MXN',
                'receptor_lineas' => PresupuestoPdf::lineasReceptorPdfDesdePayloadReceptor($normalized),
                'conceptos' => $normalized['conceptos'] ?? [],
                'anexos' => PresupuestoPdf::anexosParaPlantillaPdf($presupuestoGuardado),
                'terminos_enunciados' => Presupuesto::buildTerminosEnunciadosFromArray($formData),
                'observaciones_enunciados' => Presupuesto::buildObservacionesEnunciadosFromArray($formData),
                'qr_code' => null,
            ];

            // Calcular totales
            $subtotal = collect($datosPresupuesto['conceptos'])->sum(function ($concepto) {
                if (($concepto['tipo'] ?? 'concepto') === 'parrafo') {
                    return 0;
                }

                return ($concepto['cantidad'] ?? 0) * ($concepto['precio_unitario'] ?? 0);
            });

            $pctDesc = isset($datosPresupuesto['porcentaje_descuento'])
                ? (int) $datosPresupuesto['porcentaje_descuento']
                : null;
            $cantidadDesc = isset($datosPresupuesto['cantidad_descuento'])
                ? (float) $datosPresupuesto['cantidad_descuento']
                : null;
            $totalesDoc = Presupuesto::calcularTotalesDocumento(
                (float) $subtotal,
                $pctDesc,
                $cantidadDesc,
                (bool) ($datosPresupuesto['con_iva'] ?? false),
                (float) ($datosPresupuesto['iva_porcentaje'] ?? 16)
            );

            $datosPresupuesto['subtotal'] = $totalesDoc['subtotal'];
            $datosPresupuesto['porcentaje_descuento'] = $totalesDoc['porcentaje_descuento'];
            $datosPresupuesto['cantidad_descuento'] = $totalesDoc['cantidad_descuento'];
            $datosPresupuesto['monto_descuento'] = $totalesDoc['monto_descuento'];
            $datosPresupuesto['iva_total'] = $totalesDoc['iva_total'];
            $datosPresupuesto['total'] = $totalesDoc['total'];
            $datosPresupuesto = $this->aplicarTemaPdfADatos(
                $datosPresupuesto,
                $request->input('pdf_theme') ?? $request->query('theme')
            );

            $this->log('Generación de PDF desde formulario solicitada', [
                'proveedor_id' => $proveedor->id,
                'numero_presupuesto' => $datosPresupuesto['numero_presupuesto'],
            ]);

            $response = $this->generarPdfResponse($datosPresupuesto, $datosPresupuesto['numero_presupuesto']);
            $response->headers->set('X-Presupuesto-Id', (string) $presupuestoGuardado->id);
            $response->headers->set('X-Presupuesto-Numero', (string) $presupuestoGuardado->numero_presupuesto);

            return $response;
        } catch (Throwable $e) {
            $this->log('Error al generar PDF desde formulario', [
                'proveedor_id' => $proveedor->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No fue posible generar el PDF.',
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }

    /**
     * Envía el correo al cliente con enlace público (operación aparte del cambio de estado).
     * Permitido para cualquier estado del presupuesto.
     */
    public function enviarCorreo(Request $request, Proveedor $proveedor, Presupuesto $presupuesto): JsonResponse
    {
        $validated = $request->validate([
            'incluir_invitacion' => 'boolean',
            'correo_destino' => 'email',
        ]);

        try {
            if (! $this->presupuestoEsEmisor($proveedor, $presupuesto)) {
                return $this->error('La empresa no tiene acceso a este presupuesto en GestionPlus.', null, 403);
            }

            if (! $validated['correo_destino'] || ! filter_var($validated['correo_destino'], FILTER_VALIDATE_EMAIL)) {
                return $this->error('No hay correo del cliente válido para enviar.', null, 422);
            }

            $presupuesto->load(Presupuesto::eagerLodable());
            $presupuesto->asegurarTokenPublico();

            $incluirInvitacion = $validated['incluir_invitacion'] ?? false;
            // $this->despacharCorreoPresupuesto($presupuesto, $incluirInvitacion);

            $appUrl = config('app.frontend_url', config('app.url'));
            // $enlacePublico = $appUrl . '/public/presupuesto/' . $presupuesto->token_publico;
            $enlacePublico = $appUrl . '/pages/proveedor/presupuesto/preview/' . $presupuesto->id;

            $nombreReceptor = $presupuesto->empresa_receptora_nombre ?? $presupuesto->empresa_receptora_empresa;

            Mail::to($validated['correo_destino'])->send(
                new PresupuestoEnviadoMail($presupuesto, $enlacePublico, $nombreReceptor, $incluirInvitacion)
            );

            $this->log('Presupuesto: correo al cliente enviado', ['presupuesto_id' => $presupuesto->id]);

            return $this->success(
                new PresupuestoResource($presupuesto->fresh(Presupuesto::eagerLodable())),
                'Correo enviado correctamente al cliente en GestionPlus.'
            );
        } catch (Throwable $e) {
            $this->log('Error al enviar correo de presupuesto', [
                'presupuesto_id' => $presupuesto->id,
                'error' => $e->getMessage(),
            ]);

            return $this->error('No fue posible enviar el correo.', [$e->getMessage()], 500);
        }
    }

    /**
     * Notifica en la app (y FCM) al receptor: proveedor catálogo o cliente con cuenta en otro proveedor.
     * No notifica al usuario que generó el presupuesto (user_id).
     * Permitido para cualquier estado del presupuesto.
     */
    public function notificarReceptorApp(Request $request, Proveedor $proveedor, Presupuesto $presupuesto): JsonResponse
    {
        try {
            if (! $this->presupuestoEsEmisor($proveedor, $presupuesto)) {
                return $this->error('La empresa no tiene acceso a este presupuesto en GestionPlus.', null, 403);
            }

            $presupuesto->load(Presupuesto::eagerLodable());

            $esReenvio = $request->boolean('es_reenvio');

            $this->despacharNotificacionesReceptor($presupuesto, $esReenvio);

            $this->log('Presupuesto: notificación a receptor en app', [
                'presupuesto_id' => $presupuesto->id,
                'es_reenvio' => $esReenvio,
            ]);

            return $this->success(
                new PresupuestoResource($presupuesto->fresh(Presupuesto::eagerLodable())),
                'Notificación enviada a los usuarios del receptor en GestionPlus.'
            );
        } catch (Throwable $e) {
            $this->log('Error al notificar receptor de presupuesto en GestionPlus', [
                'presupuesto_id' => $presupuesto->id,
                'error' => $e->getMessage(),
            ]);

            return $this->error('No fue posible enviar la notificación en GestionPlus.', [$e->getMessage()], 500);
        }
    }


    /**
     * Duplica un presupuesto con un nuevo folio y estado borrador.
     */
    public function duplicar(Request $request, Proveedor $proveedor, Presupuesto $presupuesto): JsonResponse
    {
        try {
            if (! $this->presupuestoEsEmisor($proveedor, $presupuesto)) {
                return $this->error('La empresa no tiene acceso a este presupuesto en GestionPlus.', null, 403);
            }

            $user = $request->user();
            if (! $user || ! $user->tieneAccesoAProveedor((int) $proveedor->id)) {
                return $this->error('El usuario autenticado no tiene acceso a la empresa en GestionPlus.', null, 403);
            }

            $presupuesto->load('conceptos');

            $nuevo = DB::transaction(function () use ($presupuesto, $user) {
                $payload = $presupuesto->only([
                    'proveedor_id',
                    'empresa_receptora_id',
                    'proveedor_receptor_id',
                    'empresa_receptora_nombre',
                    'empresa_receptora_puesto',
                    'empresa_receptora_empresa',
                    'empresa_receptora_alias',
                    'empresa_receptora_telefono',
                    'empresa_receptora_correo',
                    'configuracion_condiciones',
                    'concepto_general',
                    'con_iva',
                    'iva_porcentaje',
                    'porcentaje_descuento',
                    'cantidad_descuento',
                    'term_cond_dias_vigencia',
                    'term_cond_moneda',
                    'term_cond_impuestos_en_pdf',
                    'term_cond_iva',
                    'term_cond_tiempo_entrega_dias',
                    'term_cond_inicio_trabajo',
                    'term_cond_inicio_trabajo_porcentaje',
                    'term_cond_inicio_trabajo_cantidad',
                    'term_cond_textos_libres',
                    'term_cond_visibilidad',
                    'validacion_alcances',
                    'obs_garantia_dias',
                    'obs_traslados',
                    'obs_viaticos',
                ]);

                $payload['numero_presupuesto'] = Presupuesto::generarNumeroPresupuesto((int) $presupuesto->proveedor_id);
                $payload['fecha_emision'] = now()->toDateString();
                $payload['estado'] = Presupuesto::ESTADO_BORRADOR;
                $payload['user_id'] = $user->id;

                $nuevo = Presupuesto::create($payload);
                $nuevo->asegurarTokenPublico();

                $conceptos = $presupuesto->conceptos->map(function (PresupuestoConcepto $c, int $index) {
                    return [
                        'tipo' => $c->tipo ?? PresupuestoConcepto::TIPO_CONCEPTO,
                        'descripcion' => $c->descripcion,
                        'cantidad' => (float) $c->cantidad,
                        'unidad' => $c->unidad,
                        'precio_unitario' => (float) $c->precio_unitario,
                    ];
                })->values()->all();

                $this->sincronizarConceptos($nuevo, $conceptos);
                $nuevo->recalcularDesdeConceptos();
                $nuevo->save();

                return $nuevo->fresh(Presupuesto::eagerLodable());
            });

            $this->log('Presupuesto duplicado', [
                'origen_id' => $presupuesto->id,
                'nuevo_id' => $nuevo->id,
                'numero_presupuesto' => $nuevo->numero_presupuesto,
            ]);

            return $this->success(
                new PresupuestoResource($nuevo),
                'Presupuesto duplicado correctamente.',
                201
            );
        } catch (Throwable $e) {
            $this->log('Error al duplicar presupuesto', [
                'presupuesto_id' => $presupuesto->id,
                'error' => $e->getMessage(),
            ]);

            return $this->error('No fue posible duplicar el presupuesto en GestionPlus.', [$e->getMessage()], 500);
        }
    }


    /**
     * Genera y descarga el PDF de un presupuesto guardado.
     */
    public function generarPdf(Request $request, Proveedor $proveedor, Presupuesto $presupuesto): Response
    {
        try {
            if (! $this->presupuestoAccesiblePorProveedor($proveedor, $presupuesto)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Presupuesto no pertenece a este proveedor.',
                ], 403);
            }

            $presupuesto->load(Presupuesto::eagerLodable());

            $this->log('Generación de PDF solicitada', [
                'presupuesto_id' => $presupuesto->id,
                'numero_presupuesto' => $presupuesto->numero_presupuesto,
            ]);

            $logoProveedorBase64 = $this->convertirLogoProveedorABase64($presupuesto->proveedor);
            $proveedorEmisor = $presupuesto->proveedor;
            $df = $proveedorEmisor?->direccion_fiscal;
            $estado = \Illuminate\Support\Arr::get((array) ($df ?? []), 'estado', $proveedorEmisor->estado ?? 'México');
            $lugar = $proveedorEmisor?->ciudad ? ($proveedorEmisor->ciudad . ', ' . $estado) : null;
            $qrCode = $this->generarQrCodeParaPresupuesto($presupuesto);

            $datosVista = $this->datosVistaPdfPresupuestoGuardado(
                $presupuesto,
                $proveedorEmisor,
                $logoProveedorBase64,
                $lugar,
                $qrCode
            );
            $datosVista = $this->aplicarTemaPdfADatos(
                $datosVista,
                $request->query('theme') ?? $request->query('pdf_theme')
            );

            return $this->generarPdfResponse($datosVista, $presupuesto->numero_presupuesto);
        } catch (Throwable $e) {
            $this->log('Error al generar PDF', [
                'presupuesto_id' => $presupuesto->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No fue posible generar el PDF.',
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }

    /**
     * Marca el presupuesto como enviado (estado, token público, vigencia). Correo y notificación in-app van en endpoints dedicados.
     *
     * @see enviarCorreo
     * @see notificarReceptorApp
     */
    public function enviar(Request $request, Proveedor $proveedor, Presupuesto $presupuesto): JsonResponse
    {
        try {
            if (! $this->presupuestoEsEmisor($proveedor, $presupuesto)) {
                return $this->error('La empresa no tiene acceso a este presupuesto en GestionPlus.', null, 403);
            }

            if ($presupuesto->estado !== Presupuesto::ESTADO_BORRADOR) {
                return $this->error(
                    'Solo se puede enviar un presupuesto en estado borrador en GestionPlus.',
                    ['estado_actual' => $presupuesto->estado],
                    422
                );
            }

            $presupuesto->load(Presupuesto::eagerLodable());

            DB::transaction(function () use ($request, $presupuesto) {
                $estadoAnterior = $presupuesto->estado;

                // Al enviar desde borrador, la fecha de emision se fija al dia actual.
                $presupuesto->fecha_emision = now()->toDateString();
                $presupuesto->estado = Presupuesto::ESTADO_ENVIADO;
                $presupuesto->asegurarTokenPublico();

                if (! $presupuesto->fecha_vencimiento) {
                    $presupuesto->fecha_vencimiento = $this->calcularFechaVencimiento($presupuesto);
                }
                $presupuesto->save();
                $presupuesto->registrarCambioEstado($estadoAnterior, $request->user()?->id);

                if ($this->debeNotificarComoProveedorCatalogo($presupuesto)) {
                    $this->notificarUsuarioPrincipalProveedorReceptor($presupuesto);
                }
            });

            $presupuesto->refresh()->load(Presupuesto::eagerLodable());
            $this->log('Presupuesto enviado (estado)', ['presupuesto_id' => $presupuesto->id]);

            return $this->success(
                new PresupuestoResource($presupuesto),
                'Presupuesto marcado como enviado.'
            );
        } catch (Throwable $e) {
            $this->log('Error al enviar presupuesto', [
                'presupuesto_id' => $presupuesto->id,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'code' => $e->getCode(),
            ]);

            return $this->error('No fue posible enviar el presupuesto en GestionPlus.', [$e->getMessage()], 500);
        }
    }

    /**
     * Reenvía el presupuesto por correo al cliente (solo si ya está enviado y tiene correo).
     */
    public function reenviar(Request $request, Proveedor $proveedor, Presupuesto $presupuesto): JsonResponse
    {
        try {
            if (! $this->presupuestoEsEmisor($proveedor, $presupuesto)) {
                return $this->error('La empresa no tiene acceso a este presupuesto en GestionPlus.', null, 403);
            }

            if (! $presupuesto->empresa_receptora_correo || ! filter_var($presupuesto->empresa_receptora_correo, FILTER_VALIDATE_EMAIL)) {
                return $this->error('No hay correo del cliente para reenviar.', null, 422);
            }

            $presupuesto->load(Presupuesto::eagerLodable());
            $presupuesto->asegurarTokenPublico();

            // $this->despacharCorreoPresupuesto($presupuesto);
            $this->despacharNotificacionesReceptor($presupuesto, true);

            $this->log('Presupuesto reenviado por correo', ['presupuesto_id' => $presupuesto->id]);

            return $this->success(
                new PresupuestoResource($presupuesto->fresh(Presupuesto::eagerLodable())),
                'Presupuesto reenviado correctamente al cliente.'
            );
        } catch (Throwable $e) {
            $this->log('Error al reenviar presupuesto', [
                'presupuesto_id' => $presupuesto->id,
                'error' => $e->getMessage(),
            ]);

            return $this->error('No fue posible reenviar el presupuesto.', [$e->getMessage()], 500);
        }
    }


    
    ///////////////////////
    // PRIVATES METHODS ///
    ///////////////////////

    /**
     * Guarda o actualiza un borrador antes de generar el PDF de preview.
     *
     * @param  array<string, mixed>  $validated
     */
    private function guardarBorradorParaPreview(Request $request, Proveedor $proveedor, array $validated): Presupuesto
    {
        return DB::transaction(function () use ($request, $proveedor, $validated) {
            $payload = collect($validated)->except(['conceptos', 'presupuesto_id'])->toArray();
            $payload['user_id'] = $request->user()->id;
            $payload['proveedor_id'] = (int) $validated['proveedor_id'];
            $payload['con_iva'] = $payload['con_iva'] ?? true;
            $payload['iva_porcentaje'] = $payload['iva_porcentaje'] ?? 16.00;
            $payload['estado'] = Presupuesto::ESTADO_BORRADOR;
            $payload = $this->normalizarEmpresaReceptora($payload, (int) $payload['proveedor_id']);

            $presupuestoId = (int) ($validated['presupuesto_id'] ?? 0);
            if ($presupuestoId > 0) {
                $presupuesto = Presupuesto::query()->findOrFail($presupuestoId);

                if (! $this->presupuestoEsEmisor($proveedor, $presupuesto)) {
                    abort(403, 'La empresa no tiene acceso a este presupuesto en GestionPlus.');
                }

                if (! $this->puedeEditarPresupuesto($presupuesto)) {
                    abort(422, 'No se puede modificar este presupuesto en GestionPlus.');
                }

                $payload['numero_presupuesto'] = $payload['numero_presupuesto'] ?? $presupuesto->numero_presupuesto;
                $presupuesto->update($payload);
            } else {
                $payload['numero_presupuesto'] = $payload['numero_presupuesto'] ?? Presupuesto::generarNumeroPresupuesto((int) $payload['proveedor_id']);
                $presupuesto = Presupuesto::create($payload);
            }

            $presupuesto->asegurarTokenPublico();
            $this->sincronizarConceptos($presupuesto, $validated['conceptos']);
            $presupuesto->recalcularDesdeConceptos();
            $presupuesto->fecha_vencimiento = array_key_exists('term_cond_dias_vigencia', $payload)
                ? $this->calcularFechaVencimiento($presupuesto)
                : $presupuesto->fecha_vencimiento;
            $presupuesto->save();

            return $presupuesto->fresh(Presupuesto::eagerLodable());
        });
    }

    /**
     * El usuario del proveedor de la ruta puede ver el presupuesto si es emisor o receptor (catálogo).
     */
    private function presupuestoAccesiblePorProveedor(Proveedor $proveedor, Presupuesto $presupuesto): bool
    {
        return (int) $presupuesto->proveedor_id === (int) $proveedor->id
            || (int) ($presupuesto->proveedor_receptor_id ?? 0) === (int) $proveedor->id;
    }

    /**
     * Solo el proveedor emisor puede editar, eliminar, enviar, duplicar, etc.
     */
    private function presupuestoEsEmisor(Proveedor $proveedor, Presupuesto $presupuesto): bool
    {
        return (int) $presupuesto->proveedor_id === (int) $proveedor->id;
    }

    /**
     * Marca como leídas en Laravel las notificaciones de “presupuesto recibido” para este folio.
     * No usa item_visto del presupuesto (ese flag es del flujo emisor).
     */
    private function marcarNotificacionesPresupuestoRecibidoLeidas(?User $user, int $presupuestoId): void
    {
        if (! $user || $presupuestoId <= 0) {
            return;
        }

        $user->unreadNotifications()
            ->where('type', PresupuestoRecibidoClienteProveedorNotification::class)
            ->where('data->presupuesto_id', $presupuestoId)
            ->get()
            ->each->markAsRead();
    }

    /**
     * Borrador: editable. Rechazado con motivo (observaciones del cliente): editable.
     */
    private function puedeEditarPresupuesto(Presupuesto $presupuesto): bool
    {
        if ($presupuesto->estado === Presupuesto::ESTADO_BORRADOR) {
            return true;
        }

        if (in_array($presupuesto->estado, [Presupuesto::ESTADO_RECHAZADO, Presupuesto::ESTADO_RECHAZADO_CON_OBSERVACION], true)) {
            return $presupuesto->motivo_rechazo && trim((string) $presupuesto->motivo_rechazo) !== '';
        }

        return false;
    }

    /**
     * Si el cliente no envía es_proveedor_receptor pero el id corresponde a otro proveedor (no a cartera del emisor), infiere true.
     * Evita 422 al confundir id de proveedor del catálogo con cliente de cartera.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function resolverReceptorEmpresaParaValidacion(array $validated, Proveedor $proveedorEmisor): array
    {
        if (empty($validated['empresa_receptora_id'])) {
            return $validated;
        }

        if (! empty($validated['es_proveedor_receptor']) && filter_var($validated['es_proveedor_receptor'], FILTER_VALIDATE_BOOLEAN)) {
            return $validated;
        }

        $id = (int) $validated['empresa_receptora_id'];

        $enCartera = CarteraCliente::query()
            ->where('proveedor_id', $proveedorEmisor->id)
            ->whereKey($id)
            ->exists();

        if ($enCartera) {
            $validated['es_proveedor_receptor'] = false;

            return $validated;
        }

        if (Proveedor::query()->whereKey($id)->exists()) {
            $validated['es_proveedor_receptor'] = true;

            return $validated;
        }

        return $validated;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function normalizarEmpresaReceptora(array $payload, int $proveedorId): array
    {
        unset($payload['empresa_receptora_logo']);

        foreach ([
            'empresa_receptora_nombre',
            'empresa_receptora_puesto',
            'empresa_receptora_empresa',
            'empresa_receptora_alias',
            'empresa_receptora_telefono',
            'empresa_receptora_correo',
        ] as $field) {
            $payload[$field] = $this->normalizarTextoReceptor($payload[$field] ?? null);
        }

        $esProveedorReceptor = filter_var($payload['es_proveedor_receptor'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (empty($payload['empresa_receptora_id'])) {
            $payload['proveedor_receptor_id'] = null;
            $payload['configuracion_condiciones'] = $this->limpiarMetaReceptorEnConfiguracionJson(
                $payload['configuracion_condiciones'] ?? null
            );
            unset($payload['es_proveedor_receptor']);

            return $payload;
        }

        if ($esProveedorReceptor) {
            /** La FK empresa_receptora_id solo admite cartera; el id del proveedor catálogo va en proveedor_receptor_id. */
            $proveedorReceptorId = (int) $payload['empresa_receptora_id'];
            $receptor = Proveedor::query()->findOrFail($proveedorReceptorId);

            $payload['empresa_receptora_nombre'] = $this->primerTextoReceptorOpcional(
                $receptor->contacto_nombre,
                $receptor->nombre_propietario
            );
            $payload['empresa_receptora_puesto'] = $this->normalizarTextoReceptor($receptor->contacto_cargo);
            $payload['empresa_receptora_empresa'] = $this->primerTextoReceptorOpcional(
                $receptor->nombre_comercial,
                $receptor->razon_social
            );
            $payload['empresa_receptora_alias'] = null;
            $payload['empresa_receptora_telefono'] = $this->primerTextoReceptorOpcional(
                $receptor->contacto_telefono,
                $receptor->telefono,
                $receptor->celular
            );
            $payload['empresa_receptora_correo'] = $this->primerTextoReceptorOpcional(
                $receptor->contacto_correo,
                $receptor->email
            );
            $payload['empresa_receptora_id'] = null;
            $payload['proveedor_receptor_id'] = $proveedorReceptorId;
            $payload['configuracion_condiciones'] = $this->limpiarMetaReceptorEnConfiguracionJson(
                $payload['configuracion_condiciones'] ?? null
            );
        } else {
            $cliente = CarteraCliente::query()
                ->where('proveedor_id', $proveedorId)
                ->findOrFail((int) $payload['empresa_receptora_id']);

            $payload['empresa_receptora_nombre'] = $this->normalizarTextoReceptor($cliente->nombre);
            $payload['empresa_receptora_puesto'] = $this->normalizarTextoReceptor($cliente->puesto);
            $payload['empresa_receptora_empresa'] = $this->normalizarTextoReceptor($cliente->empresa);
            $payload['empresa_receptora_alias'] = $this->normalizarTextoReceptor($cliente->alias_empresa);
            $payload['empresa_receptora_telefono'] = $this->normalizarTextoReceptor($cliente->telefono);
            $payload['empresa_receptora_correo'] = $this->normalizarTextoReceptor($cliente->correo);
            $payload['proveedor_receptor_id'] = null;
            $payload['configuracion_condiciones'] = $this->limpiarMetaReceptorEnConfiguracionJson(
                $payload['configuracion_condiciones'] ?? null
            );
        }

        unset($payload['es_proveedor_receptor']);

        return $payload;
    }

    /**
     * @param  string|null  ...$vals
     */
    private function primerTextoReceptorOpcional(?string ...$vals): ?string
    {
        foreach ($vals as $v) {
            $t = $this->normalizarTextoReceptor($v);
            if ($t !== null) {
                return $t;
            }
        }

        return null;
    }

    private function normalizarTextoReceptor(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));
        if ($text === '') {
            return null;
        }

        return preg_match('/^[_\-\x{2013}\x{2014}]+$/u', $text) === 1 ? null : $text;
    }

    /**
     * Quita claves legadas del JSON (el id de receptor catálogo vive en proveedor_receptor_id).
     *
     * @param  array<string, mixed>|null  $configuracion
     * @return array<string, mixed>
     */
    private function limpiarMetaReceptorEnConfiguracionJson(?array $configuracion): array
    {
        $config = is_array($configuracion) ? $configuracion : [];
        unset($config['proveedor_receptor_id'], $config['receptor_es_proveedor_catalogo']);

        return $config;
    }

    /**
     * Normaliza términos para persistencia y preview (fase de transición legacy -> estructura escalable).
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizarTerminosPayload(array $payload): array
    {
        $inicioTrabajo = isset($payload['term_cond_inicio_trabajo']) ? (int) $payload['term_cond_inicio_trabajo'] : null;
        $anticipoPct = isset($payload['term_cond_inicio_trabajo_porcentaje']) ? (float) $payload['term_cond_inicio_trabajo_porcentaje'] : null;
        $anticipoMonto = isset($payload['term_cond_inicio_trabajo_cantidad']) ? (float) $payload['term_cond_inicio_trabajo_cantidad'] : null;

        if ($inicioTrabajo !== 2) {
            $payload['term_cond_inicio_trabajo_porcentaje'] = null;
            $payload['term_cond_inicio_trabajo_cantidad'] = null;
        } else {
            $tienePct = $anticipoPct !== null && $anticipoPct > 0;
            $tieneMonto = $anticipoMonto !== null && $anticipoMonto > 0;

            if ($tienePct && $tieneMonto) {
                // Política de conflicto: priorizar porcentaje y limpiar monto.
                $payload['term_cond_inicio_trabajo_cantidad'] = null;
            } elseif (! $tienePct && ! $tieneMonto) {
                $payload['term_cond_inicio_trabajo_porcentaje'] = null;
                $payload['term_cond_inicio_trabajo_cantidad'] = null;
            } else {
                $payload['term_cond_inicio_trabajo_porcentaje'] = $tienePct ? $anticipoPct : null;
                $payload['term_cond_inicio_trabajo_cantidad'] = $tieneMonto ? $anticipoMonto : null;
            }
        }

        $textos = is_array($payload['term_cond_textos_libres'] ?? null) ? $payload['term_cond_textos_libres'] : [];
        $textos = array_values(array_filter(array_map(
            static fn($item) => trim((string) $item),
            $textos
        ), static fn($item) => $item !== ''));
        $payload['term_cond_textos_libres'] = array_slice($textos, 0, 4);

        $configuracion = is_array($payload['configuracion_condiciones'] ?? null) ? $payload['configuracion_condiciones'] : [];
        $terminosActivos = ! array_key_exists('terminos_activo', $configuracion)
            || (bool) $configuracion['terminos_activo'];

        $legacyTraslados = array_key_exists('obs_traslados', $payload) ? (bool) $payload['obs_traslados'] : false;
        $legacyViaticos = array_key_exists('obs_viaticos', $payload) ? (bool) $payload['obs_viaticos'] : false;
        $visibilidad = is_array($payload['term_cond_visibilidad'] ?? null) ? $payload['term_cond_visibilidad'] : [];

        if (! $terminosActivos) {
            $payload['term_cond_dias_vigencia'] = null;
            $payload['term_cond_tiempo_entrega_dias'] = null;
            $payload['term_cond_inicio_trabajo'] = null;
            $payload['term_cond_inicio_trabajo_porcentaje'] = null;
            $payload['term_cond_inicio_trabajo_cantidad'] = null;
            $payload['term_cond_textos_libres'] = [];
            $payload['obs_garantia_dias'] = 0;
        }

        $payload['term_cond_visibilidad'] = [
            'pago_contra_conformidad' => array_key_exists('pago_contra_conformidad', $visibilidad)
                ? (bool) $visibilidad['pago_contra_conformidad']
                : false,
            'garantia_calidad' => array_key_exists('garantia_calidad', $visibilidad)
                ? (bool) $visibilidad['garantia_calidad']
                : false,
            'correccion_defectos' => array_key_exists('correccion_defectos', $visibilidad)
                ? (bool) $visibilidad['correccion_defectos']
                : false,
            'incluye_materiales_insumos' => array_key_exists('incluye_materiales_insumos', $visibilidad)
                ? (bool) $visibilidad['incluye_materiales_insumos']
                : false,
            'incluye_traslados' => array_key_exists('incluye_traslados', $visibilidad)
                ? (bool) $visibilidad['incluye_traslados']
                : $legacyTraslados,
            'incluye_viaticos' => array_key_exists('incluye_viaticos', $visibilidad)
                ? (bool) $visibilidad['incluye_viaticos']
                : $legacyViaticos,
        ];

        if (! $terminosActivos) {
            $payload['term_cond_visibilidad'] = [
                'pago_contra_conformidad' => false,
                'garantia_calidad' => false,
                'correccion_defectos' => false,
                'incluye_materiales_insumos' => false,
                'incluye_traslados' => false,
                'incluye_viaticos' => false,
            ];
        }

        $alcances = is_array($payload['validacion_alcances'] ?? null) ? $payload['validacion_alcances'] : [];
        $payload['validacion_alcances'] = [
            'incluye_todos_los_costos' => array_key_exists('incluye_todos_los_costos', $alcances)
                ? (bool) $alcances['incluye_todos_los_costos']
                : false,
            'sin_costos_adicionales_no_autorizados' => array_key_exists('sin_costos_adicionales_no_autorizados', $alcances)
                ? (bool) $alcances['sin_costos_adicionales_no_autorizados']
                : false,
            'adicionales_requieren_autorizacion_escrita' => array_key_exists('adicionales_requieren_autorizacion_escrita', $alcances)
                ? (bool) $alcances['adicionales_requieren_autorizacion_escrita']
                : false,
        ];

        if (! $terminosActivos) {
            $payload['validacion_alcances'] = [
                'incluye_todos_los_costos' => false,
                'sin_costos_adicionales_no_autorizados' => false,
                'adicionales_requieren_autorizacion_escrita' => false,
            ];
        }

        return $payload;
    }

    /**
     * Id del proveedor del catálogo receptor: columna proveedor_receptor_id; compatibilidad JSON / empresa_receptora_id antigua.
     */
    private function resolverIdProveedorReceptorCatalogo(Presupuesto $presupuesto): ?int
    {
        if ($presupuesto->proveedor_receptor_id) {
            return (int) $presupuesto->proveedor_receptor_id;
        }

        $config = $presupuesto->configuracion_condiciones;
        if (is_array($config) && isset($config['proveedor_receptor_id'])) {
            $id = (int) $config['proveedor_receptor_id'];

            return $id > 0 ? $id : null;
        }

        $id = $presupuesto->empresa_receptora_id;
        if (! $id) {
            return null;
        }

        $emisorId = (int) $presupuesto->proveedor_id;
        $enCartera = CarteraCliente::query()
            ->where('proveedor_id', $emisorId)
            ->whereKey((int) $id)
            ->exists();

        if ($enCartera) {
            return null;
        }

        return Proveedor::query()->whereKey((int) $id)->exists() ? (int) $id : null;
    }

    /**
     * Al enviar: si el receptor es otro proveedor del catálogo, notificar a sus usuarios activos en app/FCM.
     */
    /**
     * Evita duplicados cuando un usuario pertenece al emisor y también al receptor.
     * En esos casos solo debe recibir la notificación del lado emisor.
     */
    private function usuarioDebeExcluirseDeNotificacionReceptor(Presupuesto $presupuesto, User $user): bool
    {
        // Si el usuario tiene acceso al proveedor emisor, no debe recibir la notificación de receptor.
        if (method_exists($user, 'tieneAccesoAProveedor') && $user->tieneAccesoAProveedor((int) $presupuesto->proveedor_id)) {
            return true;
        }

        return $presupuesto->user_id
            ? (int) $presupuesto->user_id === (int) $user->id
            : false;
    }

    private function notificarUsuariosProveedorReceptor(Presupuesto $presupuesto, bool $esReenvio = false): void
    {
        $id = $this->resolverIdProveedorReceptorCatalogo($presupuesto);
        if (! $id) {
            return;
        }

        $proveedorReceptor = Proveedor::query()->find((int) $id);
        if (! $proveedorReceptor) {
            return;
        }

        foreach ($proveedorReceptor->usuariosActivos()->get()->unique('id') as $user) {
            if ($this->usuarioDebeExcluirseDeNotificacionReceptor($presupuesto, $user)) {
                continue;
            }
            if ($this->yaSeNotificoReceptorPresupuesto($user, (int) $presupuesto->id, $esReenvio)) {
                continue;
            }
            $user->notify(new PresupuestoRecibidoClienteProveedorNotification($presupuesto, $esReenvio));
        }
    }

    private function notificarUsuarioPrincipalProveedorReceptor(Presupuesto $presupuesto, bool $esReenvio = false): void
    {
        $id = $this->resolverIdProveedorReceptorCatalogo($presupuesto);
        if (! $id) {
            return;
        }

        $proveedorReceptor = Proveedor::query()->find((int) $id);
        if (! $proveedorReceptor) {
            return;
        }

        $usuarioPrincipal = $proveedorReceptor->usuarioPrincipal();
        if (! $usuarioPrincipal || ! $usuarioPrincipal->status) {
            return;
        }

        if ($this->usuarioDebeExcluirseDeNotificacionReceptor($presupuesto, $usuarioPrincipal)) {
            return;
        }

        if ($this->yaSeNotificoReceptorPresupuesto($usuarioPrincipal, (int) $presupuesto->id, $esReenvio)) {
            return;
        }

        $usuarioPrincipal->notify(new PresupuestoRecibidoClienteProveedorNotification($presupuesto, $esReenvio));
    }

    private function yaSeNotificoReceptorPresupuesto(User $user, int $presupuestoId, bool $esReenvio): bool
    {
        $q = $user->notifications()
            ->where('type', PresupuestoRecibidoClienteProveedorNotification::class)
            ->where('data->presupuesto_id', $presupuestoId)
            ->where('created_at', '>=', now()->subMinutes(5));

        if ($esReenvio) {
            return $q->where('data->es_reenvio', true)->exists();
        }

        // Primera entrega: es_reenvio false o ausente en registros antiguos.
        return $q->where(function ($sub) {
            $sub->where('data->es_reenvio', false)
                ->orWhereNull('data->es_reenvio');
        })->exists();
    }

    /**
     * Usa la meta guardada en configuracion_condiciones al normalizar; si falta (registros viejos), infiere por tablas.
     */
    private function debeNotificarComoProveedorCatalogo(Presupuesto $presupuesto): bool
    {
        if ($presupuesto->proveedor_receptor_id) {
            return true;
        }

        $config = $presupuesto->configuracion_condiciones;
        if (is_array($config) && array_key_exists('receptor_es_proveedor_catalogo', $config)) {
            return (bool) $config['receptor_es_proveedor_catalogo'];
        }

        return $this->resolverIdProveedorReceptorCatalogo($presupuesto) !== null;
    }

    /**
     * @param array<int, array<string, mixed>> $conceptos
     */
    private function sincronizarConceptos(Presupuesto $presupuesto, array $conceptos): void
    {
        $presupuesto->conceptos()->delete();

        foreach ($conceptos as $index => $conceptoData) {
            $concepto = new PresupuestoConcepto([
                'numero' => $index + 1,
                'tipo' => $conceptoData['tipo'] ?? PresupuestoConcepto::TIPO_CONCEPTO,
                'descripcion' => $conceptoData['descripcion'],
                'cantidad' => $conceptoData['cantidad'],
                'unidad' => $conceptoData['unidad'],
                'precio_unitario' => $conceptoData['precio_unitario'],
            ]);
            $concepto->calcularImporte();
            $presupuesto->conceptos()->save($concepto);
        }
    }

    /**
     * Registra un mensaje en el log.
     * 
     * @param string $message
     * @param array<string, mixed> $data
     * @return void
     */
    private function log($message, $data = []): void
    {
        if (! $this->logEnabled) {
            return;
        }

        Log::info($message, $data);
    }

    /**
     * Genera el folio siguiente para el proveedor.
     * 
     * @param \App\Models\Proveedor $proveedor
     * @return string
     */
    private function formatearFolioSiguiente(Proveedor $proveedor): string
    {
        $consecutivo = (int) ($proveedor->consecutivo_presupuesto_siguiente ?? 1);
        $consecutivo = max($consecutivo, 1);

        return 'PRES-' . str_pad((string) $consecutivo, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Genera la respuesta PDF usando Laravel DomPDF.
     *
     * @param array<string, mixed> $datosPresupuesto
     */
    private function generarPdfResponse(array $datosPresupuesto, string $numeroPresupuesto): Response
    {
        try {
            // Verificar si GD está disponible antes de generar el PDF
            $gdDisponible = extension_loaded('gd');

            if (!$gdDisponible) {
                $this->log('Advertencia: GD no está disponible en GestionPlus. Las imágenes PNG/GIF no se mostrarán.', [
                    'numero_presupuesto' => $numeroPresupuesto,
                ]);
            }

            // Generar nombre del archivo
            $filename = "Presupuesto_{$numeroPresupuesto}.pdf";

            // Convertir logos a base64 para incluirlos en el PDF
            // Si GD no está disponible, retornará arrays vacíos y se usarán fallbacks de texto
            $logosBase64 = $this->convertirLogosABase64();

            // Agregar los logos base64 a los datos del presupuesto
            $datosPresupuesto['logos_base64'] = $logosBase64;
            $datosPresupuesto['gd_disponible'] = $gdDisponible; // Información útil para la vista

            // Generar PDF usando el facade PDF de barryvdh/laravel-dompdf
            // Tamaño carta (8.5" x 11") con márgenes estándar 1 pulgada (25.4mm)
            // $pdf = Pdf::loadView('presupuestos.pdf', ['presupuesto' => $datosPresupuesto])
            $pdf = Pdf::loadView(PresupuestoPdfTemplate::viewName(), ['presupuesto' => $datosPresupuesto])
                ->setPaper('letter', 'portrait') // Tamaño carta (8.5" x 11")
                ->setOption('isRemoteEnabled', false) // Deshabilitar carga remota para evitar timeouts
                ->setOption('isHtml5ParserEnabled', true)
                ->setOption('isPhpEnabled', true) // Requerido para script de número de página
                ->setOption('defaultFont', 'DejaVu Sans')
                ->setOption('margin-top', 25)
                ->setOption('margin-bottom', 70) // ~25mm: reserva espacio para pie de página
                ->setOption('margin-left', 25)
                ->setOption('margin-right', 25)
                ->setOption('enable-local-file-access', false) // No necesitamos acceso a archivos locales si usamos base64
                ->setOption('chroot', public_path()) // Establecer directorio raíz para archivos locales
                ->setOption('compress', true)
                ->setOption('dpi', 96);

            // Retornar PDF como descarga
            return $pdf->download($filename);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();

            // Mensaje más claro si el error es por falta de GD
            if (stripos($errorMessage, 'GD extension') !== false || stripos($errorMessage, 'gd') !== false) {
                $errorMessage = 'La extensión GD de PHP es requerida para generar PDFs con imágenes. Por favor, instala la extensión GD en tu servidor PHP.';
            }

            $this->log('Error al generar PDF en GestionPlus', [
                'numero_presupuesto' => $numeroPresupuesto,
                'error' => $e->getMessage(),
                'gd_disponible' => extension_loaded('gd'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No fue posible generar el PDF: ' . $errorMessage,
            ], 500);
        }
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    private function aplicarTemaPdfADatos(array $datos, ?string $theme): array
    {
        $datos['pdf_theme'] = $this->presupuestoThemeService->resolveThemeKey($theme);

        return $datos;
    }

    /**
     * Convierte los logos de las apps a base64.
     * 
     * IMPORTANTE: DomPDF requiere la extensión GD de PHP para procesar imágenes PNG/GIF.
     * Si GD no está disponible, se retornan arrays vacíos y se usan iconos de texto como fallback.
     * 
     * Solo las imágenes JPEG pueden procesarse sin GD.
     * 
     * @return array<string, string>
     */
    private function convertirLogosABase64(): array
    {
        $logos = [
            'facturapro' => '',
            'constucc' => '',
            'gestionplus' => '',
        ];

        // Verificar si GD está disponible
        // Si no está disponible, DomPDF no podrá procesar PNG/GIF, así que retornamos vacío
        if (!extension_loaded('gd')) {
            $this->log('GD no está disponible - usando fallback de texto para logos', []);
            return $logos;
        }

        $facturaproPath = public_path('assets/logos/logo-facturapro.png');
        $constuccPath = public_path('assets/logos/logo-construcc.png');
        $gestionPlusPath = \App\Support\EmailLogoHelper::gestionPlusLogoAbsolutePath();

        try {
            if (file_exists($facturaproPath) && is_readable($facturaproPath)) {
                $imageData = @file_get_contents($facturaproPath);
                if ($imageData !== false && !empty($imageData)) {
                    $logos['facturapro'] = 'data:image/png;base64,' . base64_encode($imageData);
                }
            }

            if (file_exists($constuccPath) && is_readable($constuccPath)) {
                $imageData = @file_get_contents($constuccPath);
                if ($imageData !== false && !empty($imageData)) {
                    $logos['constucc'] = 'data:image/png;base64,' . base64_encode($imageData);
                }
            }

            if ($gestionPlusPath && file_exists($gestionPlusPath) && is_readable($gestionPlusPath)) {
                $imageData = @file_get_contents($gestionPlusPath);
                if ($imageData !== false && !empty($imageData)) {
                    $logos['gestionplus'] = 'data:image/png;base64,' . base64_encode($imageData);
                }
            }
        } catch (\Exception $e) {
            // Si hay algún error al leer las imágenes, retornar arrays vacíos para usar fallback
            $this->log('Error al convertir logos a base64', [
                'error' => $e->getMessage(),
            ]);
        }

        return $logos;
    }

    /**
     * Genera la URL del código QR para la versión web del presupuesto.
     * Usa API externa y convierte a base64 para que DomPDF lo renderice sin habilitar remote.
     *
     * @param \App\Models\Presupuesto $presupuesto
     * @return string|null
     */
    private function generarQrCodeParaPresupuesto(Presupuesto $presupuesto): ?string
    {
        $presupuesto->asegurarTokenPublico();
        $token = $presupuesto->token_publico;
        if (! $token) {
            return null;
        }

        $appUrl = config('app.frontend_url', config('app.url'));
        $urlWeb = rtrim($appUrl, '/') . '/pages/proveedor/presupuesto/preview/' . $presupuesto->id;
        // $urlWeb = rtrim($appUrl, '/') . '/public/presupuesto/' . $token;

        try {
            $renderer = new GDLibRenderer(200);
            $writer = new Writer($renderer);
            $qrPng = $writer->writeString($urlWeb);

            if ($qrPng !== '') {
                return 'data:image/png;base64,' . base64_encode($qrPng);
            }
        } catch (\Throwable $e) {
            $this->log('Error al generar QR para presupuesto', [
                'presupuesto_id' => $presupuesto->id,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Convierte el logo del proveedor a base64 si está disponible.
     * 
     * IMPORTANTE: DomPDF requiere GD para procesar PNG/GIF. Si GD no está disponible:
     * - Las imágenes JPEG funcionarán sin GD
     * - Las imágenes PNG/GIF retornarán string vacío (se usará fallback de texto)
     *
     * @param \App\Models\Proveedor|null $proveedor
     * @return string
     */
    private function convertirLogoProveedorABase64($proveedor): string
    {
        if (!$proveedor || empty($proveedor->logo)) {
            return '';
        }

        try {
            $logoPath = null;

            // Si el logo es una URL completa, no podemos convertirla sin GD
            if (filter_var($proveedor->logo, FILTER_VALIDATE_URL)) {
                return '';
            }

            // Si es una ruta relativa, construir la ruta completa
            if (strpos($proveedor->logo, '/') === 0) {
                $logoPath = public_path($proveedor->logo);
            } elseif (strpos($proveedor->logo, 'storage/') === 0) {
                $logoPath = public_path($proveedor->logo);
            } else {
                $logoPath = public_path('storage/' . $proveedor->logo);
            }

            if (!$logoPath || !file_exists($logoPath) || !is_readable($logoPath)) {
                return '';
            }

            // Detectar el tipo de imagen por extensión
            $extension = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));

            // Verificar si GD está disponible para PNG/GIF
            // JPEG puede procesarse sin GD
            if (in_array($extension, ['png', 'gif']) && !extension_loaded('gd')) {
                $this->log('GD no disponible para procesar logo PNG/GIF - usando fallback', [
                    'logo' => $proveedor->logo,
                    'extension' => $extension,
                ]);
                return '';
            }

            $imageData = @file_get_contents($logoPath);
            if ($imageData === false || empty($imageData)) {
                return '';
            }

            // Determinar MIME type
            $mimeType = 'image/png';
            if ($extension === 'jpg' || $extension === 'jpeg') {
                $mimeType = 'image/jpeg';
            } elseif ($extension === 'gif') {
                $mimeType = 'image/gif';
            }

            return 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
        } catch (\Exception $e) {
            $this->log('Error al convertir logo del proveedor a base64', [
                'error' => $e->getMessage(),
                'logo' => $proveedor->logo ?? 'N/A',
            ]);
        }

        return '';
    }

    /**
     * Datos pasados a la plantilla Blade del PDF (`PresupuestoPdfTemplate::viewName()`).
     *
     * Solo claves que consume la vista. El bloque «Dirigido a:» usa `receptor_lineas`: nombre → puesto → empresa
     * (misma vista que el preview; sin alias, teléfono ni correo), vía
     * {@see PresupuestoPdf::lineasReceptorPdfDesdeColumnasPresupuesto}.
     *
     * @return array<string, mixed>
     */
    private function datosVistaPdfPresupuestoGuardado(Presupuesto $presupuesto, Proveedor $proveedorEmisor, string $logoProveedorBase64, ?string $lugar, ?string $qrCodeDataUri): array
    {
        $enunciadosClasificados = $presupuesto->getEnunciadosClasificados();

        return [
            'proveedor' => $proveedorEmisor,
            'logo_proveedor_base64' => $logoProveedorBase64,
            'numero_presupuesto' => $presupuesto->numero_presupuesto,
            'uuid' => $presupuesto->uuid,
            'fecha_emision' => $presupuesto->fecha_emision,
            'lugar' => $lugar,
            'concepto_general' => $presupuesto->concepto_general,
            'con_iva' => $presupuesto->con_iva,
            'iva_porcentaje' => $presupuesto->iva_porcentaje,
            'term_cond_moneda' => $presupuesto->term_cond_moneda ?? 'MXN',
            'subtotal' => $presupuesto->subtotal,
            'porcentaje_descuento' => $presupuesto->porcentaje_descuento,
            'cantidad_descuento' => $presupuesto->cantidad_descuento,
            'iva_total' => $presupuesto->iva_total,
            'total' => $presupuesto->total,
            'receptor_lineas' => PresupuestoPdf::lineasReceptorPdfDesdeColumnasPresupuesto($presupuesto),
            'conceptos' => $presupuesto->conceptos->map(static function ($concepto) {
                return [
                    'tipo' => $concepto->tipo ?? PresupuestoConcepto::TIPO_CONCEPTO,
                    'descripcion' => $concepto->descripcion,
                    'cantidad' => $concepto->cantidad,
                    'unidad' => $concepto->unidad,
                    'precio_unitario' => $concepto->precio_unitario,
                    'precio_total' => $concepto->precio_total,
                ];
            })->values()->all(),
            'anexos' => PresupuestoPdf::anexosParaPlantillaPdf($presupuesto),
            'terminos_enunciados' => $enunciadosClasificados['terminos'],
            'validaciones_enunciados' => $enunciadosClasificados['validaciones'],
            'observaciones_enunciados' => $enunciadosClasificados['observaciones'],
            'qr_code' => $qrCodeDataUri,
        ];
    }

    /**
     * Calcula fecha de vencimiento desde term_cond_dias_vigencia.
     */
    private function calcularFechaVencimiento(Presupuesto $presupuesto): \Carbon\Carbon
    {
        $dias = $presupuesto->term_cond_dias_vigencia ?? 7;

        return $presupuesto->fecha_emision->copy()->addDays((int) $dias);
    }


    /**
     * Usuarios con cuenta en la plataforma, correo = cliente del presupuesto y proveedor activo distinto al emisor.
     *
     * @return Collection<int, User>
     */
    private function usuariosClienteProveedorRegistrado(Presupuesto $presupuesto): Collection
    {
        $email = strtolower(trim((string) $presupuesto->empresa_receptora_correo));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return collect();
        }

        $emisorId = (int) $presupuesto->proveedor_id;

        return User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereHas('proveedoresActivos', function ($q) use ($emisorId) {
                $q->where('proveedores.id', '!=', $emisorId);
            })
            ->get();
    }

    /**
     * Notifica en app (y FCM) a clientes que también son usuarios de otro proveedor.
     */
    private function notificarClienteProveedorRegistrado(Presupuesto $presupuesto, bool $esReenvio = false): void
    {
        $usuarios = $this->usuariosClienteProveedorRegistrado($presupuesto)->unique('id');
        foreach ($usuarios as $user) {
            if ($this->usuarioDebeExcluirseDeNotificacionReceptor($presupuesto, $user)) {
                continue;
            }
            if ($this->yaSeNotificoReceptorPresupuesto($user, (int) $presupuesto->id, $esReenvio)) {
                continue;
            }
            $user->notify(new PresupuestoRecibidoClienteProveedorNotification($presupuesto, $esReenvio));
        }
    }

    private function despacharCorreoPresupuesto(Presupuesto $presupuesto, bool $incluirInvitacion = false): void
    {
        $appUrl = config('app.frontend_url', config('app.url'));
        // $enlacePublico = $appUrl . '/public/presupuesto/' . $presupuesto->token_publico;
        $enlacePublico = $appUrl . '/pages/proveedor/presupuesto/preview/' . $presupuesto->id;
        $nombreReceptor = $presupuesto->empresa_receptora_nombre
            ?? $presupuesto->empresa_receptora_empresa
            ?? 'Cliente';

        Mail::to($presupuesto->empresa_receptora_correo)->send(
            new PresupuestoEnviadoMail($presupuesto, $enlacePublico, $nombreReceptor, $incluirInvitacion)
        );
    }

    private function despacharNotificacionesReceptor(Presupuesto $presupuesto, bool $esReenvio): void
    {
        if ($this->debeNotificarComoProveedorCatalogo($presupuesto)) {
            $this->notificarUsuariosProveedorReceptor($presupuesto, $esReenvio);
        } else {
            $this->notificarClienteProveedorRegistrado($presupuesto, $esReenvio);
        }
    }
}
