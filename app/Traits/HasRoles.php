<?php

namespace App\Traits;

use App\Enums\UserRoleEnumerate;
use App\Models\User;

trait HasRoles
{
    public function hasRole(UserRoleEnumerate|string|array $roles): bool
    {
        $roles = is_array($roles) ? $roles : [$roles];

        if (!$this->relationLoaded('role')) {
            $this->load('role');
        }

        $userRoleName = strtolower($this->role?->nombre ?? '');

        return in_array(
            $userRoleName,
            array_map(fn($role) => strtolower($role instanceof UserRoleEnumerate ? $role->value : $role), $roles)
        );
    }

    public function isSuperAdmin(): bool
    {
        return strtolower($this->role?->name) === strtolower(UserRoleEnumerate::SUPER_ADMIN->value);
    }

    public function isAdmin(): bool
    {
        return strtolower($this->role?->name) === strtolower(UserRoleEnumerate::ADMIN->value);
    }

    public function isUserAdmin(): bool
    {
        $name = strtolower($this->role?->name);
        return in_array($name, [
            strtolower(UserRoleEnumerate::ADMIN->value),
            strtolower(UserRoleEnumerate::SUPER_ADMIN->value),
        ]);
    }

    public function isProveedor(): bool
    {
        return strtolower($this->role?->name) === strtolower(UserRoleEnumerate::PROVEEDOR->value);
    }
}
