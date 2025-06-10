<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Categoria;

class CategoriaSeeder extends Seeder
{
    public function run()
    {
        $categoria = [
            'Láminas y Aceros',
            'Material de Construcción',
            'Herramientas Básicas',
            'Herramientas Manuales',
            'Herramientas Eléctricas',
            'Accesorios Industriales',
            'Equipamiento Agroindustrial',
            'Insumos para Granjas',
            'Mantenimiento de Instalaciones'
        ];
        foreach ($categoria as $name) {
            Categoria::factory()->create(['nombre' => $name]);
        }
    }
}
