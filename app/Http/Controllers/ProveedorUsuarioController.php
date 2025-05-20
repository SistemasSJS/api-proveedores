<?php

namespace App\Http\Controllers;

use App\Exceptions\Api\Auth\UnauthorizedException;
use App\Exceptions\Api\Crud\ResourceNotFoundException;
use App\Exceptions\Api\Custom\MainUserDuplicateException;
use App\Exceptions\Api\Custom\NotFoundRelationException;
use App\Http\Requests\UpdateProveedorUsuarioRequest;
use App\Http\Requests\UserCreateRequest;
use App\Http\Resources\UserResource;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


/**
 * @OA\Tag(
 *     name="ProveedorUsuarios",
 *     description="Endpoints para la gestión de usuarios asociados a proveedores"
 * )
 */

class ProveedorUsuarioController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/proveedores/{proveedor}/users",
     *     summary="Obtener lista de usuarios asociados a un proveedor",
     *     tags={"ProveedorUsuarios"},
     *     operationId="listarUsuariosProveedor",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="proveedor",
     *         in="path",
     *         description="ID del proveedor",
     *         required=true,
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Campo para ordenar (por defecto 'name')",
     *         required=false,
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         description="Orden ascendente o descendente (por defecto 'asc')",
     *         required=false,
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista paginada de usuarios",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/UserResource")
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Usuarios obtenidos correctamente."
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 description="Metadatos de paginación"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="No autorizado")
     * )
     */
    public function index(Request $request, Proveedor $proveedor)
    {
        $this->authorizeAccess($request->user(), $proveedor);

        $fields = User::getFilters();
        $filters = $request->only($fields);

        $sortBy = $request->input('sort_by', 'name');
        $order = $request->input('order', 'asc');

        $usersPaginate = $proveedor->users()
            ->with(User::eagerLodable())
            ->filter($filters)
            ->orderBy($sortBy, $order)
            ->paginate(10);
        $users = UserResource::collection($usersPaginate)->resolve();
        return $this->paginated(
            $usersPaginate->setCollection(collect($users)),
            'Usuarios obtenidos correctamente.'
        );
    }

    /**
     * @OA\Post(
     *     path="/api/proveedores/{proveedor}/users",
     *     summary="Crear usuario asociado a un proveedor",
     *     operationId="crearUsuarioProveedor",
     *     tags={"ProveedorUsuarios"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="proveedor",
     *         in="path",
     *         description="ID del proveedor",
     *         required=true,
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UserCreateRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Usuario creado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/UserResource")
     *     ),
     *     @OA\Response(response=403, description="No autorizado"),
     *     @OA\Response(response=409, description="Ya existe un usuario principal")
     * )
     */
    public function store(UserCreateRequest $request, Proveedor $proveedor)
    {
        $proveedor = Proveedor::findOrFail($proveedor);
        $this->authorizeAccess($request->user(), $proveedor);

        $validated = $request->validated();

        if (!empty($validated['is_main']) && $proveedor->usuarios()->wherePivot('is_main', true)->exists()) {
            return $this->error('Ya existe un usuario principal.', null, 409);
        }

        $user = new User($validated);
        $user->password = bcrypt($validated['password']);
        $user->save();

        $proveedor->usuarios()->attach($user->id, [
            'is_main' => $validated['is_main'] ?? false,
        ]);

        return $this->success(new UserResource($user), 'Usuario creado correctamente.', 201);
    }

    /**
     * @OA\Get(
     *     path="/api/proveedores/{proveedor}/users/{user}",
     *     summary="Obtener usuario asociado a un proveedor por ID",
     *     operationId="obtenerUsuarioProveedorPorId",
     *     tags={"ProveedorUsuarios"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="proveedor",
     *         in="path",
     *         description="ID del proveedor",
     *         required=true,
     *     ),
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         description="ID del usuario",
     *         required=true,
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Usuario encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/UserResource")
     *     ),
     *     @OA\Response(response=403, description="No autorizado"),
     *     @OA\Response(response=404, description="Usuario no asociado al proveedor")
     * )
     */
    public function getById(Request $request, Proveedor $proveedor, User $user)
    {
        if (!$user) {
            throw new ResourceNotFoundException(404, 'Usuario no encontrado.');
        }

        Log::info('Proveedor: ' . $proveedor->id);
        $this->authorizeAccess($request->user(), $proveedor);
        if (!$proveedor->users()->find($user->id)) {
            throw new ResourceNotFoundException(404, 'Usuario no asociado al proveedor.');
        }

        return $this->success(new UserResource($user), 'Usuario obtenido correctamente.');
    }

    /**
     * @OA\Put(
     *     path="/api/proveedores/{proveedor}/users/{user}",
     *     summary="Actualizar usuario asociado a un proveedor",
     *     operationId="actualizarUsuarioProveedor",
     *     tags={"ProveedorUsuarios"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="proveedor",
     *         in="path",
     *         description="ID del proveedor",
     *         required=true,
     *     ),
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         description="ID del usuario",
     *         required=true,
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UpdateProveedorUsuarioRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Usuario actualizado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/UserResource")
     *     ),
     *     @OA\Response(response=403, description="No autorizado o usuario no asociado"),
     *     @OA\Response(response=409, description="Ya hay otro usuario principal")
     * )
     */
    public function update(UpdateProveedorUsuarioRequest $request, Proveedor $proveedor, User $user)
    {
        $this->authorizeAccess($request->user(), $proveedor);

        if (!$proveedor->users()->find($user->id)) {
            throw new NotFoundRelationException('Usuario no asociado al proveedor.');
        }

        $validated = $request->validated();

        if (array_key_exists('is_main', $validated)) {
            if ($validated['is_main']) {
                // Obtener el usuario principal actual (si existe)
                $usuarioPrincipal = $proveedor->users()
                    ->wherePivot('is_main', true)
                    ->first();

                if ($usuarioPrincipal && $usuarioPrincipal->id !== $user->id) {
                    throw new MainUserDuplicateException('Ya hay otro usuario principal.', null, 409);
                }
            }

            $proveedor->users()->updateExistingPivot($user->id, [
                'is_main' => $validated['is_main']
            ]);
        }

        // No debe actualizar password aquí según los FormRequest
        $user->update($validated);

        return $this->success(new UserResource($user), 'Usuario actualizado correctamente.');
    }

    /**
     * @OA\Delete(
     *     path="/api/proveedores/{proveedor}/users/{user}",
     *     summary="Eliminar usuario asociado a un proveedor",
     *     operationId="eliminarUsuarioProveedor",
     *     tags={"ProveedorUsuarios"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="proveedor",
     *         in="path",
     *         description="ID del proveedor",
     *         required=true,
     *     ),
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         description="ID del usuario",
     *         required=true,
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Usuario eliminado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Usuario eliminado correctamente.")
     *         )
     *     ),
     *     @OA\Response(response=403, description="No autorizado, usuario no asociado o es usuario principal")
     * )
     */
    public function destroy(Request $request, Proveedor $proveedor, User $user)
    {
        $this->authorizeAccess($request->user(), $proveedor);

        $pivotData = $proveedor->users()->find($user->id);
        

        if (!$pivotData) {
            throw new NotFoundRelationException('Usuario no asociado al proveedor.');
        }

        if ($pivotData->is_main) {
            throw new MainUserDuplicateException('No se puede eliminar al usuario principal.');
        }

        $proveedor->users()->detach($user->id);
        $user->delete();

        return $this->success(message: 'Usuario eliminado correctamente.');
    }

    /**
     * Autoriza si el usuario actual puede acceder al proveedor
     *
     * @param User $currentUser Usuario autenticado
     * @param Proveedor $proveedor Proveedor objetivo
     */
    protected function authorizeAccess(User $currentUser, Proveedor $proveedor)
    {
        if ($currentUser->isUserAdmin()) {
            return;
        }

        $mainProveedor = $currentUser->mainProveedor()->first();

        if (!$mainProveedor || $mainProveedor->id !== $proveedor->id) {
            throw new UnauthorizedException();
        }
    }
}
