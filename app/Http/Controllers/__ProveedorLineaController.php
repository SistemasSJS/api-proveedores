<?php

namespace App\Http\Controllers;

use App\Models\Linea;
use App\Models\Proveedor;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Http\Request;

class ProveedorLineaController extends Controller
{
    public function index(Request $request, Proveedor $proveedor)
    {
        $filters = $request->only(['nombre', 'estatus']);
        $data = $proveedor->lineas()->filter($filters)->paginate();
        return $this->paginated($data);
    }

    public function store(Request $request, Proveedor $proveedor, $lineaId)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
        ]);

        $linea = Linea::create([
            'nombre' => $request->nombre,
        ]);

        return $this->success($linea, 201);
    }

    public function show(Request $request, Proveedor $proveedor, $lineaId)
    {
        $linea = Linea::findOrFail($lineaId);
        return $this->success($linea);
    }

    public function update(Request $request, Proveedor $proveedor, $lineaId)
    {
        $linea = Linea::findOrFail($lineaId);

        $request->validate([
            'nombre' => 'sometimes|string|max:255',
        ]);

        $linea->update($request->only(['nombre']));

        return $this->success($linea);
    }

    public function destroy(Request $request, Proveedor $proveedor, $lineaId)
    {
        $linea = Linea::findOrFail($lineaId);
        $linea->delete();

        return $this->success(null, 204);
    }
}
