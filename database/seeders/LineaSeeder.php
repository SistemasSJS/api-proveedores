<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Linea;
use App\Models\Marca;
use App\Models\Proveedor;

class LineaSeeder extends Seeder
{
    public function run()
    {
        $lineas = ['Power Tools', 'Hand Tools', 'Accessories', 'Gardening', 'Measuring'];

        $marcas = Marca::all();

        // $fierroYLamina = Proveedor::where('nombre_comercial', 'Fierro y Lámina')->first();
        // $truper = Proveedor::where('nombre_comercial', 'Truper')->first();
        // $granjasElGranGero = Proveedor::where('nombre_comercial', 'Granjas ElGranGero')->first();

        // foreach ([$fierroYLamina, $truper, $granjasElGranGero,] as $proveedor) {
        foreach ($marcas as $marca) {
            foreach ($lineas as $name) {
                Linea::factory()->create([
                    'proveedor_id' => $marca->proveedor_id,
                    'marca_id' => $marca->id,
                    'nombre' => $name
                ]);
            }
        }
        // }
    }
}
