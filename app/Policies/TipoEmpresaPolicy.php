<?php

namespace App\Policies;

use App\Models\TipoEmpresa;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TipoEmpresaPolicy
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
    public function view(User $user, TipoEmpresa $tipoEmpresa): bool
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
    public function update(User $user, TipoEmpresa $tipoEmpresa): bool
    {
                return $user->isUserAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TipoEmpresa $tipoEmpresa): bool
    {
                return $user->isSuperAdmin(); // Solo super admin
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, TipoEmpresa $tipoEmpresa): bool
    {
                return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, TipoEmpresa $tipoEmpresa): bool
    {
                return false;
    }
}
