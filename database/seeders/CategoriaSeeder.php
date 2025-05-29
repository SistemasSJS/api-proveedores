<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Categoria;

class CategoriaSeeder extends Seeder
{
    public function run()
    {
        $categoria = ['Herramientas Eléctricas', 'Herramientas Manuales', 'Accesorios', 'Jardinería', 'Medición'];
        foreach ($categoria as $name) {
            Categoria::factory()->create(['nombre' => $name]);
        }
    }
}