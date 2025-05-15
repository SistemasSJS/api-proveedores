<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Grupo;
use App\Models\Linea;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Role;
use App\Models\TipoEmpresa;
use App\Models\UnidadMedida;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Llamar al Seeder de Roles
        $this->call([RoleSeeder::class]);

        // Llamar al Seeder de Usuarios
        $this->call([UserSeeder::class]);

        // Crear empresas y asociarlas a usuarios
        TipoEmpresa::factory()->count(10)->create();

        // Crear proveedores y usuarios en una transacción
        DB::transaction(function () {
            // Creamos 10 proveedores con usuarios principales y secundarios
            for ($i = 0; $i < 10; $i++) {
                // 1. Crear el usuario principal (dueño)
                $userMain = User::factory()->proveedor()->create();

                // 2. Crear el proveedor y asociarlo al usuario principal

                $proveedor = Proveedor::factory()->create();
                $userMain->proveedores()->attach($proveedor->id, ['is_main' => true]);


                // 4. Crear usuarios secundarios y asignarlos al proveedor
                User::factory(3)->proveedor()->create()->each(function ($user) use ($proveedor) {
                    // Asignamos el proveedor al usuario como relación secundaria (is_main = false)
                    $user->proveedores()->attach($proveedor->id, ['is_main' => false]);
                });
                // [
                //     'proveedor_id' => $proveedor->id,
                //     'role_id' => Role::where('nombre', 'PROVEEDOR')->first()->id, // Asignamos el rol de proveedor
                // ]
            }
        });

        // Crear más proveedores y otros modelos
        Proveedor::factory()->count(10)->create();
        UnidadMedida::factory()->count(5)->create();
        Grupo::factory()->count(5)->create();
        Categoria::factory()->count(5)->create();
        Marca::factory()->count(5)->create();
        Linea::factory()->count(5)->create();
        Producto::factory()->count(1000)->create();
    }
}
