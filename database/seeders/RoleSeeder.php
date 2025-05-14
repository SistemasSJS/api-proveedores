<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Enums\UserRoleEnumerate;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (UserRoleEnumerate::cases() as $roleEnum) {
            Role::updateOrCreate(
                ['nombre' => $roleEnum->value],
            );
        }
    }
}
