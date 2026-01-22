<?php

namespace App\Http\Controllers;

use App\Models\PagoSPP;
use App\Models\SolicitudPago;
use Illuminate\Http\Request;
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
    /**
     * Lista de pagos con filtros y paginación.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 20);
            $filters = $request->except(['page', 'per_page']);

            $query = PagoSPP::query()
                ->with(['solicitudesPago', 'empresaConstrucc'])
                ->filter($filters)
                ->orderBy('fecha_pago', 'desc');

            $pagos = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $pagos->items(),
                'pagination' => [
                    'total' => $pagos->total(),
                    'per_page' => $pagos->perPage(),
                    'current_page' => $pagos->currentPage(),
                    'last_page' => $pagos->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error al listar pagos SPP', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los pagos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Muestra un pago específico con sus solicitudes de pago asociadas.
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
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

            return response()->json([
                'success' => true,
                'data' => $pago,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error al obtener pago SPP', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el pago',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Registra un nuevo pago y lo aplica a una o más solicitudes de pago.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
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
                throw new \Exception('El monto total aplicado excede el monto del pago');
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

            return response()->json([
                'success' => true,
                'message' => 'Pago registrado exitosamente',
                'data' => $pago,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear pago SPP', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el pago',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualiza un pago existente.
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
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

            return response()->json([
                'success' => true,
                'message' => 'Pago actualizado exitosamente',
                'data' => $pago,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar pago SPP', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el pago',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Elimina un pago.
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
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

            return response()->json([
                'success' => true,
                'message' => 'Pago eliminado exitosamente',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar pago SPP', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el pago',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Descarga el comprobante de pago.
     * 
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
     */
    public function descargarComprobante($id)
    {
        try {
            $pago = PagoSPP::findOrFail($id);

            if (!$pago->comprobante_pago || !Storage::disk('public')->exists($pago->comprobante_pago)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comprobante de pago no encontrado',
                ], 404);
            }

            return Storage::disk('public')->download($pago->comprobante_pago);
        } catch (\Exception $e) {
            Log::error('Error al descargar comprobante de pago', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al descargar el comprobante',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Agrega una solicitud de pago adicional a un pago existente.
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function agregarSolicitudPago(Request $request, $id)
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
                throw new \Exception("Monto insuficiente. Disponible: {$montoDisponible}");
            }

            $solicitudPago = SolicitudPago::findOrFail($validated['solicitud_pago_id']);

            // Verificar que no esté ya relacionada
            if ($pago->solicitudesPago()->where('solicitud_pago_id', $solicitudPago->id)->exists()) {
                throw new \Exception('Esta solicitud de pago ya está asociada a este pago');
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

            return response()->json([
                'success' => true,
                'message' => 'Solicitud de pago agregada exitosamente',
                'data' => $pago,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al agregar solicitud de pago', [
                'pago_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al agregar la solicitud de pago',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualiza la relación entre un pago y una solicitud de pago.
     * 
     * @param Request $request
     * @param int $pagoId
     * @param int $solicitudPagoId
     * @return \Illuminate\Http\JsonResponse
     */
    public function actualizarSolicitudPago(Request $request, $pagoId, $solicitudPagoId)
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
                throw new \Exception('Esta solicitud de pago no está asociada a este pago');
            }

            // Actualizar la relación
            $pago->solicitudesPago()->updateExistingPivot($solicitudPagoId, array_filter([
                'monto_aplicado' => $validated['monto_aplicado'] ?? null,
                'estado_pago' => $validated['estado_pago'] ?? null,
                'notas' => $validated['notas'] ?? null,
            ]));

            DB::commit();

            $pago->load(['solicitudesPago']);

            return response()->json([
                'success' => true,
                'message' => 'Relación actualizada exitosamente',
                'data' => $pago,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar relación pago-solicitud', [
                'pago_id' => $pagoId,
                'solicitud_pago_id' => $solicitudPagoId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la relación',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Elimina la relación entre un pago y una solicitud de pago.
     * 
     * @param int $pagoId
     * @param int $solicitudPagoId
     * @return \Illuminate\Http\JsonResponse
     */
    public function eliminarSolicitudPago($pagoId, $solicitudPagoId)
    {
        try {
            $pago = PagoSPP::findOrFail($pagoId);

            DB::beginTransaction();

            // Obtener el monto aplicado antes de desconectar
            $relacion = $pago->solicitudesPago()
                ->where('solicitud_pago_id', $solicitudPagoId)
                ->first();

            if (!$relacion) {
                throw new \Exception('Esta solicitud de pago no está asociada a este pago');
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

            return response()->json([
                'success' => true,
                'message' => 'Solicitud de pago desvinculada exitosamente',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar relación pago-solicitud', [
                'pago_id' => $pagoId,
                'solicitud_pago_id' => $solicitudPagoId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al desvincular la solicitud de pago',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtiene estadísticas de pagos.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function estadisticas(Request $request)
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

            return response()->json([
                'success' => true,
                'data' => [
                    'total_pagos' => $totalPagos,
                    'monto_total_pagado' => $montoTotalPagado,
                    'pagos_por_empresa' => $pagosPorEmpresa,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error al obtener estadísticas de pagos', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
