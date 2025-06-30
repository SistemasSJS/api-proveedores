<?php

namespace App\Http\Controllers;

use App\Exceptions\Api\Crud\ResourceNotFoundException;
use App\Models\Catalogo;
use App\Models\Proveedor;
use App\Http\Requests\Catalogo\CatalogoStoreRequest;
use App\Http\Requests\Catalogo\CatalogoUpdateRequest;
use App\Http\Resources\CatalogoResource;
use App\Http\Resources\ProductoResource;
use App\Models\Producto;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;

class CatalogoController extends Controller
{
    use ApiResponse;

    public function index(Request $request, Proveedor $proveedor)
    {
        $filters = $request->only(Catalogo::getFilters());
        $sortBy = $request->input('sort_by', 'nombre');
        $order = $request->input('order', 'asc');
        $perPage = $request->input('per_page', 10);

        $query = Catalogo::with(Catalogo::eagerLodable())
            ->filter($filters)
            ->where('proveedor_id', $proveedor->id);

        $originalPaginator = $query->orderBy($sortBy, $order)->paginate($perPage);
        $data = CatalogoResource::collection($originalPaginator)->resolve();

        return $this->paginated($originalPaginator->setCollection(collect($data)));
    }

    public function store(CatalogoStoreRequest $request, Proveedor $proveedor)
    {
        $data = $request->validated();
        $data['proveedor_id'] = $proveedor->id;

        $catalogo = Catalogo::create($data);

        return $this->success(new CatalogoResource($catalogo), 201);
    }

    public function show(Request $request, Proveedor $proveedor, $id)
    {
        $catalogo = Catalogo::with(Catalogo::eagerLodable())->findOrFail($id);

        if ($catalogo->proveedor_id !== $proveedor->id) {
            abort(403, 'No autorizado para ver este catálogo.');
        }

        return $this->success(new CatalogoResource($catalogo));
    }

    public function update(CatalogoUpdateRequest $request, Proveedor $proveedor, $id)
    {
        $catalogo = Catalogo::findOrFail($id);

        if ($catalogo->proveedor_id !== $proveedor->id) {
            abort(403, 'No autorizado para actualizar este catálogo.');
        }

        $catalogo->update($request->validated());

        return $this->success(new CatalogoResource($catalogo));
    }

    public function destroy(Request $request, Proveedor $proveedor, $id)
    {
        $catalogo = Catalogo::findOrFail($id);

        if ($catalogo->proveedor_id !== $proveedor->id) {
            abort(403, 'No autorizado para eliminar este catálogo.');
        }

        $catalogo->delete();

        return $this->success(null, 204);
    }

    public function upload_perfil(Request $request, Proveedor $proveedor, $id)
    {
        $catalogo = Catalogo::findOrFail($id);

        if ($catalogo->proveedor_id !== $proveedor->id) {
            abort(403, 'No autorizado para eliminar este catálogo.');
        }

        $catalogo->delete();

        return $this->success(null, 204);
    }
}
