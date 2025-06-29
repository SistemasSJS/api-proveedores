<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            [
                'nombre' => 'ADMINISTRADOR',
                'descripcion' => 'Acceso total al sistema, configuración y administración general',
                'permissions' => [
                    'users.create',
                    'users.read',
                    'users.update',
                    'users.delete',
                    'proveedores.create',
                    'proveedores.read',
                    'proveedores.update',
                    'proveedores.delete',
                    'productos.create',
                    'productos.read',
                    'productos.update',
                    'productos.delete',
                    'requisiciones.create',
                    'requisiciones.read',
                    'requisiciones.update',
                    'requisiciones.delete',
                    'dashboard.admin'
                ]
            ],
            [
                'nombre' => 'GERENTE',
                'descripcion' => 'Gestión integral de proveedores, catálogos y supervisores',
                'permissions' => [
                    'proveedor.users.create',
                    'proveedor.users.read',
                    'proveedor.users.update',
                    'proveedor.productos.create',
                    'proveedor.productos.read',
                    'proveedor.productos.update',
                    'proveedor.productos.delete',
                    'proveedor.sucursales.create',
                    'proveedor.sucursales.read',
                    'proveedor.sucursales.update',
                    'proveedor.sucursales.delete',
                    'proveedor.requisiciones.read',
                    'proveedor.requisiciones.update',
                    'proveedor.cotizaciones.create',
                    'proveedor.cotizaciones.read',
                    'dashboard.proveedor'
                ]
            ],
            [
                'nombre' => 'SUPERVISOR',
                'descripcion' => 'Supervisión de operaciones diarias, control parcial sobre usuarios y requisiciones',
                'permissions' => [
                    'proveedor.productos.read',
                    'proveedor.productos.update',
                    'proveedor.sucursales.read',
                    'proveedor.sucursales.update',
                    'proveedor.requisiciones.read',
                    'proveedor.requisiciones.update',
                    'dashboard.proveedor'
                ]
            ],
            [
                'nombre' => 'VENTAS',
                'descripcion' => 'Acceso para gestionar requisiciones, clientes y ventas',
                'permissions' => [
                    'proveedor.productos.read',
                    'proveedor.requisiciones.read',
                    'proveedor.requisiciones.update',
                    'proveedor.cotizaciones.create',
                    'proveedor.cotizaciones.read',
                    'dashboard.proveedor'
                ]
            ],
            [
                'nombre' => 'AUXILIAR',
                'descripcion' => 'Permisos limitados, apoyo en tareas específicas',
                'permissions' => [
                    'proveedor.productos.read',
                    'proveedor.requisiciones.read'
                ]
            ],
            [
                'nombre' => 'CLIENTE',
                'descripcion' => 'Usuario final que puede realizar requisiciones',
                'permissions' => [
                    'productos.search',
                    'requisiciones.create',
                    'requisiciones.read',
                    'requisiciones.update',
                    'dashboard.cliente'
                ]
            ]
        ];

        foreach ($roles as $roleData) {
            Role::updateOrCreate(
                ['nombre' => $roleData['nombre']],
                [
                    'descripcion' => $roleData['descripcion'],
                    // 'permissions' => json_encode($roleData['permissions'])
                ]
            );
        }
    }
}
