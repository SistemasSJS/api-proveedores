<?php

namespace Database\Seeders;

use App\Models\User;
use App\Enums\UserRoleEnumerate;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $default_foto_url = 'uploads/default.png';
        $idRolAdmin = Role::where('nombre', UserRoleEnumerate::ADMINISTRADOR->value)->first()->id;
        $idRolSuperAdmin = Role::where('nombre', UserRoleEnumerate::ADMINISTRADOR->value)->first()->id;
        $userClienteId = Role::where('nombre', UserRoleEnumerate::CLIENTE->value)->first()->id;

        User::firstOrCreate(
            [
                'email' => 'user@user.com',
                'name' => 'Usuario de prueba',
                'foto_perfil_url' => $default_foto_url,
                'password' => Hash::make('123456'), // Contraseña clara
                'role_id' => $userClienteId,
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            [
                'email' => 'juliocsv@sjs.com.mx',
                'name' => 'Superadmin (JCSV)',
                'foto_perfil_url' => $default_foto_url,
                'password' => Hash::make('123456'), // Contraseña clara
                'role_id' => $idRolSuperAdmin,
                'email_verified_at' => now(),
            ]
        );
        User::firstOrCreate(
            // ['email' => 'osaco@admin.admin'],
            ['email' => 'dir.tecnico@sjs.com.mx'],
            [
                'name' => 'Admin (OSACO)', //'Administrador',
                'foto_perfil_url' => $default_foto_url,
                'password' => Hash::make('123456'), // Contraseña clara
                'role_id' => $idRolAdmin,
                'email_verified_at' => now(),
            ]
        );
        User::firstOrCreate(
            ['email' => 'jssr@admin.admin'],
            [
                'name' => 'Desarrolador (JSSR)', //'Administrador',
                'foto_perfil_url' => $default_foto_url,
                'password' => Hash::make('123456'), // Contraseña clara
                'role_id' => $idRolAdmin,
                'email_verified_at' => now(),
            ]
        );
        User::firstOrCreate(
            ['email' => 'sistemas_sjs@hotmail.com'],
            [
                'name' => 'Auxiliar de desarrollador (GMB)', //'Administrador',
                'foto_perfil_url' => $default_foto_url,
                'password' => Hash::make('123456'), // Contraseña clara
                'role_id' => $idRolAdmin,
                'email_verified_at' => now(),
            ]
        );


        $clientes = [
            [
                'nombre' => 'Juan Carlos Pérez',
                'email' => 'juan.perez@construccion.com',
                'telefono' => '6677123456',
                'empresa' => 'Constructora del Norte S.A.',
                'rfc' => 'CDN850101ABC',
                'direccion' => 'Av. Principal 123, Col. Centro',
                'ciudad' => 'Los Mochis',
                'estado' => 'Sinaloa',
                'codigo_postal' => '81200',
                'tipo_cliente' => 'EMPRESA',
                'estatus' => 'ACTIVO'
            ],
            [
                'nombre' => 'María Elena González',
                'email' => 'maria.gonzalez@gmail.com',
                'telefono' => '6677234567',
                'empresa' => null,
                'rfc' => 'GOME780915XYZ',
                'direccion' => 'Calle Revolución 456, Col. Las Flores',
                'ciudad' => 'Ahome',
                'estado' => 'Sinaloa',
                'codigo_postal' => '81220',
                'tipo_cliente' => 'PERSONA_FISICA',
                'estatus' => 'ACTIVO'
            ],
            [
                'nombre' => 'Roberto Silva Castro',
                'email' => 'roberto.silva@ingenieria.mx',
                'telefono' => '6677345678',
                'empresa' => 'Ingeniería y Proyectos Silva',
                'rfc' => 'IPS920315DEF',
                'direccion' => 'Blvd. López Mateos 789, Col. Country',
                'ciudad' => 'Los Mochis',
                'estado' => 'Sinaloa',
                'codigo_postal' => '81259',
                'tipo_cliente' => 'EMPRESA',
                'estatus' => 'ACTIVO'
            ],
            [
                'nombre' => 'Ana Patricia Morales',
                'email' => 'ana.morales@hotmail.com',
                'telefono' => '6677456789',
                'empresa' => 'Arquitectura Morales & Asociados',
                'rfc' => 'AMA841205GHI',
                'direccion' => 'Av. Obregón 321, Col. Centro',
                'ciudad' => 'Guasave',
                'estado' => 'Sinaloa',
                'codigo_postal' => '81000',
                'tipo_cliente' => 'EMPRESA',
                'estatus' => 'ACTIVO'
            ],
            [
                'nombre' => 'Fernando Ramírez López',
                'email' => 'fernando.ramirez@yahoo.com',
                'telefono' => '6677567890',
                'empresa' => null,
                'rfc' => 'RALF750820JKL',
                'direccion' => 'Calle Hidalgo 654, Col. Emiliano Zapata',
                'ciudad' => 'El Fuerte',
                'estado' => 'Sinaloa',
                'codigo_postal' => '81890',
                'tipo_cliente' => 'PERSONA_FISICA',
                'estatus' => 'ACTIVO'
            ]
        ];

        foreach ($clientes as $clienteData) {
            // Crear usuario para el cliente
            User::create([
                'name' => $clienteData['nombre'],
                'email' => $clienteData['email'],
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'role_id' => $userClienteId, // Asumiendo que el rol CLIENTE tiene ID 6
            ]);
        }
    }
}
