<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use App\Exceptions\Api\Crud\ResourceNotFoundException;
use Illuminate\Validation\Rule;

/**
 * @OA\Tag(
 *     name="Proveedores",
 *     description="CRUD de proveedores"
 * )
 */
class ProveedorController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/proveedores",
     *     summary="Listar proveedores",
     *     operationId="getProveedores",
     *     tags={"Proveedores"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="nombre_comercial", in="query", description="Filtrar por nombre comercial del proveedor.", @OA\Schema(type="string")),
     *     @OA\Parameter(name="razon_social", in="query", description="Filtrar por razón social", @OA\Schema(type="string")),
     *     @OA\Parameter(name="rfc", in="query", description="Filtrar por el RFC.", @OA\Schema(type="string")),
     *     @OA\Parameter(name="direccion_fiscal", in="query", description="Filtrar por direccion fiscal registrada.", @OA\Schema(type="string")),
     *     @OA\Parameter(name="estado", in="query", description="Filtrar por estado.", @OA\Schema(type="string")),
     *     @OA\Parameter(name="municipio", in="query", description="Filtrar por municipio.", @OA\Schema(type="string")),
     *     @OA\Parameter(name="fecha_registro", in="query", description="Filtrar por fecha del registro del proveedor.", @OA\Schema(type="string")),
     *     @OA\Parameter(name="estatus", in="query", description="Filtrar por el estatus del proveedor.", @OA\Schema(type="string")),
     *     @OA\Parameter(name="notas", in="query", description="Filtrar por notas agregadas al proveedor.", @OA\Schema(type="string")),
     *     @OA\Parameter(name="email", in="query", description="Filtrar por email.", @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de proveedores",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Proveedor"))
     *     )
     * )
     */
    public function index(Request $request)
    {
        $fields = Proveedor::getFilters();
        $filters = $request->only($fields);
        $proveedores = Proveedor::with(Proveedor::eagerLodable())->filter($filters)->paginate(10);
        return $this->paginated($proveedores);
    }

    /**
     * @OA\Post(
     *     path="/api/proveedores",
     *     summary="Crear proveedor",
     *     operationId="storeProveedor",
     *     tags={"Proveedores"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Proveedor")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Proveedor creado exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/Proveedor")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Entrada inválida",
     *         @OA\JsonContent(ref="#/components/schemas/InvalidInputException")
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre_comercial' => 'required|string|max:255',
            'razon_social' => 'required|string|max:255',
            'rfc' => [
                'required',
                'string',
                'min:12',
                'max:13',
                Rule::unique('proveedores'),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('proveedores'),
            ],
            'telefono' => 'required|string|max:15',
            'estado' => 'required|string|max:255',
            'municipio' => 'required|string|max:255',
            'codigo_postal' => 'required|string|max:10',
            'contacto_nombre' => 'required|string|max:255',
            'contacto_telefono' => 'required|string|max:15',
            'contacto_email' => 'required|email|max:255',
        ]);


        $proveedor = Proveedor::create($request->all());

        return $this->success($proveedor->load(Proveedor::eagerLodable()), 201);
    }

    /**
     * @OA\Get(
     *     path="/api/proveedores/{id}",
     *     summary="Obtener un proveedor específico",
     *     operationId="getProveedor",
     *     tags={"Proveedores"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del proveedor",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Proveedor encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/Proveedor")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Proveedor no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ResourceNotFoundException")
     *     )
     * )
     */
    public function show($id)
    {
        $proveedor = Proveedor::with(Proveedor::eagerLodable())->find($id);
        if (!$proveedor) {
            throw new ResourceNotFoundException("Proveedor no encontrado.");
        }

        return $this->success($proveedor);
    }

    /**
     * @OA\Put(
     *     path="/api/proveedores/{id}",
     *     summary="Actualizar proveedor",
     *     operationId="updateProveedor",
     *     tags={"Proveedores"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del proveedor",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Proveedor")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Proveedor actualizado exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/Proveedor")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Entrada inválida"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Proveedor no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ResourceNotFoundException")
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $proveedor = Proveedor::find($id);
        if (!$proveedor) {
            throw new ResourceNotFoundException("Proveedor no encontrado.");
        }

        $request->validate([
            'nombre_comercial' => 'required|string|max:255',
            'razon_social' => 'required|string|max:255',
            'rfc' => [
                'required',
                'string',
                'min:12',
                'max:13',
                Rule::unique('proveedores')->ignore($id),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('proveedores')->ignore($id),
            ],
            'telefono' => 'required|string|max:15',
            'estado' => 'required|string|max:255',
            'municipio' => 'required|string|max:255',
            'codigo_postal' => 'required|string|max:10',
            'contacto_nombre' => 'required|string|max:255',
            'contacto_telefono' => 'required|string|max:15',
            'contacto_email' => 'required|email|max:255',
        ]);


        $proveedor->update($request->all());

        return $this->success($proveedor->load(Proveedor::eagerLodable()), 201);
    }

    /**
     * @OA\Delete(
     *     path="/api/proveedores/{id}",
     *     summary="Eliminar proveedor",
     *     operationId="deleteProveedor",
     *     tags={"Proveedores"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del proveedor",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Proveedor eliminado exitosamente"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Proveedor no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ResourceNotFoundException")
     *     )
     * )
     */
    public function destroy($id)
    {
        $proveedor = Proveedor::find($id);
        if (!$proveedor) {
            throw new ResourceNotFoundException("Proveedor no encontrado.");
        }

        // FIXME: Manejo de los estatus de los recursos:
        //      - uso de tablas en la BD
        //      - Enumerate 
        //      - a la voluntad de dios y la buena memoria (Estado actual)
        $proveedor->update(
            [
                ['estatus' => 'baja']
            ]
        );

        // $proveedor->delete();

        return $this->success(null, 204);
    }
}
