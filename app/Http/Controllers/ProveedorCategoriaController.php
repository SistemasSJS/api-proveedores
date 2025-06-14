<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Proveedor;
use Illuminate\Http\Request;

class ProveedorCategoriaController extends Controller
{
    public function index(Request $request, Proveedor $proveedor)
    {
        $filters = $request->only(['nombre', 'estatus']);
        $data = $proveedor->categorias()->filter($filters)->paginate();
        return $this->paginated($data);
    }

    public function indexSubcategoria(Request $request, Proveedor $proveedor, $categoriaId)
    {
        $categoriaPadre = Categoria::findOrFail($categoriaId);
        $filters = $request->only(['nombre', 'estatus']);
        $data = $categoriaPadre->children()->filter($filters)->paginate();
        return $this->paginated($data);
    }

    public function store(Request $request, Proveedor $proveedor, $categoriaId)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
        ]);

        $categoria = Categoria::create([
            'nombre' => $request->nombre,
        ]);

        return $this->success($categoria, 201);
    }

    public function show(Request $request, Proveedor $proveedor, $categoriaId)
    {
        $categoria = Categoria::findOrFail($categoriaId);
        return $this->success($categoria);
    }

    public function update(Request $request, Proveedor $proveedor, $categoriaId)
    {
        $categoria = Categoria::findOrFail($categoriaId);

        $request->validate([
            'nombre' => 'sometimes|string|max:255',
        ]);

        $categoria->update($request->only(['nombre']));

        return $this->success($categoria);
    }

    public function destroy(Request $request, Proveedor $proveedor, $categoriaId)
    {
        $categoria = Categoria::findOrFail($categoriaId);
        $categoria->delete();

        return $this->success(null, 204);
    }
}
