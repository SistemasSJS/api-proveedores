<?php

namespace App\Http\Controllers;

use App\Models\Sucursal;
use App\Models\Proveedor;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Sucursales",
 *     description="Gestión de sucursales por proveedor"
 * )
 */
class SucursalController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/proveedores/{proveedorId}/sucursales",
     *     summary="Listar sucursales de un proveedor",
     *     tags={"Sucursales"},
     *     @OA\Parameter(name="proveedorId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Lista de sucursales", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Sucursal"))),
     *     @OA\Response(response=404, description="Proveedor no encontrado")
     * )
     */
    public function index($proveedorId)
    {
        $proveedor = Proveedor::findOrFail($proveedorId);
        return $this->success($proveedor->sucursales);
    }

    /**
     * @OA\Post(
     *     path="/api/proveedores/{proveedorId}/sucursales",
     *     summary="Crear una sucursal para un proveedor",
     *     tags={"Sucursales"},
     *     @OA\Parameter(name="proveedorId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nombre", "direccion"},
     *             @OA\Property(property="nombre", type="string", example="Sucursal Centro"),
     *             @OA\Property(property="direccion", type="string", example="Av. Principal 123"),
     *             @OA\Property(property="telefono", type="string", example="667-123-4567")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Sucursal creada", @OA\JsonContent(ref="#/components/schemas/Sucursal")),
     *     @OA\Response(response=404, description="Proveedor no encontrado")
     * )
     */
    public function store(Request $request, $proveedorId)
    {
        $proveedor = Proveedor::findOrFail($proveedorId);

        $request->validate([
            'nombre' => 'required|string|max:255',
            'direccion' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:20',
        ]);

        $sucursal = $proveedor->sucursales()->create($request->all());

        return $this->success($sucursal, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/proveedores/{proveedorId}/sucursales/{id}",
     *     summary="Obtener detalles de una sucursal",
     *     tags={"Sucursales"},
     *     @OA\Parameter(name="proveedorId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Datos de la sucursal", @OA\JsonContent(ref="#/components/schemas/Sucursal")),
     *     @OA\Response(response=404, description="Sucursal o proveedor no encontrado")
     * )
     */
    public function show($proveedorId, $id)
    {
        $proveedor = Proveedor::findOrFail($proveedorId);
        $sucursal = $proveedor->sucursales()->findOrFail($id);
        return $this->success($sucursal);
    }

    /**
     * @OA\Put(
     *     path="/api/proveedores/{proveedorId}/sucursales/{id}",
     *     summary="Actualizar una sucursal",
     *     tags={"Sucursales"},
     *     @OA\Parameter(name="proveedorId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="nombre", type="string", example="Sucursal Centro"),
     *             @OA\Property(property="direccion", type="string", example="Av. Principal 123"),
     *             @OA\Property(property="telefono", type="string", example="667-123-4567")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Sucursal actualizada"),
     *     @OA\Response(response=404, description="Sucursal o proveedor no encontrado")
     * )
     */
    public function update(Request $request, $proveedorId, $id)
    {
        $proveedor = Proveedor::findOrFail($proveedorId);
        $sucursal = $proveedor->sucursales()->findOrFail($id);

        $sucursal->update($request->all());

        return $this->success(['message' => 'Sucursal actualizada correctamente']);
    }

    /**
     * @OA\Delete(
     *     path="/api/proveedores/{proveedorId}/sucursales/{id}",
     *     summary="Eliminar una sucursal",
     *     tags={"Sucursales"},
     *     @OA\Parameter(name="proveedorId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Sucursal eliminada correctamente"),
     *     @OA\Response(response=404, description="Sucursal o proveedor no encontrado")
     * )
     */
    public function destroy($proveedorId, $id)
    {
        $proveedor = Proveedor::findOrFail($proveedorId);
        $sucursal = $proveedor->sucursales()->findOrFail($id);

        $sucursal->delete();

        return $this->success(['message' => 'Sucursal eliminada correctamente']);
    }
}
