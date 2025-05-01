<?php

namespace Database\Factories;

use App\Models\Categoria;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoriaFactory extends Factory
{
    protected $model = Categoria::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->words(2, true),
            'nivel' => $this->faker->numberBetween(1, 3),
            'padre_id' => null, // se puede actualizar luego en un seeder
        ];
    }
}
