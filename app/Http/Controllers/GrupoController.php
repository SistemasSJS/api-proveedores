<?php

namespace App\Http\Controllers;

use App\Models\Grupo;
use Illuminate\Http\Request;

class GrupoController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/grupos",
     *     summary="Listar todos los grupos con filtros opcionales y paginación",
     *     operationId="listarGrupos",
     *     tags={"Grupo"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="nombre", in="query", description="Filtrar por nombre", @OA\Schema(type="string")),
     *     @OA\Parameter(name="estatus", in="query", description="Filtrar por estatus", @OA\Schema(type="string")),
     *     @OA\Parameter(name="page", in="query", description="Número de página", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Listado paginado de grupos")
     * )
     */
    public function index(Request $request)
    {
        $filters = $request->only(['nombre', 'estatus']);
        $grupos = Grupo::filter($filters)->paginate(10);

        return $this->paginated($grupos, 'Lista de grupos');
    }

    /**
     * @OA\Post(
     *     path="/api/grupos",
     *     summary="Crear un grupo",
     *     operationId="crearGrupo",
     *     tags={"Grupo"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nombre"},
     *             @OA\Property(property="nombre", type="string"),
     *             @OA\Property(property="descripcion", type="string"),
     *             @OA\Property(property="estatus", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Grupo creado")
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:255',
            'estatus' => 'nullable|string',
        ]);

        $grupo = Grupo::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'estatus' => $request->estatus,
        ]);

        return $this->success($grupo, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/grupos/{id}",
     *     summary="Obtener un grupo por ID",
     *     operationId="mostrarGrupo",
     *     tags={"Grupo"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Grupo encontrado")
     * )
     */
    public function show($id)
    {
        $grupo = Grupo::findOrFail($id);
        return $this->success($grupo);
    }

    /**
     * @OA\Put(
     *     path="/api/grupos/{id}",
     *     summary="Actualizar grupo",
     *     operationId="actualizarGrupo",
     *     tags={"Grupo"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="nombre", type="string"),
     *             @OA\Property(property="descripcion", type="string"),
     *             @OA\Property(property="estatus", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Grupo actualizado")
     * )
     */
    public function update(Request $request, $id)
    {
        $grupo = Grupo::findOrFail($id);

        $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'descripcion' => 'nullable|string|max:255',
            'estatus' => 'nullable|string',
        ]);

        $grupo->update($request->only(['nombre', 'descripcion', 'estatus']));

        return $this->success($grupo);
    }

    /**
     * @OA\Delete(
     *     path="/api/grupos/{id}",
     *     summary="Eliminar grupo",
     *     operationId="eliminarGrupo",
     *     tags={"Grupo"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Grupo eliminado")
     * )
     */
    public function destroy($id)
    {
        $grupo = Grupo::findOrFail($id);
        $grupo->delete();

        return $this->success(null, 204);
    }
}
