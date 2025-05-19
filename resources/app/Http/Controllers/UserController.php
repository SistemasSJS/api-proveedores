<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserUpdateRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/users",
     *     summary="Listar todos los usuarios con filtros opcionales y paginación",
     *     operationId="listarUsuarios",
     *     tags={"Usuario"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="nombre", in="query", description="Filtrar por nombre", @OA\Schema(type="string")),
     *     @OA\Parameter(name="email", in="query", description="Filtrar por email", @OA\Schema(type="string")),
     *     @OA\Parameter(name="fecha_inicio", in="query", description="Filtrar por fecha de inicio", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="fecha_fin", in="query", description="Filtrar por fecha de fin", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="page", in="query", description="Número de página", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Listado paginado de usuarios")
     * )
     */
    public function index(Request $request)
    {
        $fields = User::getFilters();
        $filters = $request->only($fields);

        $sortBy = $request->input('sort_by', 'nombre_comercial');
        $order = $request->input('order', 'asc');

        $originalPaginator = User::filter($filters)
            ->orderBy($sortBy, $order)
            ->paginate(10);

        
        $users = UserResource::collection($originalPaginator)->resolve();
        return $this->paginated($originalPaginator->setCollection(collect($users)));
    }

    /**
     * @OA\Post(
     *     path="/api/users",
     *     summary="Crear un usuario",
     *     operationId="crearUsuario",
     *     tags={"Usuario"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", format="password")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Usuario creado")
     * )
     */
    public function store(UserUpdateRequest $request)
    {
        $user = User::create($request->validate());
        return $this->success([
            'user' => new UserResource($user)
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/users/{id}",
     *     summary="Obtener un usuario por ID",
     *     operationId="mostrarUsuario",
     *     tags={"Usuario"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Usuario encontrado")
     * )
     */
    public function show($id)
    {
        $user = User::findOrFail($id);
        return $this->success($user);
    }

    /**
     * @OA\Put(
     *     path="/api/users/{id}",
     *     summary="Actualizar usuario",
     *     operationId="actualizarUsuario",
     *     tags={"Usuario"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", format="password")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Usuario actualizado")
     * )
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'password' => ['nullable', 'string', Password::min(8)],
        ]);

        $data = $request->only(['name', 'email']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return $this->success($user);
    }

    /**
     * @OA\Delete(
     *     path="/api/users/{id}",
     *     summary="Eliminar usuario",
     *     operationId="eliminarUsuario",
     *     tags={"Usuario"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Usuario eliminado")
     * )
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return $this->success(null, 204);
    }
}
