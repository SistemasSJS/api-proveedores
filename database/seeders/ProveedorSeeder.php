<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Proveedor;
use App\Models\User;

class ProveedorSeeder extends Seeder
{
    public function run()
    {
        DB::transaction(function () {
            for ($i = 0; $i < 10; $i++) {
                // 1. Crear usuario principal (dueño)
                $userMain = User::factory()->proveedor()->create();

                // 2. Crear proveedor
                $proveedor = Proveedor::factory()->create();

                // 3. Asociar proveedor al usuario principal
                $userMain->proveedores()->attach($proveedor->id, ['is_main' => true]);

                // 4. Crear usuarios secundarios asociados al proveedor
                User::factory(3)->proveedor()->create()->each(function ($user) use ($proveedor) {
                    $user->proveedores()->attach($proveedor->id, ['is_main' => false]);
                });
            }
        });
    }
}
