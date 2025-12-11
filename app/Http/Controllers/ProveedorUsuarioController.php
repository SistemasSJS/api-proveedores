<?php

namespace App\Http\Controllers;

use App\Exceptions\Api\Auth\UnauthorizedException;
use App\Exceptions\Api\Crud\ResourceNotFoundException;
use App\Exceptions\Api\Custom\MainUserDuplicateException;
use App\Exceptions\Api\Custom\NotFoundRelationException;
use App\Http\Requests\ProveedorUsuario\ProveedorUsuairoStoreRequest;
use App\Http\Requests\ProveedorUsuario\ProveedorUsuairoUpdateLogoRequest;
use App\Http\Requests\ProveedorUsuario\ProveedorUsuairoUpdateRequest;
use App\Http\Requests\ProveedorUsuario\ProveedorUsuarioUpdateRelacionRequest;
use App\Http\Requests\ProveedorUsuario\ProveedorUsuarioCambiarEstadoRequest;
use App\Http\Resources\UserResource;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Http\Request;
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
            ->paginate($perPage);

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

        // Extraer datos del usuario y de la relación
        $tipoRelacion = $validated['tipo_relacion'] ?? 'SECUNDARIO';
        $activo = $validated['activo'] ?? true;
        $observaciones = $validated['observaciones'] ?? null;

        // Validar que no exista más de un usuario PRINCIPAL activo
        if ($tipoRelacion === 'PRINCIPAL') {
            $existePrincipal = $proveedor->users()
                ->wherePivot('tipo_relacion', 'PRINCIPAL')
                ->wherePivot('activo', true)
                ->exists();

            if ($existePrincipal) {
                return $this->error('Ya existe un usuario principal activo. Debe desactivarlo primero.', null, 409);
            }
        }

        // Crear el usuario
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role_id' => $validated['role_id'],
        ]);

        // Asociar al proveedor con los datos pivot
        $proveedor->users()->attach($user->id, [
            'tipo_relacion' => $tipoRelacion,
            'activo' => $activo,
            'estado' => 'registrado',
            'fecha_asignacion' => now(),
            'observaciones' => $observaciones ?? "Usuario {$tipoRelacion} creado",
        ]);

        return $this->success(new UserResource($user->load(User::eagerLodable())), 'Usuario creado correctamente.', 201);
    }

    public function show(Request $request, Proveedor $proveedor, $user_id)
    {
        $this->authorizeAccess($request->user(), $proveedor);
        $user = USer::findOrFail($user_id);
        if (! $proveedor->users()->find($user->id)) {
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
        if (! $proveedor->users()->find($user->id)) {
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

        if (! $pivotData) {
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
        if (! $proveedor->users()->find($user->id)) {
            throw new ResourceNotFoundException(404, 'Usuario no asociado al proveedor.');
        }
        // Eliminar logo anterior si existe
        if ($user->foto_perfil_url) {
            $rutaAnterior = str_replace(asset('storage').'/', '', $user->foto_perfil_url);
            Storage::disk('public')->delete($rutaAnterior);
        }

        // Guardar nuevo archivo
        $file = $request->file('logo');
        $filename = "logo_user_{$user->id}_".time().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs('uploads', $filename, 'public');

        // Actualizar ruta en base de datos
        $user->update(['foto_perfil_url' => $path]);

        return $this->success(new UserResource($user->fresh(User::eagerLodable())));
    }

    public function updateRelacion(ProveedorUsuarioUpdateRelacionRequest $request, Proveedor $proveedor, $user_id)
    {
        $this->authorizeAccess($request->user(), $proveedor);

        $user = User::findOrFail($user_id);
        $pivotData = $proveedor->users()->find($user->id);

        if (! $pivotData) {
            throw new ResourceNotFoundException(404, 'Usuario no asociado al proveedor.');
        }

        $validated = $request->validated();
        $tipoRelacion = $validated['tipo_relacion'] ?? $pivotData->pivot->tipo_relacion;
        $activo = $validated['activo'] ?? $pivotData->pivot->activo;

        // Validar cambio a PRINCIPAL
        if (isset($validated['tipo_relacion']) && $validated['tipo_relacion'] === 'PRINCIPAL') {
            $existePrincipalOtro = $proveedor->users()
                ->wherePivot('tipo_relacion', 'PRINCIPAL')
                ->wherePivot('activo', true)
                ->where('users.id', '!=', $user_id)
                ->exists();

            if ($existePrincipalOtro) {
                return $this->error('Ya existe otro usuario principal activo. Debe desactivarlo o cambiarlo a secundario primero.', null, 409);
            }
        }

        // Validar que no se desactive el único usuario PRINCIPAL activo
        if (isset($validated['activo']) && !$validated['activo']) {
            if ($pivotData->pivot->tipo_relacion === 'PRINCIPAL') {
                $countPrincipalesActivos = $proveedor->users()
                    ->wherePivot('tipo_relacion', 'PRINCIPAL')
                    ->wherePivot('activo', true)
                    ->count();

                if ($countPrincipalesActivos <= 1) {
                    return $this->error('No se puede desactivar al único usuario principal activo.', null, 409);
                }
            }
        }

        // Preparar datos para actualizar
        $updateData = [];
        if (isset($validated['tipo_relacion'])) {
            $updateData['tipo_relacion'] = $validated['tipo_relacion'];
        }
        if (isset($validated['activo'])) {
            $updateData['activo'] = $validated['activo'];
            // Si se desactiva, registrar fecha de desasignación
            if (!$validated['activo']) {
                $updateData['fecha_desasignacion'] = now();
            } else {
                $updateData['fecha_desasignacion'] = null;
            }
        }
        if (isset($validated['observaciones'])) {
            // Agregar observación conservando las anteriores
            $observacionesAnteriores = $pivotData->pivot->observaciones ?? '';
            $nuevaObservacion = $validated['observaciones'];
            $timestamp = now()->format('Y-m-d H:i:s');
            $updateData['observaciones'] = $observacionesAnteriores . "\n[{$timestamp}] {$nuevaObservacion}";
        }

        // Actualizar la relación pivot
        $proveedor->users()->updateExistingPivot($user_id, $updateData);

        return $this->success(
            new UserResource($user->fresh()->load(User::eagerLodable())),
            'Relación actualizada correctamente.'
        );
    }

    public function cambiarEstado(ProveedorUsuarioCambiarEstadoRequest $request, Proveedor $proveedor, $user_id)
    {
        $this->authorizeAccess($request->user(), $proveedor);

        $user = User::findOrFail($user_id);
        $pivotData = $proveedor->users()->find($user->id);

        if (! $pivotData) {
            throw new ResourceNotFoundException(404, 'Usuario no asociado al proveedor.');
        }

        $validated = $request->validated();
        $nuevoEstado = $validated['estado'];
        $observacion = $validated['observaciones'] ?? "Estado cambiado a {$nuevoEstado}";

        // Registrar el cambio en observaciones
        $observacionesAnteriores = $pivotData->pivot->observaciones ?? '';
        $timestamp = now()->format('Y-m-d H:i:s');
        $observacionCompleta = $observacionesAnteriores . "\n[{$timestamp}] {$observacion}";

        // Actualizar estado
        $proveedor->users()->updateExistingPivot($user_id, [
            'estado' => $nuevoEstado,
            'observaciones' => $observacionCompleta,
        ]);

        return $this->success(
            new UserResource($user->fresh()->load(User::eagerLodable())),
            'Estado actualizado correctamente.'
        );
    }

    protected function authorizeAccess(User $currentUser, Proveedor $proveedor)
    {
        if ($currentUser->isUserAdmin()) {
            return;
        }

        $proveedorPrincipal = $currentUser->proveedorPrincipal();

        if (! $proveedorPrincipal || $proveedorPrincipal->id !== $proveedor->id) {
            throw new UnauthorizedException;
        }
    }
}
