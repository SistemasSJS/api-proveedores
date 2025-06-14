<?php

namespace App\Http\Controllers;

use App\Models\Marca;
use App\Models\Proveedor;
use Illuminate\Http\Request;

class ProveedorMarcaController extends Controller
{
    public function index(Request $request, Proveedor $proveedor)
    {
        $filters = $request->only(['nombre', 'estatus']);
        $data = $proveedor->marcas()->filter($filters)->paginate();
        return $this->paginated($data);
    }

    public function index_lineas_por_marca(Request $request, Proveedor $proveedor, $marcaId)
    {
        $marca = Marca::findOrFail($marcaId);
        $filters = $request->only(['nombre', 'estatus']);
        $data = $marca->lineas()->filter($filters)->paginate();
        return $this->paginated($data);
    }

    public function store(Request $request, Proveedor $proveedor, $marcaId)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
        ]);

        $marca = Marca::create([
            'nombre' => $request->nombre,
        ]);

        return $this->success($marca, 201);
    }

    public function show(Request $request, Proveedor $proveedor, $marcaId)
    {
        $marca = Marca::findOrFail($marcaId);
        return $this->success($marca);
    }

    public function update(Request $request, Proveedor $proveedor, $marcaId)
    {
        $marca = Marca::findOrFail($marcaId);

        $request->validate([
            'nombre' => 'sometimes|string|max:255',
        ]);

        $marca->update($request->only(['nombre']));

        return $this->success($marca);
    }

    public function destroy(Request $request, Proveedor $proveedor, $marcaId)
    {
        $marca = Marca::findOrFail($marcaId);
        $marca->delete();

        return $this->success(null, 204);
    }
}
