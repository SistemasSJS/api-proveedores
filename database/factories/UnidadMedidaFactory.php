<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class UnidadMedidaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => ucfirst($this->faker->unique()->word),
            'descripcion' => $this->faker->sentence,
            'estatus' => 'activo',
        ];
    }
}
