<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Linea;
use App\Models\Proveedor;

class LineaSeeder extends Seeder
{
    public function run()
    {
        $lineas = ['Power Tools', 'Hand Tools', 'Accessories', 'Gardening', 'Measuring'];

        $fierroYLamina = Proveedor::where('nombre_comercial', 'Fierro y Lámina')->first();
        $truper = Proveedor::where('nombre_comercial', 'Truper')->first();
        $granjasElGranGero = Proveedor::where('nombre_comercial', 'Granjas ElGranGero')->first();

        foreach ([$fierroYLamina, $truper, $granjasElGranGero,] as $proveedor) {
            foreach ($lineas as $name) {
                Linea::factory()->create([
                    'proveedor_id' => $proveedor->id,
                    'nombre' => $name
                ]);
            }
        }
    }
}
