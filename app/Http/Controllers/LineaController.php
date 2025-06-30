<?php

namespace App\Http\Controllers;

use App\Http\Resources\LineaResource;
use App\Models\Linea;
use Illuminate\Http\Request;

class LineaController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(Linea::getFilters());
        $originalPaginator = Linea::filter($filters)->paginate();
        $lineas = LineaResource::collection($originalPaginator)->resolve();
        return $this->paginated($originalPaginator->setCollection(collect($lineas)));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
        ]);

        $linea = Linea::create([
            'nombre' => $request->nombre,
        ]);

        return $this->success($linea, 201);
    }

    public function show($id)
    {
        $linea = Linea::findOrFail($id);
        return $this->success($linea);
    }

    public function update(Request $request, $id)
    {
        $linea = Linea::findOrFail($id);

        $request->validate([
            'nombre' => 'sometimes|string|max:255',
        ]);

        $linea->update($request->only(['nombre']));

        return $this->success($linea);
    }

    public function destroy($id)
    {
        $linea = Linea::findOrFail($id);
        $linea->delete();

        return $this->success(null, 204);
    }
}
