<?php

namespace App\Http\Controllers;

use App\Enums\EstadoSP;
use App\Http\Requests\Construcc\ConstruccPagosSPPRegistrarPago;
use App\Http\Resources\Construcc\ConstruccPagosProveedorResource;
use App\Http\Resources\Construcc\ConstruccProveedorSppResource;
use App\Http\Resources\Construcc\ConstruccPagosSPPResource;
use App\Models\PagoSPP;
use App\Models\Proveedor;
use App\Models\SolicitudPago;;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Controlador para gestionar los pagos de solicitudes de pago (SPP).
 * Maneja la relación muchos a muchos entre pagos y solicitudes de pago.
 */
class ConstruccPagosSPPController extends Controller
{
    use ApiResponse;

    /**
     * Lista de pagos con filtros y paginación.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // $perPage = $request->get('per_page', 20);
            // $filters = $request->except(['page', 'per_page']);

            $filters = $request->only(PagoSPP::getFilters());
            $sortBy = $request->input('sort_by', 'fecha_pago');
            $order = $request->input('order', 'desc');
            $perPage = $request->input('per_page', 10000);

            $query = PagoSPP::query()
                ->with(PagoSPP::eagerLodable())
                ->filter($filters)
                ->orderBy($sortBy, $order);



            $pagos = $query->paginate($perPage);

            return $this->success([
                'pagos' => $pagos->items(),
                'pagination' => [
                    'total' => $pagos->total(),
                    'per_page' => $pagos->perPage(),
                    'current_page' => $pagos->currentPage(),
                    'last_page' => $pagos->lastPage(),
                ],
            ], 'Pagos obtenidos exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error al listar pagos SPP', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'No se pudieron obtener los pagos. Por favor, intente nuevamente.',
                null,
                500
            );
        }
    }

    /**
     * Muestra un pago específico con sus solicitudes de pago asociadas.
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        try {
            $pago = PagoSPP::with([
                'solicitudesPago' => function ($query) {
                    $query->withPivot(['monto_aplicado', 'estado_pago', 'notas', 'fecha_aplicacion']);
                },
                'solicitudesPago.proveedor',
                'solicitudesPago.empresaConstrucc',
                'empresaConstrucc',
            ])->findOrFail($id);

            return $this->success(
                $pago,
                'Pago obtenido exitosamente.'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Pago no encontrado', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'El pago no existe.',
                null,
                404
            );
        } catch (\Exception $e) {
            Log::error('Error al obtener pago SPP', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'No se pudo obtener el detalle del pago. Por favor, intente nuevamente.',
                null,
                500
            );
        }
    }

    /**
     * Registra un nuevo pago y lo aplica a una o más solicitudes de pago.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validación
            $validated = $request->validate([
                // Datos del pago
                'comprobante_pago' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
                'fecha_pago' => 'required|date',
                'referencia_pago' => 'required|string|max:100',

                // Datos bancarios del pago (origen)
                'banco_pago' => 'nullable|string|max:100',
                'cuenta_origen' => 'nullable|string|max:50',
                'tipo_cuenta_origen' => 'nullable|string|max:50',
                'clabe_interbancaria_origen' => 'nullable|string|max:18',

                // Datos bancarios del proveedor (destino)
                'banco_destino' => 'nullable|string|max:100',
                'cuenta_destino' => 'nullable|string|max:50',
                'tipo_cuenta_destino' => 'nullable|string|max:50',
                'clabe_interbancaria_destino' => 'nullable|string|max:18',
                'titular_cuenta_destino' => 'nullable|string|max:255',

                // Montos
                'monto_total' => 'required|numeric|min:0.01',

                // Metadatos
                'observaciones' => 'nullable|string',
                'usuario_registro_id' => 'nullable|integer',
                'usuario_registro_nombre' => 'nullable|string|max:255',
                'empresa_construcc_id' => 'nullable|integer',

                // Solicitudes de pago a las que se aplica (obligatorio)
                'solicitudes_pago' => 'required|array|min:1',
                'solicitudes_pago.*.solicitud_pago_id' => 'required|integer|exists:solicitudes_pago,id',
                'solicitudes_pago.*.monto_aplicado' => 'required|numeric|min:0.01',
                'solicitudes_pago.*.estado_pago' => [
                    'required',
                    Rule::in(['aplicado', 'pendiente', 'rechazado', 'parcial', 'completado'])
                ],
                'solicitudes_pago.*.notas' => 'nullable|string',
            ], [
                'comprobante_pago.required' => 'El comprobante de pago es obligatorio.',
                'comprobante_pago.mimes' => 'El comprobante debe ser PDF, JPG o PNG.',
                'comprobante_pago.max' => 'El comprobante no debe superar los 10MB.',
                'fecha_pago.required' => 'La fecha de pago es obligatoria.',
                'referencia_pago.required' => 'La referencia de pago es obligatoria.',
                'monto_total.required' => 'El monto total es obligatorio.',
                'monto_total.min' => 'El monto debe ser mayor a cero.',
                'solicitudes_pago.required' => 'Debe especificar al menos una solicitud de pago.',
                'solicitudes_pago.*.solicitud_pago_id.exists' => 'Una o más solicitudes de pago no existen.',
            ]);

            DB::beginTransaction();

            // Guardar el comprobante de pago
            $comprobantePath = null;
            if ($request->hasFile('comprobante_pago')) {
                $comprobantePath = $request->file('comprobante_pago')->store(
                    'comprobantes_pago',
                    'public'
                );
            }

            // Crear el pago
            $pago = PagoSPP::create([
                'comprobante_pago' => $comprobantePath,
                'fecha_pago' => $validated['fecha_pago'],
                'fecha_registro' => now(),
                'referencia_pago' => $validated['referencia_pago'],
                'banco_pago' => $validated['banco_pago'] ?? null,
                'cuenta_origen' => $validated['cuenta_origen'] ?? null,
                'tipo_cuenta_origen' => $validated['tipo_cuenta_origen'] ?? null,
                'clabe_interbancaria_origen' => $validated['clabe_interbancaria_origen'] ?? null,
                'banco_destino' => $validated['banco_destino'] ?? null,
                'cuenta_destino' => $validated['cuenta_destino'] ?? null,
                'tipo_cuenta_destino' => $validated['tipo_cuenta_destino'] ?? null,
                'clabe_interbancaria_destino' => $validated['clabe_interbancaria_destino'] ?? null,
                'titular_cuenta_destino' => $validated['titular_cuenta_destino'] ?? null,
                'monto_total' => $validated['monto_total'],
                'observaciones' => $validated['observaciones'] ?? null,
                'usuario_registro_id' => $validated['usuario_registro_id'] ?? null,
                'usuario_registro_nombre' => $validated['usuario_registro_nombre'] ?? null,
                'empresa_construcc_id' => $validated['empresa_construcc_id'] ?? null,
            ]);

            // Verificar que el monto total aplicado no exceda el monto del pago
            $montoTotalAplicado = collect($validated['solicitudes_pago'])->sum('monto_aplicado');
            if ($montoTotalAplicado > $validated['monto_total']) {
                throw new \Exception('El monto total aplicado a las solicitudes excede el monto del pago registrado.');
            }

            // Aplicar el pago a las solicitudes de pago
            foreach ($validated['solicitudes_pago'] as $solicitudData) {
                $solicitudPago = SolicitudPago::findOrFail($solicitudData['solicitud_pago_id']);

                // Adjuntar la relación con los datos del pivot
                $pago->solicitudesPago()->attach($solicitudPago->id, [
                    'monto_aplicado' => $solicitudData['monto_aplicado'],
                    'estado_pago' => $solicitudData['estado_pago'],
                    'notas' => $solicitudData['notas'] ?? null,
                    'fecha_aplicacion' => now(),
                ]);

                // Actualizar los saldos de la solicitud de pago si el estado es aplicado
                if ($solicitudData['estado_pago'] === 'aplicado' || $solicitudData['estado_pago'] === 'completado') {
                    $solicitudPago->actualizarSaldos($solicitudData['monto_aplicado']);
                }
            }

            DB::commit();

            // Cargar las relaciones para la respuesta
            $pago->load(['solicitudesPago', 'empresaConstrucc']);

            Log::info('Pago registrado exitosamente', [
                'pago_id' => $pago->id,
                'monto_total' => $pago->monto_total,
                'cantidad_spp' => count($validated['solicitudes_pago']),
            ]);

            return $this->success(
                $pago,
                'Pago registrado y aplicado exitosamente.',
                201
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return $this->error(
                'Error de validación en los datos del pago.',
                $e->errors(),
                422
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            Log::error('Solicitud de pago no encontrada al registrar pago', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'Una o más solicitudes de pago no existen.',
                null,
                404
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear pago SPP', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'No se pudo registrar el pago. ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * Actualiza un pago existente.
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $pago = PagoSPP::findOrFail($id);

            // Validación
            $validated = $request->validate([
                'comprobante_pago' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
                'fecha_pago' => 'nullable|date',
                'referencia_pago' => 'nullable|string|max:100',
                'banco_pago' => 'nullable|string|max:100',
                'cuenta_origen' => 'nullable|string|max:50',
                'tipo_cuenta_origen' => 'nullable|string|max:50',
                'clabe_interbancaria_origen' => 'nullable|string|max:18',
                'banco_destino' => 'nullable|string|max:100',
                'cuenta_destino' => 'nullable|string|max:50',
                'tipo_cuenta_destino' => 'nullable|string|max:50',
                'clabe_interbancaria_destino' => 'nullable|string|max:18',
                'titular_cuenta_destino' => 'nullable|string|max:255',
                'monto_total' => 'nullable|numeric|min:0.01',
                'observaciones' => 'nullable|string',
            ]);

            DB::beginTransaction();

            // Actualizar comprobante si se proporciona uno nuevo
            if ($request->hasFile('comprobante_pago')) {
                // Eliminar el comprobante anterior
                if ($pago->comprobante_pago && Storage::disk('public')->exists($pago->comprobante_pago)) {
                    Storage::disk('public')->delete($pago->comprobante_pago);
                }

                // Guardar el nuevo comprobante
                $validated['comprobante_pago'] = $request->file('comprobante_pago')->store(
                    'comprobantes_pago',
                    'public'
                );
            }

            // Actualizar el pago
            $pago->update(array_filter($validated));

            DB::commit();

            $pago->load(['solicitudesPago', 'empresaConstrucc']);

            return $this->success(
                $pago,
                'Pago actualizado exitosamente.'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Pago no encontrado al actualizar', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'El pago no existe.',
                null,
                404
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar pago SPP', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'No se pudo actualizar el pago. Por favor, intente nuevamente.',
                null,
                500
            );
        }
    }

    /**
     * Elimina un pago.
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        try {
            $pago = PagoSPP::findOrFail($id);

            DB::beginTransaction();

            // Eliminar el archivo del comprobante
            if ($pago->comprobante_pago && Storage::disk('public')->exists($pago->comprobante_pago)) {
                Storage::disk('public')->delete($pago->comprobante_pago);
            }

            // Eliminar el pago (esto también eliminará las relaciones en la tabla pivot por cascade)
            $pago->delete();

            DB::commit();

            return $this->success(
                null,
                'Pago eliminado exitosamente.'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Pago no encontrado al eliminar', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'El pago no existe.',
                null,
                404
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar pago SPP', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'No se pudo eliminar el pago. Por favor, intente nuevamente.',
                null,
                500
            );
        }
    }

    /**
     * Descarga el comprobante de pago.
     * 
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|JsonResponse
     */
    public function descargarComprobante($id)
    {
        try {
            $pago = PagoSPP::findOrFail($id);

            if (!$pago->comprobante_pago || !Storage::disk('public')->exists($pago->comprobante_pago)) {
                return $this->error(
                    'El comprobante de pago no está disponible.',
                    null,
                    404
                );
            }

            return Storage::disk('public')->download($pago->comprobante_pago);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Pago no encontrado al descargar comprobante', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'El pago no existe.',
                null,
                404
            );
        } catch (\Exception $e) {
            Log::error('Error al descargar comprobante de pago', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'No se pudo descargar el comprobante. Por favor, intente nuevamente.',
                null,
                500
            );
        }
    }

    /**
     * Agrega una solicitud de pago adicional a un pago existente.
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function agregarSolicitudPago(Request $request, $id): JsonResponse
    {
        try {
            $pago = PagoSPP::findOrFail($id);

            // Validación
            $validated = $request->validate([
                'solicitud_pago_id' => 'required|integer|exists:solicitudes_pago,id',
                'monto_aplicado' => 'required|numeric|min:0.01',
                'estado_pago' => [
                    'required',
                    Rule::in(['aplicado', 'pendiente', 'rechazado', 'parcial', 'completado'])
                ],
                'notas' => 'nullable|string',
            ]);

            DB::beginTransaction();

            // Verificar que hay monto disponible
            $montoDisponible = $pago->montoDisponible();
            if ($montoDisponible < $validated['monto_aplicado']) {
                throw new \Exception("No hay monto suficiente disponible. Disponible: " . number_format($montoDisponible, 2));
            }

            $solicitudPago = SolicitudPago::findOrFail($validated['solicitud_pago_id']);

            // Verificar que no esté ya relacionada
            if ($pago->solicitudesPago()->where('solicitud_pago_id', $solicitudPago->id)->exists()) {
                throw new \Exception('Esta solicitud de pago ya está asociada a este pago.');
            }

            // Adjuntar la solicitud de pago
            $pago->solicitudesPago()->attach($solicitudPago->id, [
                'monto_aplicado' => $validated['monto_aplicado'],
                'estado_pago' => $validated['estado_pago'],
                'notas' => $validated['notas'] ?? null,
                'fecha_aplicacion' => now(),
            ]);

            // Actualizar saldos si el estado es aplicado
            if ($validated['estado_pago'] === 'aplicado' || $validated['estado_pago'] === 'completado') {
                $solicitudPago->actualizarSaldos($validated['monto_aplicado']);
            }

            DB::commit();

            $pago->load(['solicitudesPago']);

            return $this->success(
                $pago,
                'Solicitud de pago agregada exitosamente.'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            Log::error('Pago o solicitud de pago no encontrada', [
                'pago_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'El pago o la solicitud de pago no existe.',
                null,
                404
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al agregar solicitud de pago', [
                'pago_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'No se pudo agregar la solicitud de pago. ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * Actualiza la relación entre un pago y una solicitud de pago.
     * 
     * @param Request $request
     * @param int $pagoId
     * @param int $solicitudPagoId
     * @return JsonResponse
     */
    public function actualizarSolicitudPago(Request $request, $pagoId, $solicitudPagoId): JsonResponse
    {
        try {
            $pago = PagoSPP::findOrFail($pagoId);

            // Validación
            $validated = $request->validate([
                'monto_aplicado' => 'nullable|numeric|min:0.01',
                'estado_pago' => [
                    'nullable',
                    Rule::in(['aplicado', 'pendiente', 'rechazado', 'parcial', 'completado'])
                ],
                'notas' => 'nullable|string',
            ]);

            DB::beginTransaction();

            // Verificar que la relación existe
            $relacion = $pago->solicitudesPago()->where('solicitud_pago_id', $solicitudPagoId)->first();
            if (!$relacion) {
                throw new \Exception('Esta solicitud de pago no está asociada a este pago.');
            }

            // Actualizar la relación
            $pago->solicitudesPago()->updateExistingPivot($solicitudPagoId, array_filter([
                'monto_aplicado' => $validated['monto_aplicado'] ?? null,
                'estado_pago' => $validated['estado_pago'] ?? null,
                'notas' => $validated['notas'] ?? null,
            ]));

            DB::commit();

            $pago->load(['solicitudesPago']);

            return $this->success(
                $pago,
                'Relación actualizada exitosamente.'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            Log::error('Pago o solicitud de pago no encontrada', [
                'pago_id' => $pagoId,
                'solicitud_pago_id' => $solicitudPagoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'El pago o la solicitud de pago no existe.',
                null,
                404
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar relación pago-solicitud', [
                'pago_id' => $pagoId,
                'solicitud_pago_id' => $solicitudPagoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'No se pudo actualizar la relación. ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * Elimina la relación entre un pago y una solicitud de pago.
     * 
     * @param int $pagoId
     * @param int $solicitudPagoId
     * @return JsonResponse
     */
    public function eliminarSolicitudPago($pagoId, $solicitudPagoId): JsonResponse
    {
        try {
            $pago = PagoSPP::findOrFail($pagoId);

            DB::beginTransaction();

            // Obtener el monto aplicado antes de desconectar
            $relacion = $pago->solicitudesPago()
                ->where('solicitud_pago_id', $solicitudPagoId)
                ->first();

            if (!$relacion) {
                throw new \Exception('Esta solicitud de pago no está asociada a este pago.');
            }

            $montoAplicado = $relacion->pivot->monto_aplicado;

            // Desconectar la solicitud de pago
            $pago->solicitudesPago()->detach($solicitudPagoId);

            // Revertir el monto en la solicitud de pago
            $solicitudPago = SolicitudPago::findOrFail($solicitudPagoId);
            $solicitudPago->update([
                'monto_abonado' => max(0, $solicitudPago->monto_abonado - $montoAplicado),
                'saldo_pendiente' => $solicitudPago->saldo_pendiente + $montoAplicado,
                'pago_completo' => false,
            ]);

            DB::commit();

            return $this->success(
                null,
                'Solicitud de pago desvinculada exitosamente.'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            Log::error('Pago o solicitud de pago no encontrada al eliminar relación', [
                'pago_id' => $pagoId,
                'solicitud_pago_id' => $solicitudPagoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'El pago o la solicitud de pago no existe.',
                null,
                404
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar relación pago-solicitud', [
                'pago_id' => $pagoId,
                'solicitud_pago_id' => $solicitudPagoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'No se pudo desvincular la solicitud de pago. ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * Obtiene estadísticas de pagos.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function estadisticas(Request $request): JsonResponse
    {
        try {
            $empresaId = $request->get('empresa_construcc_id');

            $query = PagoSPP::query();

            if ($empresaId) {
                $query->where('empresa_construcc_id', $empresaId);
            }

            $totalPagos = $query->count();
            $montoTotalPagado = $query->sum('monto_total');
            $pagosPorEmpresa = PagoSPP::select('empresa_construcc_id')
                ->selectRaw('COUNT(*) as total_pagos')
                ->selectRaw('SUM(monto_total) as monto_total')
                ->groupBy('empresa_construcc_id')
                ->with('empresaConstrucc')
                ->get();

            return $this->success([
                'total_pagos' => $totalPagos,
                'monto_total_pagado' => (float) $montoTotalPagado,
                'pagos_por_empresa' => $pagosPorEmpresa,
            ], 'Estadísticas obtenidas exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error al obtener estadísticas de pagos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'No se pudieron obtener las estadísticas. Por favor, intente nuevamente.',
                null,
                500
            );
        }
    }

    /**
     *--------------------------------------------------------------------------
     * MÉTODOS PARA GESTIONAR SPP POR PROVEEDOR
     *--------------------------------------------------------------------------
     * Estos métodos permiten listar y consultar las SPP de un proveedor específico
     * y sus pagos asociados.
     */


    /**
     * listado de proveedores con informacion de las SPP activas.
     */
    public function indexProveedor(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(Proveedor::getFilters());
            $sortBy  = $request->input('sort_by', 'razon_social');
            $order   = $request->input('order', 'asc');
            $perPage = $request->input('per_page', 1000);

            $query = Proveedor::query()
                ->withCount([
                    'solicitudesPago as spp_autorizadas_count' => function ($q) {
                        $q->where('estado_solicitud', EstadoSP::AUTORIZADA);
                    }
                ])
                ->filter($filters)
                ->orderBy($sortBy, $order);

            $paginator = $query->paginate($perPage);

            return $this->paginated(
                $paginator->setCollection(
                    ConstruccPagosProveedorResource::collection($paginator)->collection
                )
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
     * GET /api/construcc/pagos-spp/proveedor/{proveedor}/spp
     */
    public function sppPorProveedor(Request $request, Proveedor $proveedor): JsonResponse
    {
        try {
            $filters = $request->only(SolicitudPago::getFilters());
            $sortBy  = $request->input('sort_by', 'created_at');
            $order   = $request->input('order', 'desc');
            $perPage = $request->input('per_page', 20);

            $query = SolicitudPago::query()
                ->where('proveedor_id', $proveedor->id)
                ->with(array_merge(
                    SolicitudPago::eagerLodable(),
                    [
                        // Pagos aplicados a la SPP
                        'pagos' => function ($q) {
                            $q->orderBy('pago_solicitud_pago.fecha_aplicacion', 'desc');
                        }
                    ]
                ))
                ->filter($filters)
                ->orderBy($sortBy, $order);

            $paginator = $query->paginate($perPage);

            return $this->paginated(
                $paginator->setCollection(
                    ConstruccPagosSPPResource::collection($paginator)->collection
                )
            );
        } catch (\Exception $e) {
            Log::error('Error al listar SPP del proveedor', [
                'proveedor_id' => $proveedor->id,
                'error'        => $e->getMessage(),
                'trace'        => $e->getTraceAsString(),
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
     * 
     * @param Proveedor $proveedor
     * @param SolicitudPago $spp
     * @return JsonResponse
     */
    public function showSppProveedor(Proveedor $proveedor, SolicitudPago $spp): JsonResponse
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

            $spp->load([
                'proveedor',
                'empresaConstrucc',
                'cuentasBancarias',
                'pagos' => function ($query) {
                    $query->with(['pagoSPP', 'solicitudPago'])->orderBy('fecha_aplicacion', 'desc');
                }
            ]);

            return $this->success(
                new ConstruccProveedorSppResource($spp),
                'Solicitud de pago obtenida exitosamente.'
            );
        } catch (\Exception $e) {
            Log::error('Error al obtener SPP del proveedor', [
                'proveedor_id' => $proveedor->id,
                'spp_id' => $spp->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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
                ->withPivot([
                    'monto_aplicado',
                    'estado_pago',
                    'notas',
                    'fecha_aplicacion'
                ])
                ->orderBy('pago_solicitud_pago.fecha_aplicacion', 'desc')
                ->get();

            return $this->success([
                'pagos' => $pagos,
                'solicitud_pago' => [
                    'id' => $spp->id,
                    'numero_folio_solicitud' => $spp->numero_folio_solicitud,
                    'monto_total' => (float) $spp->monto_total,
                    'monto_abonado' => (float) $spp->monto_abonado,
                    'saldo_pendiente' => (float) $spp->saldo_pendiente,
                    'pago_completo' => (bool) $spp->pago_completo,
                ],
            ], 'Pagos obtenidos exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error al listar pagos de SPP', [
                'proveedor_id' => $proveedor->id,
                'spp_id' => $spp->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'No se pudieron obtener los pagos. Por favor, intente nuevamente.',
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
    public function showPagoDeSpp(Proveedor $proveedor, SolicitudPago $spp, PagoSPP $pago): JsonResponse
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

            $pago->load(['empresaConstrucc']);

            // Obtener datos del pivot
            $pivotData = DB::connection('mysql5')
                ->table('pago_solicitud_pago')
                ->where('pago_spp_id', $pago->id)
                ->where('solicitud_pago_id', $spp->id)
                ->first();

            return $this->success([
                'pago' => $pago,
                'relacion' => [
                    'monto_aplicado' => (float) $pivotData->monto_aplicado,
                    'estado_pago' => $pivotData->estado_pago,
                    'notas' => $pivotData->notas,
                    'fecha_aplicacion' => $pivotData->fecha_aplicacion,
                ],
                'solicitud_pago' => [
                    'id' => $spp->id,
                    'numero_folio_solicitud' => $spp->numero_folio_solicitud,
                    'monto_total' => (float) $spp->monto_total,
                    'monto_abonado' => (float) $spp->monto_abonado,
                    'saldo_pendiente' => (float) $spp->saldo_pendiente,
                ],
            ], 'Detalle del pago obtenido exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error al obtener pago de SPP', [
                'proveedor_id' => $proveedor->id,
                'spp_id' => $spp->id,
                'pago_id' => $pago->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'No se pudo obtener el detalle del pago. Por favor, intente nuevamente.',
                null,
                500
            );
        }
    }

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

            return $this->error(
                'No se pudo guardar el comprobante. Por favor, intente nuevamente.',
                null,
                500
            );
        }
    }

    /**
     * Registra un pago y lo aplica a una o varias SPP de un proveedor.
     * Este endpoint permite crear un pago con comprobante y asociarlo a múltiples SPP.
     * 
     * POST /api/construcc/pagos-spp/proveedor/{proveedor}/pagos
     * 
     * @param Request $request
     * @param Proveedor $proveedor
     * @return JsonResponse
     */
    public function registrarPagoProveedor(ConstruccPagosSPPRegistrarPago $request, Proveedor $proveedor): JsonResponse
    {
        try {
            // ✅ Validación del FormRequest
            $validated = $request->validated();

            // ✅ VALIDACIÓN ANTES DE BD (sin throw)
            $montoTotalAplicado = collect($validated['solicitudes_pago'])
                ->sum('monto_aplicado');

            if ($montoTotalAplicado > $validated['monto_total']) {
                return $this->error(
                    'El monto total aplicado a las solicitudes excede el monto del pago registrado.',
                    [
                        'monto_total' => $validated['monto_total'],
                        'monto_aplicado' => $montoTotalAplicado,
                    ],
                    422
                );
            }

            DB::beginTransaction();

            // Guardar el comprobante de pago
            $file = $comprobantePath = $request->file('comprobante_pago');

            $file->store('comprobantes', 'private');

            // Crear el pago
            $pago = PagoSPP::create([
                'comprobante_pago' => $comprobantePath,
                'fecha_pago' => $validated['fecha_pago'],
                'fecha_registro' => now(),
                'referencia_pago' => $validated['referencia_pago'],
                'banco_pago' => $validated['banco_pago'] ?? null,
                'cuenta_origen' => $validated['cuenta_origen'] ?? null,
                'tipo_cuenta_origen' => $validated['tipo_cuenta_origen'] ?? null,
                'clabe_interbancaria_origen' => $validated['clabe_interbancaria_origen'] ?? null,
                'banco_destino' => $validated['banco_destino'] ?? null,
                'cuenta_destino' => $validated['cuenta_destino'] ?? null,
                'tipo_cuenta_destino' => $validated['tipo_cuenta_destino'] ?? null,
                'clabe_interbancaria_destino' => $validated['clabe_interbancaria_destino'] ?? null,
                'titular_cuenta_destino' => $validated['titular_cuenta_destino'] ?? null,
                'monto_total' => $validated['monto_total'],
                'observaciones' => $validated['observaciones'] ?? null,
                'usuario_registro_id' => $validated['usuario_registro_id'] ?? null,
                'usuario_registro_nombre' => $validated['usuario_registro_nombre'] ?? null,
                'empresa_construcc_id' => $validated['empresa_construcc_id'] ?? null,
            ]);

            // Aplicar el pago a las solicitudes
            foreach ($validated['solicitudes_pago'] as $solicitudData) {
                $solicitudPago = SolicitudPago::where('id', $solicitudData['solicitud_pago_id'])
                    ->where('proveedor_id', $proveedor->id)
                    ->firstOrFail();

                $pago->solicitudesPago()->attach($solicitudPago->id, [
                    'monto_aplicado' => $solicitudData['monto_aplicado'],
                    'estado_pago' => $solicitudData['estado_pago'],
                    'notas' => $solicitudData['notas'] ?? null,
                    'fecha_aplicacion' => now(),
                ]);

                if (in_array($solicitudData['estado_pago'], ['aplicado', 'completado'])) {
                    $solicitudPago->actualizarSaldos(
                        $solicitudData['monto_aplicado']
                    );
                }
            }

            DB::commit();

            $pago->load(['solicitudesPago', 'empresaConstrucc']);

            Log::info('Pago registrado exitosamente', [
                'proveedor_id' => $proveedor->id,
                'pago_id' => $pago->id,
                'monto_total' => $pago->monto_total,
                'cantidad_spp' => count($validated['solicitudes_pago']),
            ]);

            return $this->success(
                $pago,
                'Pago registrado y aplicado exitosamente.',
                201
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();

            return $this->error(
                'El proveedor o alguna solicitud de pago no existe.',
                null,
                404
            );
        } catch (\Exception $e) {
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
}
