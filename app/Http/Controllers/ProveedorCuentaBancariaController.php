<?php

namespace App\Http\Controllers;

use App\Http\Requests\CuentaBancaria\CuentaBancariaStoreRequest;
use App\Http\Requests\CuentaBancaria\UpdateCuentaBancariaRequest;
use App\Http\Resources\CuentaBancaria\CuentaBancariaResource;
use App\Models\CuentaBancaria;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class ProveedorCuentaBancariaController extends Controller
{
  use ApiResponse;

  /**
   * Lista cuentas bancarias del proveedor con filtros, ordenamiento y paginación
   */
  public function index(Request $request, Proveedor $proveedor)
  {
    $fields = CuentaBancaria::getFilters();
    $filters = $request->only($fields);

    $sortBy = $request->input('sort_by', 'alias'); // Default sort by 'nombre_comercial'
    $order =  $request->input('order', 'asc');
    $perPage = $request->input('per_page', 10);

    $originalPaginator = $proveedor->cuentasBancarias()
      ->filter($filters)
      ->orderBy($sortBy, $order)
      ->paginate($perPage);

    $data = CuentaBancariaResource::collection($originalPaginator)->resolve();
    return $this->paginated($originalPaginator->setCollection(collect($data)));
  }

  /**
   * Muestra una cuenta bancaria específica del proveedor
   */
  public function show(Proveedor $proveedor, CuentaBancaria $cuenta)
  {
    return $this->success(
      new CuentaBancariaResource($cuenta),
      'Cuenta bancaria obtenida correctamente'
    );
  }

  /**
   * Crea una nueva cuenta bancaria del proveedor
   */
  public function store(CuentaBancariaStoreRequest $request, Proveedor $proveedor)
  {
    $cuenta = $proveedor->cuentasBancarias()->create($request->validated());
    return $this->success(
      new CuentaBancariaResource($cuenta),
      'Cuenta bancaria creada exitosamente',
      201
    );
  }

  /**
   * Actualiza una cuenta bancaria del proveedor
   */
  public function update(
    Proveedor $proveedor,
    CuentaBancaria $cuenta,
    UpdateCuentaBancariaRequest $request
  ): JsonResponse {
    if ($cuenta->proveedor_id !== $proveedor->id) { // Verificar que la cuenta pertenezca al proveedor
      return $this->error('La cuenta bancaria no pertenece al proveedor.', 403);
    }

    // Solo actualizar los campos enviados en el request
    $cuenta->fill($request->validated());
    $cuenta->save();

    return $this->success(
      new CuentaBancariaResource($cuenta),
      'Cuenta bancaria actualizada correctamente.'
    );
  }

  /**
   * Elimina una cuenta bancaria del proveedor
   */
  public function destroy(Proveedor $proveedor, CuentaBancaria $cuenta)
  {
    $cuenta->delete();
    return $this->success('Cuenta bancaria eliminada exitosamente');
  }
}
