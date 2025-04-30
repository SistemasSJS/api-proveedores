<?php

namespace Database\Seeders;

use App\Models\Grupo;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\UnidadMedida;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        User::factory()->create();
        User::factory(10)->proveedor()->create();
        Proveedor::factory()->count(10)->create();
        UnidadMedida::factory()->count(5)->create();
        Grupo::factory()->count(5)->create();
        Producto::factory()->count(1000)->create();
    }
}
