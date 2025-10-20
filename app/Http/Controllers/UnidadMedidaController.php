<?php

namespace App\Http\Controllers;

use App\Http\Resources\UnidadMedidaResource;
use App\Models\UnidadMedida;
use Illuminate\Http\Request;

class UnidadMedidaController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(UnidadMedida::getFilters());
        $originalPaginator = UnidadMedida::filter($filters)->paginate(1000);
        $unidadMedida = UnidadMedidaResource::collection($originalPaginator)->resolve();

        return $this->paginated($originalPaginator->setCollection(collect($unidadMedida)));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
        ]);

        $unidadMedida = UnidadMedida::create([
            'nombre' => $request->nombre,
        ]);

        return $this->success($unidadMedida, 201);
    }

    public function show($id)
    {
        $unidadMedida = UnidadMedida::findOrFail($id);

        return $this->success($unidadMedida);
    }

    public function update(Request $request, $id)
    {
        $unidadMedida = UnidadMedida::findOrFail($id);

        $request->validate([
            'nombre' => 'sometimes|string|max:255',
        ]);

        $unidadMedida->update($request->only(['nombre']));

        return $this->success($unidadMedida);
    }

    public function destroy($id)
    {
        $unidadMedida = UnidadMedida::findOrFail($id);
        $unidadMedida->delete();

        return $this->success(null, 204);
    }
}
