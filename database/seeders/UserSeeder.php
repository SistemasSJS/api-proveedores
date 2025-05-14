<?php

namespace Database\Seeders;

use App\Models\User;
use App\Enums\UserRoleEnumerate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@admin.admin'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('admin123'), // Contraseña clara
                // 'role' => UserRoleEnumerate::ADMIN->value,
                'email_verified_at' => now(),
            ]
        );
    }
}
