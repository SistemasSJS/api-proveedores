<?php

namespace App\Traits;

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
        return $this->hasRole('super_admin');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isUserAdmin(): bool
    {
        return $this->hasRole(['admin', 'super_admin']);
    }

    public function isProveedor(): bool
    {
        return $this->hasRole('proveedor');
    }
}
