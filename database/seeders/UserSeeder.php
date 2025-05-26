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
        $default_foto_url = 'http://192.168.0.132:8080/storage/uploads/default.png';
        $idRolAdmin = Role::where('nombre', UserRoleEnumerate::ADMIN->value)->first()->id;
        $idRolSuperAdmin = Role::where('nombre', UserRoleEnumerate::SUPER_ADMIN->value)->first()->id;
        User::firstOrCreate(
            ['email' => 'jcsv@admin.admin'],
            [
                'name' => 'Superadmin (JCSV)',
                'foto_perfil_url' => $default_foto_url,
                'password' => Hash::make('admin123'), // Contraseña clara
                'role_id' => $idRolSuperAdmin,
                'email_verified_at' => now(),
            ]
        );
        User::firstOrCreate(
            ['email' => 'osaco@admin.admin'],
            [
                'name' => 'Admin (OSACO)', //'Administrador',
                'foto_perfil_url' => $default_foto_url,
                'password' => Hash::make('admin123'), // Contraseña clara
                'role_id' => $idRolAdmin,
                'email_verified_at' => now(),
            ]
        );
        User::firstOrCreate(
            ['email' => 'jssr@admin.admin'],
            [
                'name' => 'Desarrolador (JSSR)', //'Administrador',
                'foto_perfil_url' => $default_foto_url,
                'password' => Hash::make('admin123'), // Contraseña clara
                'role_id' => $idRolAdmin,
                'email_verified_at' => now(),
            ]
        );
        User::firstOrCreate(
            ['email' => 'gmb@admin.admin'],
            [
                'name' => 'Auxiliar de desarrollador (GMB)', //'Administrador',
                'foto_perfil_url' => $default_foto_url,
                'password' => Hash::make('admin123'), // Contraseña clara
                'role_id' => $idRolAdmin,
                'email_verified_at' => now(),
            ]
        );
    }
}
