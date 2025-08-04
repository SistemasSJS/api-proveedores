<?php

namespace App\Http\Controllers;

use App\Enums\EstadoGeneral;
use App\Http\Requests\Categoria\CategoriaStoreRequest;
use App\Http\Resources\CategoriaResource;
use App\Models\Categoria;
use App\Models\Proveedor;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Http\Request;

class ProveedorCategoriaController extends Controller
{

    public function all(Request $request, Proveedor $proveedor)
    {
        // Obtener todas las categorías activas para el proveedor con las subcategorías (hijas)
        $data = Categoria::with(['children'])
            ->whereNull('parent_id')
            ->where('proveedor_id', $proveedor->id)
            ->where('estatus', EstadoGeneral::ACTIVO->value)
            ->paginate(10000);

        $categorias =    CategoriaResource::collection($data)->resolve();
        return $this->paginated($data->setCollection(collect($categorias)));
    }

    public function index(Request $request, Proveedor $proveedor)
    {
        $filters = $request->only(['nombre', 'estatus']);
        $data = Categoria::with(['children'])
            ->whereNull('parent_id')
            ->filter($filters)
            ->where('proveedor_id', $proveedor->id)
            ->paginate();
        return $this->paginated($data);
    }

    public function index_sub_categorias(Request $request, Proveedor $proveedor, $categoriaId)
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

    public function store(CategoriaStoreRequest $request, Proveedor $proveedor)
    {
        $categoriaPadreId = $request->input('categoria_padre_id');
        $nivel = 0;

        if ($categoriaPadreId) {
            $padre = Categoria::findOrFail($categoriaPadreId);
            $nivel = $padre->nivel + 1;

            if ($nivel > 2) {
                return $this->error('Solo se permiten hasta 2 niveles de subcategorías.', 422);
            }
        }

        $categoria = Categoria::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'parent_id' => $categoriaPadreId,
            'proveedor_id' => $proveedor->id,
            'nivel' => $nivel,
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
