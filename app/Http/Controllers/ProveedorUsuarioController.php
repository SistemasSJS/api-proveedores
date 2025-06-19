<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Proveedor;

use App\Http\Resources\UserResource;


use App\Http\Requests\ProveedorUsuario\ProveedorUsuairoStoreRequest;
use App\Http\Requests\ProveedorUsuario\ProveedorUsuairoUpdateRequest;
use App\Http\Requests\ProveedorUsuario\ProveedorUsuairoUpdateLogoRequest;

use App\Exceptions\Api\Auth\UnauthorizedException;
use App\Exceptions\Api\Crud\ResourceNotFoundException;
use App\Exceptions\Api\Custom\MainUserDuplicateException;
use App\Exceptions\Api\Custom\NotFoundRelationException;
use Illuminate\Support\Facades\Storage;

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
        $perPage = $request->input('per_page', 10);

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
     *         @OA\JsonContent(ref="#/components/schemas/UserStoreRequest")
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
    public function store(ProveedorUsuairoStoreRequest $request, Proveedor $proveedor)
    {
        $this->authorizeAccess($request->user(), $proveedor);

        $validated = $request->validated();

        // NO ES NECESARIO VALIDAR EL is_main DADOP QUE SIEMPRE SERAN REGISTROS DE USUARIOS SECUNDARIOS
        // if (!empty($validated['is_main']) && $proveedor->users()->wherePivot('is_main', true)->exists()) {
        //     return $this->error('Ya existe un usuario principal.', null, 409);
        // }
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role_id' => $validated['role_id'],
        ]);

        // $proveedor->users()->attach($user->id, [
        //     'is_main' => false,
        // ]);
        // TODO: add on  ProveedorUsuaiorStoreRequest params:
        // - tipo_relacion: PRIMARIO | SECUNDARIO
        // - activo ---> change a estatus string
        // - fecha_asignacion
        // - observaciones  ---> in from generar opciones prefabricadas. No dejar libre

        $proveedor->users()->attach($user->id, [
            'tipo_relacion' => 'SECUNDARIO',
            'activo' => true,
            'fecha_asignacion' => now(),
            'observaciones' => 'Usuario secundario del proveedor',
        ]);

        return $this->success(new UserResource($user->load(User::eagerLodable())), 'Usuario creado correctamente.', 201);
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
    public function show(Request $request, Proveedor $proveedor, $user_id)
    {
        $this->authorizeAccess($request->user(), $proveedor);
        $user = USer::findOrFail($user_id);
        if (!$proveedor->users()->find($user->id)) {
            throw new ResourceNotFoundException(404, 'Usuario no asociado al proveedor.');
        }

        return $this->success(new UserResource($user->load(User::eagerLodable())), 'Usuario obtenido correctamente.');
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
     *         @OA\JsonContent(ref="#/components/schemas/ProveedorUsuairoUpdateRequest")
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
    public function update(ProveedorUsuairoUpdateRequest $request, Proveedor $proveedor, $user_id)
    {
        // 1. VALIDAR LA RELACION DEL USUARIO DE LA PETICON Y EL PROVEEDOR
        // 2. VERIFICAR QUE EL USUARIO EN LOS PARAM QRY PERTENECE AL PROVEEDOR
        $this->authorizeAccess($request->user(), $proveedor);
        $user = USer::findOrFail($user_id);
        if (!$proveedor->users()->find($user->id)) {
            throw new ResourceNotFoundException(404, 'Usuario no asociado al proveedor.');
        }
        $validated = $request->validated();
        $user->update($validated);

        return $this->success(new UserResource($user->fresh(User::eagerLodable())), 'Usuario actualizado correctamente.');
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
    public function destroy(Request $request, Proveedor $proveedor, $user_id)
    {
        $this->authorizeAccess($request->user(), $proveedor);

        $user = User::findOrFail($user_id);

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

    public function updateLogo(ProveedorUsuairoUpdateLogoRequest $request, Proveedor $proveedor, $user_id)
    {
        $this->authorizeAccess($request->user(), $proveedor);
        $user = USer::findOrFail($user_id);
        if (!$proveedor->users()->find($user->id)) {
            throw new ResourceNotFoundException(404, 'Usuario no asociado al proveedor.');
        }
        // Eliminar logo anterior si existe
        if ($user->foto_perfil_url) {
            $rutaAnterior = str_replace(asset('storage') . '/', '', $user->foto_perfil_url);
            Storage::disk('public')->delete($rutaAnterior);
        }

        // Guardar nuevo archivo
        $file = $request->file('logo');
        $filename = "logo_user_{$user->id}_" . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('uploads', $filename, 'public');

        // Actualizar ruta en base de datos
        $user->update(['foto_perfil_url' => $path]);

        return $this->success(new UserResource($user->fresh(User::eagerLodable())));
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

        $proveedorPrincipal = $currentUser->proveedorPrincipal();

        if (!$proveedorPrincipal || $proveedorPrincipal->id !== $proveedor->id) {
            throw new UnauthorizedException();
        }
    }
}
