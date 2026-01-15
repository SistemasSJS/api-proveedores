<?php

namespace App\Http\Controllers;

use App\Enums\EstadoCuentaBancaria;
use App\Enums\EstadoUsuario;
use App\Http\Requests\Construcc\ConstruccProveedorStoreRequest;
use App\Http\Requests\Construcc\ConstruccProveedorUpdateRequest;
use App\Http\Resources\Construcc\ConstruccProveedorDetalleResource;
use App\Models\CuentaBancaria;
use App\Models\Proveedor;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConstruccProveedorController extends Controller
{
    use ApiResponse;

    /**
     * Lista proveedores con tipo_alta = 2 (registrados por usuarios construcciรณn)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $fields = Proveedor::getFilters();
            $filters = $request->only($fields);

            $sortBy = $request->input('sort_by', 'nombre_comercial');
            $order = $request->input('order', 'asc');
            $perPage = $request->input('per_page', 10);

            $query = Proveedor::query()
                ->where('tipo_alta', 2) // Solo proveedores de construcciรณn
                ->with(['cuentasBancarias', 'empresasConstrucc'])
                ->withCount('solicitudesPago')
                ->filter($filters)
                ->orderBy($sortBy, $order);

            $originalPaginator = $query->paginate($perPage);

            $data = ConstruccProveedorDetalleResource::collection($originalPaginator)->resolve();

            return $this->paginated($originalPaginator->setCollection(collect($data)));
        } catch (\Exception $e) {
            Log::error('Error al listar proveedores construcciรณn: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'Error al obtener el listado de proveedores',
                null,
                500
            );
        }
    }

    /**
     * Obtiene el detalle de un proveedor especรญfico con tipo_alta = 2
     */
    public function show(Proveedor $proveedor): JsonResponse
    {
        try {
            // Validar que sea un proveedor de construcciรณn
            if ($proveedor->tipo_alta !== 2) {
                return $this->error(
                    'Proveedor no encontrado o no corresponde a tipo_alta = 2.',
                    null,
                    404
                );
            }

            // Cargar relaciones
            $proveedor->load(['cuentasBancarias', 'empresasConstrucc']);

            // Agregar estadรญsticas de solicitudes de pago
            $estadisticas = [
                'total_solicitudes_pago' => $proveedor->solicitudesPago()->count(),
                'total_sp_pendientes' => $proveedor->solicitudesPago()->where('estado_solicitud', 'pendiente')->count(),
                'total_sp_autorizadas' => $proveedor->solicitudesPago()->where('estado_solicitud', 'autorizada')->count(),
                'total_sp_pagadas' => $proveedor->solicitudesPago()->where('estado_solicitud', 'pagada')->count(),
                'monto_total_solicitado' => $proveedor->solicitudesPago()->sum('monto_total'),
                'monto_total_pagado' => $proveedor->solicitudesPago()->where('pago_completo', true)->sum('monto_total'),
            ];

            $proveedor->estadisticas = $estadisticas;

            return $this->success(
                new ConstruccProveedorDetalleResource($proveedor),
                'Operaciรณn exitosa.'
            );
        } catch (\Exception $e) {
            Log::error('Error al obtener detalle del proveedor: ' . $e->getMessage(), [
                'proveedor_id' => $proveedor->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'Error al obtener el detalle del proveedor',
                null,
                500
            );
        }
    }

    /**
     * Crea un nuevo proveedor con tipo_alta = 2 y su cuenta bancaria inicial
     */
    public function store(ConstruccProveedorStoreRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();

            // PASO 1: Crear proveedor con tipo_alta = 2
            // La validación de duplicados (unique) se hace automáticamente en el FormRequest
            $proveedor = Proveedor::create([
                'razon_social' => $data['razon_social'],
                'nombre_comercial' => $data['nombre_comercial'] ?? $data['razon_social'],
                'rfc' => strtoupper($data['rfc']),
                'email' => $data['email'],
                'telefono' => $data['telefono'],
                'celular' => $data['celular'] ?? null,
                'tipo_alta' => 2, // UserConstrucc
                'is_proveedor_sp' => true,
                'is_proveedor_catalogo' => false,
                'perfil_empresa_completo' => false,
                'cambiar_pass_default' => false,
                'user_construcc_alta' => $data['usuario_id'],
                'estatus' => EstadoUsuario::REGISTRADO->value,
            ]);

            // Nota: El campo celular no existe en la tabla proveedores
            // Solo existe el campo 'telefono' que puede almacenar cualquier número

            // PASO 2: Crear cuenta bancaria inicial (siempre preferida = true)
            $cuentaData = $data['cuenta'];
            $cuentaData['preferida'] = true; // Primera cuenta siempre es preferida
            // $cuentaData['estatus'] = EstadoCuentaBancaria::ACTIVA->value;

            $cuenta = $proveedor->cuentasBancarias()->create($cuentaData);

            // PASO 2.1: Asociar proveedor con empresa construcción
            $proveedor->empresasConstrucc()->attach($data['empresa_construcc_id'], [
                'usuario_construcc_id' => $data['usuario_id'],
                'usuario_construcc_nombre' => $data['usuario_nombre'],
            ]);

            DB::commit();

            return $this->success(
                [
                    'proveedor' => [
                        'id' => $proveedor->id,
                        'nombre_comercial' => $proveedor->nombre_comercial,
                        'razon_social' => $proveedor->razon_social,
                        'rfc' => $proveedor->rfc,
                        'email' => $proveedor->email,
                        'telefono' => $proveedor->telefono,
                        'estatus' => $proveedor->estatus,
                        'tipo_alta' => $proveedor->tipo_alta,
                        'created_at' => $proveedor->created_at->format('Y-m-d H:i:s'),
                    ],
                    'cuenta_bancaria' => [
                        'id' => $cuenta->id,
                        'alias' => $cuenta->alias,
                        'banco_nombre' => $cuenta->banco_nombre,
                        'preferida' => $cuenta->preferida,
                        'referencia' => $cuenta->referencia,
                        'sucursal' => $cuenta->sucursal,
                        'swift' => $cuenta->swift,
                        'tipo_cuenta' => $cuenta->tipo_cuenta,
                        'campo_dependiente' => $cuenta->campo_dependiente,
                        'titular_cuenta' => $cuenta->titular_cuenta,
                        'banco_clave' => $cuenta->banco_clave,
                        'banco_nombre' => $cuenta->banco_nombre,
                        'tipo_cuenta' => $cuenta->tipo_cuenta,
                        // 'estatus' => $cuenta->estatus,
                    ],
                ],
                'Proveedor creado exitosamente con cuenta bancaria.',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al crear proveedor construcciรณn: ' . $e->getMessage(), [
                'data' => $request->except(['cuenta']),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'Error al crear el proveedor',
                null,
                500
            );
        }
    }

    /**
     * Actualiza un proveedor con tipo_alta = 2
     * Solo pueden actualizar: Directores (DG, DT, DA, PC) o el usuario que lo registrรณ
     */
    public function update(
        ConstruccProveedorUpdateRequest $request,
        Proveedor $proveedor
    ): JsonResponse {
        DB::beginTransaction();

        try {
            // Validar tipo_alta
            if ($proveedor->tipo_alta !== 2) {
                return $this->error(
                    'Solo se pueden actualizar proveedores registrados por usuarios construcción (tipo_alta = 2).',
                    null,
                    403
                );
            }

            // Validar autorización
            $nivelesDirectores = [1, 2, 3, 5]; // DG, DT, DA, PC
            $esDirector = in_array($request->nivel_id, $nivelesDirectores);

            $pivotData = $proveedor->empresasConstrucc()->first();
            $usuarioCreadorId = $pivotData?->pivot->usuario_construcc_id;
            $esCreador = $request->usuario_id == $usuarioCreadorId;

            if (!$esDirector && !$esCreador) {
                return $this->error(
                    'No tiene permisos para actualizar este proveedor.',
                    null,
                    403
                );
            }

            // Actualizar proveedor
            $dataToUpdate = $request->only([
                'razon_social',
                'rfc',
                'nombre_comercial',
                'email',
                'telefono',
                'celular',
            ]);

            if (isset($dataToUpdate['rfc'])) {
                $dataToUpdate['rfc'] = strtoupper($dataToUpdate['rfc']);
            }

            $proveedor->update($dataToUpdate);

            // Procesar cuentas bancarias (si vienen)
            if ($request->filled('cuentas_bancarias')) {
                $cuentas = $request->cuentas_bancarias;

                $hayPreferida = collect($cuentas)->contains(
                    fn($c) => isset($c['preferida']) && $c['preferida']
                );

                if ($hayPreferida) {
                    $proveedor->cuentasBancarias()->update(['preferida' => false]);
                }

                foreach ($cuentas as $cuentaData) {
                    if (!empty($cuentaData['id'])) {
                        $proveedor->cuentasBancarias()
                            ->where('id', $cuentaData['id'])
                            ->update(collect($cuentaData)->except('id')->toArray());
                    } else {
                        $proveedor->cuentasBancarias()->create($cuentaData);
                    }
                }
            }

            DB::commit();

            // Recargar relaciones
            $proveedor->load(['cuentasBancarias', 'empresasConstrucc']);

            // 🔵 RESPUESTA EXACTAMENTE IGUAL A LA QUE TENÍAS
            return $this->success(
                [
                    'proveedor' => [
                        'id' => $proveedor->id,
                        'nombre_comercial' => $proveedor->nombre_comercial,
                        'razon_social' => $proveedor->razon_social,
                        'rfc' => $proveedor->rfc,
                        'email' => $proveedor->email,
                        'telefono' => $proveedor->telefono,
                        'celular' => $proveedor->celular,
                        'estatus' => $proveedor->estatus,
                        'tipo_alta' => $proveedor->tipo_alta,
                        'created_at' => $proveedor->created_at->format('Y-m-d H:i:s'),
                        'updated_at' => $proveedor->updated_at->format('Y-m-d H:i:s'),
                    ],
                    'cuenta_bancaria' => $proveedor->cuentasBancarias
                        ->where('preferida', true)
                        ->map(function ($cuenta) {
                            return [
                                'id' => $cuenta->id,
                                'alias' => $cuenta->alias,
                                'banco_clave' => $cuenta->banco_clave,
                                'banco_nombre' => $cuenta->banco_nombre,
                                'tipo_cuenta' => $cuenta->tipo_cuenta,
                                'campo_dependiente' => $cuenta->campo_dependiente,
                                'titular_cuenta' => $cuenta->titular_cuenta,
                                'preferida' => (bool) $cuenta->preferida,
                                'referencia' => $cuenta->referencia,
                                'sucursal' => $cuenta->sucursal,
                                'swift' => $cuenta->swift,
                            ];
                        })
                        ->first(),
                ],
                'Proveedor actualizado exitosamente.'
            );
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al actualizar proveedor construcción', [
                'proveedor_id' => $proveedor->id,
                'error' => $e->getMessage(),
            ]);

            return $this->error(
                'Error al actualizar el proveedor',
                null,
                500
            );
        }
    }

    /**
     * Marca un proveedor como dado de baja (soft delete)
     * Solo pueden eliminar: Directores (DG, DT, DA, PC) o el usuario que lo registrรณ
     */
    public function destroy(Proveedor $proveedor, Request $request): JsonResponse
    {
        try {
            // Validar que sea un proveedor de construcciรณn
            if ($proveedor->tipo_alta !== 2) {
                return $this->error(
                    'Solo se pueden eliminar proveedores registrados por usuarios construcciรณn (tipo_alta = 2).',
                    null,
                    403
                );
            }

            // Validar datos de autorizaciรณn
            $request->validate([
                'usuario_id' => 'required|integer',
                'nivel_id' => 'required|integer|min:0|max:6',
                'motivo_baja' => 'nullable|string|max:500',
            ]);

            // Validar autorizaciรณn
            $nivelesDirectores = [1, 2, 3, 5]; // DG, DT, DA, PC
            $esDirector = in_array($request->nivel_id, $nivelesDirectores);

            // Obtener el usuario que registrรณ el proveedor desde la tabla pivot
            $pivotData = $proveedor->empresasConstrucc()->first();
            $usuarioCreadorId = $pivotData ? $pivotData->pivot->usuario_construcc_id : null;
            $esCreador = $request->usuario_id == $usuarioCreadorId;

            if (!$esDirector && !$esCreador) {
                return $this->error(
                    'No tiene permisos para eliminar este proveedor. Solo directores (DG, DT, DA, PC) o el usuario que lo registrรณ pueden eliminarlo.',
                    null,
                    403
                );
            }

            // Cambiar estatus a inactivo (soft delete)
            $proveedor->estatus  = EstadoUsuario::SUSPENDIDO->value;
            $proveedor->notas = $request->motivo_baja ?? 'Proveedor dado de baja';
            $proveedor->save();

            return $this->success(
                [
                    'id' => $proveedor->id,
                    'razon_social' => $proveedor->razon_social,
                    'estatus' => $proveedor->estatus,
                    'fecha_baja' => $proveedor->updated_at->format('Y-m-d H:i:s'),
                    'motivo_baja' => $request->motivo_baja,
                ],
                'Proveedor dado de baja exitosamente.'
            );
        } catch (\Exception $e) {
            Log::error('Error al eliminar proveedor construcciรณn: ' . $e->getMessage(), [
                'proveedor_id' => $proveedor->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'Error al dar de baja el proveedor',
                null,
                500
            );
        }
    }
}
