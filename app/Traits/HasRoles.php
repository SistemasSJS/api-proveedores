<?php

namespace App\Traits;

use App\Enums\UserRoleEnumerate;

trait HasRoles
{
    public function hasRole(UserRoleEnumerate|string|array $roles): bool
    {
        $roles = is_array($roles) ? $roles : [$roles];

        return in_array(
            $this->role,
            array_map(fn($role) => $role instanceof UserRoleEnumerate ? $role->value : $role, $roles)
        );
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRoleEnumerate::SUPER_ADMIN->value;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRoleEnumerate::ADMIN->value;
    }

    public function isUserAdmin(): bool
    {
        return $this->role === UserRoleEnumerate::ADMIN->value || $this->role === UserRoleEnumerate::ADMIN->value;
    }

    public function isProveedor(): bool
    {
        return $this->role === UserRoleEnumerate::PROVEEDOR->value;
    }

    // Agrega aquí otros atajos que necesites
}
