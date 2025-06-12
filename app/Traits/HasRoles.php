<?php

namespace App\Traits;

use App\Enums\UserRoleEnumerate;

trait HasRoles
{
    public function hasRole(string|array $roles): bool
    {
        $roles = is_array($roles) ? $roles : [$roles];

        $userRoleNombre = $this->role()->first()->nombre;
        $hasPermission = in_array(strtolower($userRoleNombre), array_map('strtolower', $roles));
        return $hasPermission;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(UserRoleEnumerate::ADMINISTRADOR->value);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(UserRoleEnumerate::ADMINISTRADOR->value);
    }

    public function isUserAdmin(): bool
    {
        return $this->hasRole(UserRoleEnumerate::ADMINISTRADOR->value);
    }

    public function isProveedor(): bool
    {
        return $this->hasRole(UserRoleEnumerate::GERENTE->value);
    }
}
