<?php

namespace App\Traits;


trait HasRoles
{
    public function hasRole(string|array $roles): bool
    {
        $roles = is_array($roles) ? $roles : [$roles];

        // if (!$this->relationLoaded('role')) {
            $this->load('role');
        // }

        $userRoleName = strtolower($this->role?->nombre ?? '');

        return in_array($userRoleName, array_map('strtolower', $roles));
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
