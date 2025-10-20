<?php

namespace App\Policies;

use App\Models\Proveedor;
use App\Models\User;

class ProveedorPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isUserAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Proveedor $proveedor): bool
    {
        return $user->isUserAdmin();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isUserAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Proveedor $proveedor): bool
    {
        return $user->isUserAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Proveedor $proveedor): bool
    {
        return $user->isSuperAdmin(); // Solo super admin
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Proveedor $proveedor): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Proveedor $proveedor): bool
    {
        return false;
    }
}
