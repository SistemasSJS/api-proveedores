<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            ['nombre' => 'ADMINISTRADOR', 'descripcion' => 'Acceso total al sistema'],
            ['nombre' => 'GERENTE', 'descripcion' => 'Gestión integral de proveedores'],
            ['nombre' => 'SUPERVISOR', 'descripcion' => 'Supervisión de operaciones diarias'],
            ['nombre' => 'VENTAS', 'descripcion' => 'Gestión de requisiciones y ventas'],
            ['nombre' => 'AUXILIAR', 'descripcion' => 'Permisos limitados de apoyo'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->insertOrIgnore([
                'nombre' => $role['nombre'],
                'descripcion' => $role['descripcion'],
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
