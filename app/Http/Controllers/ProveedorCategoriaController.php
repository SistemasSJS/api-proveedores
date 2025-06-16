<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Proveedor;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Http\Request;

class ProveedorCategoriaController extends Controller
{


    public function index(Request $request, Proveedor $proveedor)
    {
        $filters = $request->only(['nombre', 'estatus']);
        // $data = $proveedor->categorias()->filter($filters)->paginate();
        $data = Categoria::with(['children'])
            ->filter($filters)
            ->where('proveedor_id', $proveedor->id)
            ->paginate();
        return $this->paginated($data);
    }


    public function inDex_sub_categorias(Request $request, Proveedor $proveedor, $categoriaId)
    {
        $categoriaPadre = Categoria::findOrFail($categoriaId);

        if ($categoriaPadre->proveedor_id !== $proveedor->id) {
            // return $this->error('El producto no pertenece a este proveedor.', 403);
            throw new RelationNotFoundException('El producto no pertenece a este proveedor.', 403);
        }

        $filters = $request->only(['nombre', 'estatus']);
        $data = $categoriaPadre->children()->filter($filters)->paginate();
        return $this->paginated($data);
    }


    public function store(Request $request, Proveedor $proveedor)
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
