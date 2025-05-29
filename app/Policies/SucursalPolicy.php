<?php

namespace App\Policies;

use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SucursalPolicy
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
    public function view(User $user, Sucursal $sucursal): bool
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
    public function update(User $user, Sucursal $sucursal): bool
    {
        return $user->isUserAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Sucursal $sucursal): bool
    {
        return $user->isSuperAdmin(); // Solo super admin
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Sucursal $sucursal): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Sucursal $sucursal): bool
    {
        return false;
    }
}
