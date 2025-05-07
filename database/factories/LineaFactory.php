<?php

namespace Database\Factories;

use App\Models\Linea;
use App\Models\Marca;
use Illuminate\Database\Eloquent\Factories\Factory;

class LineaFactory extends Factory
{
    protected $model = Linea::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->unique()->company,
            'estatus' => 'activo',
        ];
    }
}
