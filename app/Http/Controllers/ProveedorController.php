<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProveedorController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware(['auth:sanctum']);
    // }

    /**
     * @OA\Get(
     *     path="/api/proveedores/{id}",
     *     summary="Mostrar perfil del proveedor autenticado",
     *     description="Devuelve los datos de un proveedor específico solo si pertenece al usuario autenticado.",
     *     operationId="getProveedorById",
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
     *         response=403,
     *         description="No autorizado"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Proveedor no encontrado"
     *     )
     * )
     */
    public function show($id)
    {
        $proveedor = Proveedor::findOrFail($id);

        if ($proveedor->user_id !== Auth::id()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        return response()->json($proveedor);
    }

    /**
     * @OA\Put(
     *     path="/api/proveedores/{id}",
     *     summary="Actualizar perfil del proveedor",
     *     description="Actualiza los datos del proveedor si pertenece al usuario autenticado.",
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
     *         @OA\JsonContent(
     *             required={"razon_social", "nombre_comercial", "email"},
     *             @OA\Property(property="razon_social", type="string", example="Proveedor S.A."),
     *             @OA\Property(property="nombre_comercial", type="string", example="ProveedorTech"),
     *             @OA\Property(property="email", type="string", format="email", example="contacto@proveedor.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Proveedor actualizado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Proveedor actualizado correctamente"),
     *             @OA\Property(property="proveedor", ref="#/components/schemas/Proveedor")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="No autorizado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $proveedor = Proveedor::findOrFail($id);

        if ($proveedor->user_id !== Auth::id()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $request->validate([
            'razon_social' => [
                'required',
                'string',
                'max:255',
                Rule::unique('proveedores')->ignore($proveedor->id),
            ],
            'nombre_comercial' => [
                'required',
                'string',
                'max:255',
                Rule::unique('proveedores')->ignore($proveedor->id),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('proveedores')->ignore($proveedor->id),
            ],
        ]);

        $proveedor->update($request->all());

        return response()->json(['message' => 'Proveedor actualizado correctamente', 'proveedor' => $proveedor]);
    }


    /**
     * @OA\Get(
     *     path="/api/proveedores",
     *     summary="Listar proveedores del usuario autenticado",
     *     description="Devuelve una lista de todos los proveedores asociados al usuario autenticado.",
     *     operationId="listarProveedores",
     *     tags={"Proveedores"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de proveedores",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Proveedor"))
     *     )
     * )
     */
    public function index()
    {
        $proveedores = Proveedor::where('user_id', Auth::id())->get();
        return response()->json($proveedores);
    }

    /**
     * @OA\Post(
     *     path="/api/proveedores",
     *     summary="Crear un nuevo proveedor",
     *     description="Registra un proveedor asociado al usuario autenticado.",
     *     operationId="crearProveedor",
     *     tags={"Proveedores"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"razon_social", "nombre_comercial", "email"},
     *             @OA\Property(property="razon_social", type="string", example="Proveedor S.A."),
     *             @OA\Property(property="nombre_comercial", type="string", example="ProveedorTech"),
     *             @OA\Property(property="email", type="string", format="email", example="proveedor@email.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Proveedor creado exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/Proveedor")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'razon_social' => 'required|string|max:255|unique:proveedores',
            'nombre_comercial' => 'required|string|max:255|unique:proveedores',
            'email' => 'required|email|max:255|unique:proveedores',
        ]);

        $proveedor = Proveedor::create([
            'razon_social' => $request->razon_social,
            'nombre_comercial' => $request->nombre_comercial,
            'email' => $request->email,
            'user_id' => Auth::id(),
        ]);

        return response()->json($proveedor, 201);
    }

    /**
     * @OA\Delete(
     *     path="/api/proveedores/{id}",
     *     summary="Eliminar proveedor",
     *     description="Elimina un proveedor si pertenece al usuario autenticado.",
     *     operationId="eliminarProveedor",
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
     *         description="Proveedor eliminado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Proveedor eliminado correctamente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="No autorizado"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Proveedor no encontrado"
     *     )
     * )
     */
    public function destroy($id)
    {
        $proveedor = Proveedor::findOrFail($id);

        if ($proveedor->user_id !== Auth::id()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $proveedor->delete();

        return response()->json(['message' => 'Proveedor eliminado correctamente']);
    }
}
