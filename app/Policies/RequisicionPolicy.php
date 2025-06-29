<?php

namespace App\Policies;

use App\Models\Requisicion;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class RequisicionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return true; // Todos los usuarios autenticados pueden ver sus requisiciones
    }

    public function view(User $user, Requisicion $requisicion)
    {
        // El usuario puede ver la requisición si la creó
        if ($user->id === $requisicion->usuario_id) {
            return true;
        }

        // Los usuarios del proveedor pueden ver requisiciones dirigidas a su proveedor
        if ($user->proveedores()->where('proveedor_id', $requisicion->proveedor_id)->exists()) {
            return true;
        }

        // Los administradores pueden ver todas
        return $user->role?->name === 'ADMINISTRADOR';
    }

    public function create(User $user)
    {
        // Todos los usuarios autenticados pueden crear requisiciones
        return true;
    }

    public function update(User $user, Requisicion $requisicion)
    {
        // Solo el creador puede actualizar (cancelar) si está pendiente o en proceso
        if ($user->id === $requisicion->usuario_id) {
            return in_array($requisicion->estatus, ['pendiente', 'en_proceso']);
        }

        return false;
    }

    public function delete(User $user, Requisicion $requisicion)
    {
        // Solo administradores pueden eliminar requisiciones
        return $user->role?->name === 'ADMINISTRADOR';
    }

    public function updateStatus(User $user, Requisicion $requisicion)
    {
        // Solo usuarios del proveedor pueden cambiar el estatus
        return $user->proveedores()->where('proveedor_id', $requisicion->proveedor_id)->exists();
    }

    public function createCotizacion(User $user, Requisicion $requisicion)
    {
        // Solo usuarios del proveedor con rol GERENTE o VENTAS pueden cotizar
        if (!$user->proveedores()->where('proveedor_id', $requisicion->proveedor_id)->exists()) {
            return false;
        }

        return in_array($user->role?->name, ['GERENTE', 'VENTAS', 'ADMINISTRADOR']);
    }
}
