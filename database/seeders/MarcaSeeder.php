<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Marca;
use App\Models\Proveedor;

class MarcaSeeder extends Seeder
{
    public function run()
    {
        $marcas = ['Bosch', 'Makita', 'DeWalt', 'Hitachi', 'Black & Decker'];

        $fierroYLamina = Proveedor::where('nombre_comercial', 'Fierro y Lámina')->first();
        $truper = Proveedor::where('nombre_comercial', 'Truper')->first();
        $granjasElGranGero = Proveedor::where('nombre_comercial', 'Granjas ElGranGero')->first();

        foreach ([$fierroYLamina, $truper, $granjasElGranGero,] as $proveedor) {
            foreach ($marcas as $name) {
                Marca::factory()->create([
                    'proveedor_id' => $proveedor->id,
                    'nombre' => $name
                ]);
            }
        }
    }
}
