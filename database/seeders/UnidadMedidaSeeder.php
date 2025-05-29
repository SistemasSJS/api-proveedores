<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UnidadMedida;

class UnidadMedidaSeeder extends Seeder
{
    public function run()
    {
        $unidades = [
            ['descripcion' => 'm',     'nombre' => 'Metro'],
            ['descripcion' => 'm2',    'nombre' => 'Metro Cuadrado'],
            ['descripcion' => 'm3',    'nombre' => 'Metro Cúbico'],
            ['descripcion' => 'kg',    'nombre' => 'Kilogramo'],
            ['descripcion' => 'g',     'nombre' => 'Gramo'],
            ['descripcion' => 't',     'nombre' => 'Tonelada'],
            ['descripcion' => 'lt',    'nombre' => 'Litro'],
            ['descripcion' => 'ml',    'nombre' => 'Mililitro'],
            ['descripcion' => 'pza',   'nombre' => 'Pieza'],
            ['descripcion' => 'cj',    'nombre' => 'Caja'],
            ['descripcion' => 'bulto', 'nombre' => 'Bulto'],
            ['descripcion' => 'par',   'nombre' => 'Par'],
            ['descripcion' => 'jgo',   'nombre' => 'Juego'],
            ['descripcion' => 'ro',    'nombre' => 'Rollo'],
            ['descripcion' => 'mll',   'nombre' => 'Milla'],
        ];

        foreach ($unidades as $unidad) {
            UnidadMedida::firstOrCreate(
                ['descripcion' => $unidad['descripcion']],
                ['nombre' => $unidad['nombre']]
            );
        }
    }
}
