<?php

namespace App\Http\Controllers;

use App\Models\TipoEmpresa;
use Illuminate\Http\Request;

/**
 * @OA\Get(
 *     path="/api/tipos-empresa",
 *     summary="Listar todos los tipos de empresa con filtros opcionales y paginación",
 *     operationId="listarTipoEmpresa",
 *     tags={"TipoEmpresa"},
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(name="nombre", in="query", description="Filtrar por nombre.", @OA\Schema(type="string")),
 *     @OA\Parameter(name="estatus", in="query", description="Filtrar por estatus.", @OA\Schema(type="string")),
 *     @OA\Response(response=200, description="Listado paginado de tipos de empresa")
 * )
 */
class TipoEmpresaController extends Controller
{
    /**
     * Listar todos los tipos de empresa con filtros opcionales y paginación
     */
    public function index(Request $request)
    {
        $filters = $request->only(['nombre', 'estatus']);
        $tipoEmpresas = TipoEmpresa::filter($filters)->paginate(10);
        return $this->paginated($tipoEmpresas);
    }

    /**
     * @OA\Post(
     *     path="/api/tipos-empresa",
     *     summary="Crear un tipo de empresa",
     *     operationId="crearTipoEmpresa",
     *     tags={"TipoEmpresa"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nombre"},
     *             @OA\Property(property="nombre", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Tipo de empresa creado")
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
        ]);

        $tipoEmpresa = TipoEmpresa::create([
            'nombre' => $request->nombre,
        ]);

        return $this->success($tipoEmpresa, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/tipos-empresa/{id}",
     *     summary="Obtener un tipo de empresa por ID",
     *     operationId="mostrarTipoEmpresa",
     *     tags={"TipoEmpresa"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Tipo de empresa encontrado")
     * )
     */
    public function show($id)
    {
        $tipoEmpresa = TipoEmpresa::findOrFail($id);
        return $this->success($tipoEmpresa);
    }

    /**
     * @OA\Put(
     *     path="/api/tipos-empresa/{id}",
     *     summary="Actualizar tipo de empresa",
     *     operationId="actualizarTipoEmpresa",
     *     tags={"TipoEmpresa"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="nombre", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Tipo de empresa actualizado")
     * )
     */
    public function update(Request $request, $id)
    {
        $tipoEmpresa = TipoEmpresa::findOrFail($id);

        $request->validate([
            'nombre' => 'sometimes|string|max:255',
        ]);

        $tipoEmpresa->update($request->only(['nombre']));

        return $this->success($tipoEmpresa);
    }

    /**
     * @OA\Delete(
     *     path="/api/tipos-empresa/{id}",
     *     summary="Eliminar tipo de empresa",
     *     operationId="eliminarTipoEmpresa",
     *     tags={"TipoEmpresa"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Tipo de empresa eliminado")
     * )
     */
    public function destroy($id)
    {
        $tipoEmpresa = TipoEmpresa::findOrFail($id);
        $tipoEmpresa->delete();

        return $this->success(null, 204);
    }
}
