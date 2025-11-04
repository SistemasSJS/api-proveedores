<?php

namespace App\Http\Controllers;

use App\Http\Resources\RoleResource;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/roles",
     *     summary="Listar roles",
     *     tags={"Administración"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Lista de roles")
     * )
     */
    public function index(Request $request)
    {
        $filters = $request->only(Role::getFilters());
        $originalPaginator = Role::filter($filters)->paginate(1000);
        $data = RoleResource::collection($originalPaginator)->resolve();

        return $this->paginated($originalPaginator->setCollection(collect($data)));
    }

    /**
     * @OA\Post(
     *     path="/api/roles",
     *     summary="Crear nuevo rol",
     *     tags={"Administración"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Editor")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Rol creado")
     * )
     */
    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|unique:roles,name']);

        $role = Role::create($request->all());

        return response()->json($role, 201);
    }
}
