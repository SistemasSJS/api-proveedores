<?php

namespace App\Http\Controllers;

use App\Enums\EstadoGeneral;

use App\Http\Requests\UnidadMedidaRequest;
use App\Http\Requests\UnidadMedidaStoreRequest;
use App\Http\Requests\UnidadMedidaUpdateRequest;
use App\Models\UnidadMedida;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\RelationNotFoundException;

class ProveedorUnidadMedidaController extends Controller
{
    public function all(Request $request, Proveedor $proveedor)
    {
        $originalPaginator = UnidadMedida::where('proveedor_id', $proveedor->id)
            ->where('estatus', EstadoGeneral::ACTIVO->value)
            ->paginate(10000);

        return $this->paginated($originalPaginator);
    }
    public function index(Request $request, Proveedor $proveedor)
    {
        $filters = $request->only(UnidadMedida::getFilters());
        $originalPaginator = UnidadMedida::filter($filters)
            ->where('proveedor_id', $proveedor->id)
            ->paginate();

        return $this->paginated($originalPaginator);
    }

    public function store(UnidadMedidaStoreRequest $request, Proveedor $proveedor)
    {
        $unidad = UnidadMedida::create([
            'clave' => $request->clave,
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'activo' => $request->activo ?? true,
            'proveedor_id' => $proveedor->id,
        ]);

        return $this->success($unidad, 201);
    }

    public function show(Request $request, Proveedor $proveedor, $unidadId)
    {
        $unidad = UnidadMedida::findOrFail($unidadId);

        if ($unidad->proveedor_id !== $proveedor->id) {
            throw new RelationNotFoundException('La unidad de medida no pertenece a este proveedor.', 403);
        }

        return $this->success($unidad);
    }

    public function update(UnidadMedidaUpdateRequest $request, Proveedor $proveedor, $unidadId)
    {
        $unidad = UnidadMedida::findOrFail($unidadId);

        if ($unidad->proveedor_id !== $proveedor->id) {
            throw new RelationNotFoundException('La unidad de medida no pertenece a este proveedor.', 403);
        }

        $unidad->update($request->only(['clave', 'nombre', 'descripcion', 'activo']));

        return $this->success($unidad);
    }

    public function destroy(Request $request, Proveedor $proveedor, $unidadId)
    {
        $unidad = UnidadMedida::findOrFail($unidadId);

        if ($unidad->proveedor_id !== $proveedor->id) {
            throw new RelationNotFoundException('La unidad de medida no pertenece a este proveedor.', 403);
        }

        $unidad->delete();

        return $this->success(null, 204);
    }
}
