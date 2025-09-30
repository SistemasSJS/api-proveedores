<?php

namespace App\Http\Controllers;


use App\Http\Resources\TipoEmpresaResource;
use App\Models\Proveedor;
use App\Models\TipoEmpresa;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Http\Request;

class ProveedorTipoEmpresaController extends Controller
{
    public function index(Request $request, Proveedor $proveedor)
    {
        $filters = $request->only(TipoEmpresa::getFilters());
        $originalPaginator = TipoEmpresa::filter($filters)->paginate(1000);
        $tipoEmpresas = TipoEmpresaResource::collection($originalPaginator)->resolve();
        return $this->paginated($originalPaginator->setCollection(collect($tipoEmpresas)));
    }

    public function store(Request $request, Proveedor $proveedor)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
        ]);

        $tipoEmpresa = TipoEmpresa::create([
            'nombre' => $request->nombre,
        ]);

        return $this->success($tipoEmpresa, 201);
    }

    public function show(Request $request, Proveedor $proveedor, $tipoEmpresaId)
    {
        $tipoEmpresa = TipoEmpresa::findOrFail($tipoEmpresaId);

        if ($tipoEmpresa->proveedor_id !== $proveedor->id) {
            throw new RelationNotFoundException('El producto no pertenece a este proveedor.', 403);
        }

        return $this->success($tipoEmpresa);
    }

    public function update(Request $request, Proveedor $proveedor, $tipoEmpresaId)
    {
        $tipoEmpresa = TipoEmpresa::findOrFail($tipoEmpresaId);

        if ($tipoEmpresa->proveedor_id !== $proveedor->id) {
            throw new RelationNotFoundException('El producto no pertenece a este proveedor.', 403);
        }

        $request->validate([
            'nombre' => 'sometimes|string|max:255',
        ]);

        $tipoEmpresa->update($request->only(['nombre']));

        return $this->success($tipoEmpresa);
    }

    public function destroy(Request $request, Proveedor $proveedor, $tipoEmpresaId)
    {
        $tipoEmpresa = TipoEmpresa::findOrFail($tipoEmpresaId);

        if ($tipoEmpresa->proveedor_id !== $proveedor->id) {
            throw new RelationNotFoundException('El producto no pertenece a este proveedor.', 403);
        }

        $tipoEmpresa->delete();

        return $this->success(null, 204);
    }
}
