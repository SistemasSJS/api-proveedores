<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Proveedor\ProveedorLineaStoreRequest;
use App\Http\Requests\Proveedor\ProveedorLineaUpdateRequest;
use App\Http\Resources\ProveedorLineaResource;
use App\Models\Linea;
use App\Models\Proveedor;
use App\Models\ProveedorLinea;
use Illuminate\Http\Request;

class ProveedorLineaController extends Controller
{
  public function index(Proveedor $proveedor)
  {
    $lineas = $proveedor->lineas()->latest()->get();
    return ProveedorLineaResource::collection($lineas);
  }

  public function store(ProveedorLineaStoreRequest $request, Proveedor $proveedor)
  {
    $linea = $proveedor->lineas()->create($request->validated());
    return new ProveedorLineaResource($linea);
  }

  public function show(Proveedor $proveedor,  $lineaId)
  {
    $this->authorizeLinea($proveedor, $lineaId);
    return new ProveedorLineaResource($lineaId);
  }

  public function update(ProveedorLineaUpdateRequest $request, Proveedor $proveedor,  $lineaId)
  {
    $this->authorizeLinea($proveedor, $lineaId);
    $lineaId->update($request->validated());
    return new ProveedorLineaResource($lineaId);
  }

  public function destroy(Proveedor $proveedor,  $lineaId)
  {
    $this->authorizeLinea($proveedor, $lineaId);
    $lineaId->delete();
    return response()->noContent();
  }

  protected function authorizeLinea(Proveedor $proveedor,  $lineaId)
  {
    $linea = Linea::findOrFail($lineaId);
    if ($linea->proveedor_id !== $proveedor->id) {
      abort(404);
    }
  }
}
