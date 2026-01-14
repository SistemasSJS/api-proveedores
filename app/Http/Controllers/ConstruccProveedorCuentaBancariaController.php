<?php

namespace App\Http\Controllers;

use App\Enums\EstadoCuentaBancaria;
use App\Http\Requests\Construcc\ConstruccCuentaBancariaStoreRequest;
use App\Http\Requests\Construcc\ConstruccCuentaBancariaUpdateRequest;
use App\Http\Resources\CuentaBancaria\CuentaBancariaResource;
use App\Models\CuentaBancaria;
use App\Models\Proveedor;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ConstruccProveedorCuentaBancariaController extends Controller
{
    use ApiResponse;

    /**
     * Middleware: Validar que el proveedor sea tipo_alta = 2
     */
    private function validarProveedorConstruccion(Proveedor $proveedor): ?JsonResponse
    {
        if ($proveedor->tipo_alta !== 2) {
            return $this->error(
                'Solo se pueden gestionar cuentas bancarias de proveedores registrados por usuarios construcción (tipo_alta = 2).',
                null,
                403
            );
        }

        return null;
    }

    /**
     * Lista cuentas bancarias del proveedor con tipo_alta = 2
     */
    public function index(Request $request, Proveedor $proveedor): JsonResponse
    {
        // Validar tipo_alta = 2
        if ($error = $this->validarProveedorConstruccion($proveedor)) {
            return $error;
        }

        try {
            $fields = CuentaBancaria::getFilters();
            $filters = $request->only($fields);

            $sortBy = $request->input('sort_by', 'alias');
            $order = $request->input('order', 'asc');

            $cuentas = $proveedor->cuentasBancarias()
                ->filter($filters)
                ->orderBy($sortBy, $order)
                ->get();

            return $this->success(
                CuentaBancariaResource::collection($cuentas),
                'Operación exitosa.'
            );
        } catch (\Exception $e) {
            Log::error('Error al listar cuentas bancarias: ' . $e->getMessage(), [
                'proveedor_id' => $proveedor->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'Error al obtener las cuentas bancarias',
                null,
                500
            );
        }
    }

    /**
     * Muestra una cuenta bancaria específica del proveedor
     */
    public function show(Proveedor $proveedor, CuentaBancaria $cuenta): JsonResponse
    {
        // Validar tipo_alta = 2
        if ($error = $this->validarProveedorConstruccion($proveedor)) {
            return $error;
        }

        try {
            // Verificar que la cuenta pertenece al proveedor
            if ($cuenta->proveedor_id !== $proveedor->id) {
                return $this->error(
                    'La cuenta bancaria no pertenece a este proveedor.',
                    null,
                    403
                );
            }

            // Agregar información adicional
            $cuenta->solicitudes_pago_asociadas = $cuenta->solicitudesPago()->count();

            return $this->success(
                new CuentaBancariaResource($cuenta),
                'Operación exitosa.'
            );
        } catch (\Exception $e) {
            Log::error('Error al obtener cuenta bancaria: ' . $e->getMessage(), [
                'proveedor_id' => $proveedor->id,
                'cuenta_id' => $cuenta->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'Error al obtener la cuenta bancaria',
                null,
                500
            );
        }
    }

    /**
     * Crea una nueva cuenta bancaria para el proveedor
     */
    public function store(ConstruccCuentaBancariaStoreRequest $request, Proveedor $proveedor): JsonResponse
    {
        // Validar tipo_alta = 2
        if ($error = $this->validarProveedorConstruccion($proveedor)) {
            return $error;
        }

        try {
            $data = $request->validated();

            // Verificar si es la primera cuenta bancaria del proveedor
            $esPrimeraCuenta = !$proveedor->cuentasBancarias()->exists();

            if ($esPrimeraCuenta) {
                $data['preferida'] = true;
            } else {
                // Si no es la primera, por defecto no es preferida
                $data['preferida'] = false;
            }

            $data['estatus'] = EstadoCuentaBancaria::ACTIVA->value;

            $cuenta = $proveedor->cuentasBancarias()->create($data);

            return $this->success(
                new CuentaBancariaResource($cuenta),
                'Cuenta bancaria creada exitosamente.',
                201
            );
        } catch (\Exception $e) {
            Log::error('Error al crear cuenta bancaria: ' . $e->getMessage(), [
                'proveedor_id' => $proveedor->id,
                'data' => $request->validated(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'Error al crear la cuenta bancaria',
                null,
                500
            );
        }
    }

    /**
     * Actualiza una cuenta bancaria del proveedor
     */
    public function update(
        Proveedor $proveedor,
        CuentaBancaria $cuenta,
        ConstruccCuentaBancariaUpdateRequest $request
    ): JsonResponse {
        // Validar tipo_alta = 2
        if ($error = $this->validarProveedorConstruccion($proveedor)) {
            return $error;
        }

        try {
            // Verificar que la cuenta pertenece al proveedor
            if ($cuenta->proveedor_id !== $proveedor->id) {
                return $this->error(
                    'La cuenta bancaria no pertenece a este proveedor.',
                    null,
                    403
                );
            }

            // Si se marca como preferida, desmarcamos las demás
            if ($request->filled('preferida') && $request->preferida) {
                $proveedor->cuentasBancarias()
                    ->where('id', '!=', $cuenta->id)
                    ->update(['preferida' => false]);
            }

            $cuenta->fill($request->validated());
            $cuenta->save();

            return $this->success(
                new CuentaBancariaResource($cuenta),
                'Cuenta bancaria actualizada exitosamente.'
            );
        } catch (\Exception $e) {
            Log::error('Error al actualizar cuenta bancaria: ' . $e->getMessage(), [
                'proveedor_id' => $proveedor->id,
                'cuenta_id' => $cuenta->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'Error al actualizar la cuenta bancaria',
                null,
                500
            );
        }
    }

    /**
     * Elimina una cuenta bancaria del proveedor
     */
    public function destroy(Proveedor $proveedor, CuentaBancaria $cuenta): JsonResponse
    {
        // Validar tipo_alta = 2
        if ($error = $this->validarProveedorConstruccion($proveedor)) {
            return $error;
        }

        try {
            // Verificar que la cuenta pertenece al proveedor
            if ($cuenta->proveedor_id !== $proveedor->id) {
                return $this->error(
                    'La cuenta bancaria no pertenece a este proveedor.',
                    null,
                    403
                );
            }

            // Validar que no sea la única cuenta
            $totalCuentas = $proveedor->cuentasBancarias()->count();
            if ($totalCuentas <= 1) {
                return $this->error(
                    'No se puede eliminar la única cuenta bancaria del proveedor.',
                    null,
                    422
                );
            }

            // Validar que no tenga solicitudes de pago asociadas
            $totalSolicitudes = $cuenta->solicitudesPago()->count();
            if ($totalSolicitudes > 0) {
                return $this->error(
                    "No se puede eliminar la cuenta bancaria porque tiene {$totalSolicitudes} solicitudes de pago asociadas.",
                    ['total_solicitudes' => $totalSolicitudes],
                    422
                );
            }

            $cuenta->delete();

            return $this->success(
                null,
                'Cuenta bancaria eliminada exitosamente.'
            );
        } catch (\Exception $e) {
            Log::error('Error al eliminar cuenta bancaria: ' . $e->getMessage(), [
                'proveedor_id' => $proveedor->id,
                'cuenta_id' => $cuenta->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'Error al eliminar la cuenta bancaria',
                null,
                500
            );
        }
    }

    /**
     * Establece una cuenta bancaria como favorita
     */
    public function setFavorita(Proveedor $proveedor, CuentaBancaria $cuenta): JsonResponse
    {
        // Validar tipo_alta = 2
        if ($error = $this->validarProveedorConstruccion($proveedor)) {
            return $error;
        }

        try {
            // Verificar que la cuenta pertenece al proveedor
            if ($cuenta->proveedor_id !== $proveedor->id) {
                return $this->error(
                    'La cuenta bancaria no pertenece a este proveedor.',
                    null,
                    403
                );
            }

            // Desmarcar todas las cuentas como preferidas
            $proveedor->cuentasBancarias()->update(['preferida' => false]);

            // Marcar la cuenta seleccionada como preferida
            $cuenta->preferida = true;
            $cuenta->save();

            return $this->success(
                new CuentaBancariaResource($cuenta),
                'Cuenta bancaria marcada como favorita exitosamente.'
            );
        } catch (\Exception $e) {
            Log::error('Error al establecer cuenta favorita: ' . $e->getMessage(), [
                'proveedor_id' => $proveedor->id,
                'cuenta_id' => $cuenta->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'Error al establecer la cuenta como favorita',
                null,
                500
            );
        }
    }
}
