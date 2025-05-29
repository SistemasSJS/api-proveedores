<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Linea;

class LineaSeeder extends Seeder
{
    public function run()
    {
        $lineas = ['Power Tools', 'Hand Tools', 'Accessories', 'Gardening', 'Measuring'];
        foreach ($lineas as $name) {
            Linea::factory()->create(['nombre' => $name]);
        }
    }
}