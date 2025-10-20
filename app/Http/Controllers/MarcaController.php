<?php

namespace App\Http\Controllers;

use App\Http\Resources\MarcaResource;
use App\Models\Marca;
use Illuminate\Http\Request;

class MarcaController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(Marca::getFilters());
        $originalPaginator = Marca::filter($filters)->paginate(1000);
        $marcas = MarcaResource::collection($originalPaginator)->resolve();

        return $this->paginated($originalPaginator->setCollection(collect($marcas)));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
        ]);

        $Marca = Marca::create([
            'nombre' => $request->nombre,
        ]);

        return $this->success($Marca, 201);
    }

    public function show($id)
    {
        $Marca = Marca::findOrFail($id);

        return $this->success($Marca);
    }

    public function update(Request $request, $id)
    {
        $Marca = Marca::findOrFail($id);

        $request->validate([
            'nombre' => 'sometimes|string|max:255',
        ]);

        $Marca->update($request->only(['nombre']));

        return $this->success($Marca);
    }

    public function destroy($id)
    {
        $Marca = Marca::findOrFail($id);
        $Marca->delete();

        return $this->success(null, 204);
    }
}
