<?php

namespace App\Http\Controllers;

use App\Http\Resources\TipoEmpresaResource;
use App\Models\TipoEmpresa;
use Illuminate\Http\Request;

class TipoEmpresaController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/tipos-empresa",
     *     summary="Listar tipos de empresa",
     *     tags={"Administración"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Lista de tipos de empresa")
     * )
     */
    public function index(Request $request)
    {
        $filters = $request->only(TipoEmpresa::getFilters());
        $originalPaginator = TipoEmpresa::filter($filters)->paginate(1000);
        $tipoEmpresas = TipoEmpresaResource::collection($originalPaginator)->resolve();

        return $this->paginated($originalPaginator->setCollection(collect($tipoEmpresas)));
    }

    /**
     * @OA\Post(
     *     path="/api/tipos-empresa",
     *     summary="Crear tipo de empresa",
     *     tags={"Administración"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nombre"},
     *             @OA\Property(property="nombre", type="string", example="S.A. de C.V.")
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
     *     summary="Obtener tipo de empresa por ID",
     *     tags={"Administración"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Datos del tipo de empresa")
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
     *     tags={"Administración"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
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
     *     tags={"Administración"},
     *     security={{"bearerAuth":{}}},
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
