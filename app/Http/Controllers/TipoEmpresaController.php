<?php

namespace App\Http\Controllers;

use App\Http\Resources\TipoEmpresaResource;
use App\Models\TipoEmpresa;
use Illuminate\Http\Request;

class TipoEmpresaController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(TipoEmpresa::getFilters());
        $originalPaginator = TipoEmpresa::filter($filters)->paginate(1000);
        $tipoEmpresas = TipoEmpresaResource::collection($originalPaginator)->resolve();
        return $this->paginated($originalPaginator->setCollection(collect($tipoEmpresas)));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
        ]);

        $tipoEmpresa = TipoEmpresa::create([
            'nombre' => $request->nombre,
        ]);

        return $this->success($tipoEmpresa, 201);
    }

    public function show($id)
    {
        $tipoEmpresa = TipoEmpresa::findOrFail($id);
        return $this->success($tipoEmpresa);
    }

    public function update(Request $request, $id)
    {
        $tipoEmpresa = TipoEmpresa::findOrFail($id);

        $request->validate([
            'nombre' => 'sometimes|string|max:255',
        ]);

        $tipoEmpresa->update($request->only(['nombre']));

        return $this->success($tipoEmpresa);
    }

    public function destroy($id)
    {
        $tipoEmpresa = TipoEmpresa::findOrFail($id);
        $tipoEmpresa->delete();

        return $this->success(null, 204);
    }
}
