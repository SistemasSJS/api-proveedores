<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Llamar al Seeder de Roles
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            TipoEmpresaSeeder::class,
        ]);

        if (config('app.debug')) {
            $this->call([
                ProveedorSeeder::class,
                SucursalSeeder::class,
                UnidadMedidaSeeder::class,
                CategoriaSeeder::class,
                MarcaSeeder::class,
                ProductoSeeder::class,
                AccesoRapidoSeeder::class,
                CotizacionesSeeder::class,
                // PedidosSeeder::class,
            ]);
        }

        // Llamar al Seeder de Usuarios
        // $this->call([UserSeeder::class]);


        // TipoEmpresa::factory()->count(10)->create();
        // Proveedor::factory()->count(10)->create();
        // UnidadMedida::factory()->count(5)->create();
        // Categoria::factory()->count(5)->create();
        // Marca::factory()->count(5)->create();
        // Producto::factory()->count(1000)->create();
    }
}
