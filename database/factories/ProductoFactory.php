<?php

namespace Database\Factories;

use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductoFactory extends Factory
{
    protected $model = Producto::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->word,
            'descripcion' => $this->faker->sentence(10),
            'sku' => strtoupper($this->faker->unique()->bothify('??-###-??-#')),

            // 'catalogo_id' => Catalogo::inRandomOrder()->value('id') ?? 1,
            // 'unidad_medida_id' => UnidadMedida::inRandomOrder()->value('id') ?? 1,
            // 'categoria_id' => Categoria::inRandomOrder()->value('id') ?? 1,
            // 'marca_id' => Marca::inRandomOrder()->value('id') ?? 1,
            // 'linea_id' => Linea::inRandomOrder()->value('id') ?? 1,
        ];
    }
}
