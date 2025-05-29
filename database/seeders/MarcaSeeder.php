<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Marca;

class MarcaSeeder extends Seeder
{
    public function run()
    {
        $marcas = ['Bosch', 'Makita', 'DeWalt', 'Hitachi', 'Black & Decker'];
        foreach ($marcas as $name) {
            Marca::factory()->create(['nombre' => $name]);
        }
    }
}