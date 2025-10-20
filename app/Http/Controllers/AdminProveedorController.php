<?php

namespace App\Http\Controllers;

use App\Exceptions\Api\Crud\ResourceNotFoundException;
use App\Http\Requests\Admin\AdminProveedorStoreRequest;
use App\Http\Requests\Proveedor\ProveedorUpdateRequest;
use App\Http\Resources\Admin\AdminProveedorAcordeonResource;
use App\Http\Resources\ProveedorResource;
use App\Models\Proveedor;
use Illuminate\Http\Request;

class AdminProveedorController extends Controller
{
    /**
     * Lista los proveedores con filtros, ordenamiento y paginación.
     */
    public function index(Request $request)
    {
        $filters = $request->only(Proveedor::getFilters());
        $sortBy = $request->input('sort_by', 'nombre_comercial');
        $order = $request->input('order', 'asc');
        $perPage = $request->input('per_page', 10);

        $originalPaginator = Proveedor::with(Proveedor::eagerLodable())
            ->filter($filters)
            ->orderBy($sortBy, $order)
            ->paginate($perPage);

        $data = ProveedorResource::collection($originalPaginator)->resolve();

        return $this->paginated($originalPaginator->setCollection(collect($data)));
    }

    /**
     * Crea un nuevo proveedor.
     *
     * @param  ProveedorStoreRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(AdminProveedorStoreRequest $request)
    {
        $validated = $request->validated();

        // Crear proveedor
        $proveedor = Proveedor::create($validated);

        return $this->success(
            new ProveedorResource($proveedor->fresh(Proveedor::eagerLodable())),
            'Proveedor creado con éxito.',
            201
        );
    }

    /**
     * Muestra los datos de un proveedor específico.
     */
    public function show(Request $request, Proveedor $proveedor)
    {
        return $this->success(new ProveedorResource($proveedor));
    }

    /**
     * Actualiza la información de un proveedor.
     */
    public function update(ProveedorUpdateRequest $request, Proveedor $proveedor)
    {
        $validated = $request->validated();
        $proveedor->update($validated);
        $proveedor = $proveedor->fresh(Proveedor::eagerLodable());

        return $this->success(new ProveedorResource($proveedor), 'Proveedor actualizado con éxito.', 200);
    }

    /**
     * Marca un proveedor como baja (eliminación lógica).
     */
    public function destroy($id)
    {
        $proveedor = Proveedor::find($id);
        if (! $proveedor) {
            throw new ResourceNotFoundException('Proveedor no encontrado.');
        }
        $proveedor->update(['estatus' => 'baja']);

        return $this->success(null, 204);
    }

    /**
     * Obtiene los proveedores con sus categorías raíz, subcategorías y conteo de productos.
     */
    public function proveedoresConCategoriasConSubcatCountProductos(Request $request)
    {
        $proveedores = Proveedor::with([
            'categorias' => function ($query) {
                $query->whereNull('parent_id') // solo categorías raíz
                    ->with([
                        'children' => function ($subquery) {
                            $subquery->withCount('productos');
                        },
                    ])
                    ->withCount('productos');
            },
        ])
            ->withCount('productos') // total de productos por proveedor
            ->get();

        return $this->success(
            AdminProveedorAcordeonResource::collection($proveedores),
            'Listado de proveedores con sus categorías, subcategorías y contador de productos.'
        );
    }
}
