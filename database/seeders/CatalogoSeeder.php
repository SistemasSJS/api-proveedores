<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Catalogo;
use App\Models\Proveedor;

class CatalogoSeeder extends Seeder
{
    public function run()
    {
        $proveedores = Proveedor::all();

        foreach ($proveedores as $Proveedor) {
            Catalogo::factory()->count(3)->create([
                'proveedor_id' => $Proveedor->id,
            ]);
        }
    }
}