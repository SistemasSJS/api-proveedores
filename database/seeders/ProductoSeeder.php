<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Producto;
use App\Models\Marca;
use App\Models\Linea;
use App\Models\Catalogo;
use App\Models\Categoria;
use App\Models\UnidadMedida;

class ProductoSeeder extends Seeder
{
    public function run()
    {
        $marcas = Marca::all();
        $lineas = Linea::all();
        $categoria = Categoria::all();
        $catalogos = Catalogo::all();
        $unidad_medida = UnidadMedida::all();

        foreach ($catalogos as $catalogo) {
            Producto::factory()->count(10)->create([
                'catalogo_id' => $catalogo->id,
                'unidad_medida_id' => $unidad_medida->random()->id,
                'categoria_id' => $categoria->random()->id,
                'linea_id' => $lineas->random()->id,
                'marca_id' => $marcas->random()->id,
            ]);
            // ->each(function ($Producto) use ($categoria) {
            //     $Producto->categorias()->attach($categoria->random(rand(1, 3))->pluck('id')->toArray());
            // });
        }
    }
}