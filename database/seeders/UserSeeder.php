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
        $idRolAdmin = Role::where('nombre', UserRoleEnumerate::ADMIN->value)->first()->id;
        User::firstOrCreate(
            ['email' => 'admin@admin.admin'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('admin123'), // Contraseña clara
                'role_id' => $idRolAdmin,
                'email_verified_at' => now(),
            ]
        );
    }
}
