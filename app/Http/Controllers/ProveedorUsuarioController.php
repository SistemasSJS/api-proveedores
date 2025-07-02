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

class ProveedorUsuarioController extends Controller
{

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

    public function show(Request $request, Proveedor $proveedor, $user_id)
    {
        $this->authorizeAccess($request->user(), $proveedor);
        $user = USer::findOrFail($user_id);
        if (!$proveedor->users()->find($user->id)) {
            throw new ResourceNotFoundException(404, 'Usuario no asociado al proveedor.');
        }

        return $this->success(new UserResource($user->load(User::eagerLodable())), 'Usuario obtenido correctamente.');
    }

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

    public function destroy(Request $request, Proveedor $proveedor, $user_id)
    {
        $this->authorizeAccess($request->user(), $proveedor);

        $user = User::findOrFail($user_id);

        $pivotData = $proveedor->users()->find($user->id);


        if (!$pivotData) {
            throw new NotFoundRelationException('Usuario no asociado al proveedor.');
        }

        if ($pivotData->proveedorPrincipal()) {
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
