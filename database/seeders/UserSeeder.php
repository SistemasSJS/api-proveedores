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
        $default_foto_url = '/uploads/default.png';
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
    }
}
