<?php

namespace App\Policies;

use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SucursalPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user, $proveedor = null)
    {
        if ($user->role?->name === 'ADMINISTRADOR') {
            return true;
        }

        // Si hay un proveedor específico, verificar acceso
        if ($proveedor) {
            return $user->proveedores()->where('proveedor_id', $proveedor->id)->exists();
        }

        return false;
    }

    public function view(User $user, Sucursal $sucursal)
    {
        if ($user->role?->name === 'ADMINISTRADOR') {
            return true;
        }

        return $user->proveedores()->where('proveedor_id', $sucursal->proveedor_id)->exists();
    }

    public function create(User $user, $proveedor)
    {
        if ($user->role?->name === 'ADMINISTRADOR') {
            return true;
        }

        // Solo GERENTE puede crear sucursales
        if ($user->role?->name !== 'GERENTE') {
            return false;
        }

        return $user->proveedores()->where('proveedor_id', $proveedor->id)->exists();
    }

    public function update(User $user, Sucursal $sucursal)
    {
        if ($user->role?->name === 'ADMINISTRADOR') {
            return true;
        }

        // Solo GERENTE puede actualizar sucursales
        if ($user->role?->name !== 'GERENTE') {
            return false;
        }

        return $user->proveedores()->where('proveedor_id', $sucursal->proveedor_id)->exists();
    }

    public function delete(User $user, Sucursal $sucursal)
    {
        if ($user->role?->name === 'ADMINISTRADOR') {
            return true;
        }

        // Solo GERENTE puede eliminar sucursales
        if ($user->role?->name !== 'GERENTE') {
            return false;
        }

        return $user->proveedores()->where('proveedor_id', $sucursal->proveedor_id)->exists();
    }

    public function manageProducts(User $user, Sucursal $sucursal)
    {
        if ($user->role?->name === 'ADMINISTRADOR') {
            return true;
        }

        // GERENTE y SUPERVISOR pueden gestionar productos en sucursales
        if (! in_array($user->role?->name, ['GERENTE', 'SUPERVISOR'])) {
            return false;
        }

        return $user->proveedores()->where('proveedor_id', $sucursal->proveedor_id)->exists();
    }
}
