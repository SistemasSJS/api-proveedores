<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\UnidadMedida;
use App\Models\Grupo;
use App\Models\Linea;
use App\Models\Marca;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductoFactory extends Factory
{
    protected $model = Producto::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->words(3, true),
            'descripcion' => $this->faker->sentence(10),
            'sku' => strtoupper($this->faker->bothify('??-###-??-#')),
            'modelo_codigo' => strtoupper($this->faker->bothify('MDL-###-??')),

            'proveedor_id' => Proveedor::inRandomOrder()->value('id') ?? 1,
            'unidad_medida_id' => UnidadMedida::inRandomOrder()->value('id') ?? 1,
            'grupo_id' => Grupo::inRandomOrder()->value('id') ?? 1,

            'categoria_id' => Categoria::inRandomOrder()->value('id') ?? 1,
            'marca_id' => Marca::inRandomOrder()->value('id') ?? 1,
            'linea_id' => Linea::inRandomOrder()->value('id') ?? 1,
        ];
    }
}
