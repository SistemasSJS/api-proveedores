<?php

namespace App\Http\Controllers;

use App\Models\{Proveedor, Producto, Sucursal};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProveedorController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/proveedores",
     *     summary="Listar todos los proveedores con filtros opcionales y paginación",
     *     operationId="listarProveedores",
     *     tags={"Proveedor"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="razon_social", in="query", description="Filtrar por razon social", @OA\Schema(type="string")),
     *     @OA\Parameter(name="nombre_comercial", in="query", description="Filtrar por nombre comercial", @OA\Schema(type="string")),
     *     @OA\Parameter(name="email", in="query", description="Filtrar por email", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Listado paginado de proveedores")
     * )
     */
    public function index(Request $request)
    {
        $filters = $request->only(['razon_social','nombre_comercial','email']);
        $proveedores= Proveedor::filter($filters)->paginate(10);
        return $this->paginated($proveedores);
    }

    /**
     * @OA\Post(
     *     path="/api/proveedores",
     *     summary="Crear un proveedor",
     *     operationId="crearProveedor",
     *     tags={"Proveedor"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nombre", "rfc"},
     *             @OA\Property(property="nombre", type="string"),
     *             @OA\Property(property="rfc", type="string"),
     *             @OA\Property(property="telefono", type="string"),
     *             @OA\Property(property="logo", type="file", description="Logo del proveedor (jpg, png)")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Proveedor creado")
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'rfc' => 'required|string|max:13|unique:proveedores,rfc',
            'telefono' => 'nullable|string|max:20',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // validar imagen
        ]);

        $data = $request->only(['nombre', 'rfc', 'telefono']);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $proveedor = Proveedor::create($data);

        return response()->json($proveedor, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/proveedores/{id}",
     *     summary="Obtener un proveedor por ID",
     *     operationId="mostrarProveedor",
     *     tags={"Proveedor"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Proveedor encontrado")
     * )
     */
    public function show($id)
    {
        $proveedor = Proveedor::with(['sucursales', 'productos'])->findOrFail($id);

        return response()->json($proveedor);
    }

    /**
     * @OA\Put(
     *     path="/api/proveedores/{id}",
     *     summary="Actualizar proveedor",
     *     operationId="actualizarProveedor",
     *     tags={"Proveedor"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="nombre", type="string"),
     *             @OA\Property(property="rfc", type="string"),
     *             @OA\Property(property="telefono", type="string"),
     *             @OA\Property(property="logo", type="file", description="Logo del proveedor (jpg, png)")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Proveedor actualizado")
     * )
     */
    public function update(Request $request, $id)
    {
        $proveedor = Proveedor::findOrFail($id);

        $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'rfc' => 'sometimes|string|max:13|unique:proveedores,rfc,' . $proveedor->id,
            'telefono' => 'nullable|string|max:20',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = $request->only(['nombre', 'rfc', 'telefono']);

        if ($request->hasFile('logo')) {
            // Eliminar logo anterior si existe
            if ($proveedor->logo) {
                Storage::disk('public')->delete($proveedor->logo);
            }
            // Guardar nuevo logo
            $data['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $proveedor->update($data);

        return response()->json($proveedor);
    }

    /**
     * @OA\Delete(
     *     path="/api/proveedores/{id}",
     *     summary="Eliminar proveedor",
     *     operationId="eliminarProveedor",
     *     tags={"Proveedor"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Proveedor eliminado")
     * )
     */
    public function destroy($id)
    {
        $proveedor = Proveedor::findOrFail($id);
        $proveedor->delete();

        return response()->json(null, 204);
    }

    /**
     * @OA\Get(
     *     path="/api/proveedores/{id}/productos",
     *     summary="Listar productos de un proveedor con filtros",
     *     operationId="productosPorProveedor",
     *     tags={"Proveedor"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="nombre", in="query", description="Filtrar por nombre de producto", @OA\Schema(type="string")),
     *     @OA\Parameter(name="categoria", in="query", description="Filtrar por categoría", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Listado de productos del proveedor")
     * )
     */
    public function productosPorProveedor(Request $request, $proveedorId)
    {
        $query = Producto::with(["unidad_medida", "grupo", "imagenes"])->where('proveedor_id', $proveedorId);

        if ($request->has('nombre')) {
            $query->where('nombre', 'like', "%{$request->nombre}%");
        }
        if ($request->has('categoria')) {
            $query->where('categoria', $request->categoria);
        }

        return response()->json($query->paginate(10));
    }

    /**
     * @OA\Get(
     *     path="/api/proveedores/{id}/sucursales",
     *     summary="Listar sucursales de un proveedor con filtros",
     *     operationId="sucursalesPorProveedor",
     *     tags={"Proveedor"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="nombre", in="query", description="Filtrar por nombre de sucursal", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Listado de sucursales del proveedor")
     * )
     */
    public function sucursalesPorProveedor(Request $request, $proveedorId)
    {
        $query = Sucursal::where('proveedor_id', $proveedorId);

        if ($request->has('nombre')) {
            $query->where('nombre', 'like', "%{$request->nombre}%");
        }
        if ($request->has('categoria')) {
            $query->where('categoria', $request->categoria);
        }

        return response()->json($query->paginate(10));
    }
}
