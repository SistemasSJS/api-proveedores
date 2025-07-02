<?php

namespace Database\Factories;

use App\Models\AccesoRapido;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccesoRapidoFactory extends Factory
{
    protected $model = AccesoRapido::class;

    public function definition(): array
    {
        $iconos = [
            'storefront',
            'bag-check',
            'cart-plus',
            'star',
            'clock-history',
            'graph-up',
            'person-circle',
            'gear'
        ];

        $colores = [
            '#007bff',
            '#28a745',
            '#dc3545',
            '#ffc107',
            '#17a2b8',
            '#6f42c1',
            '#e83e8c',
            '#fd7e14'
        ];

        return [
            'titulo' => $this->faker->words(2, true),
            'descripcion' => $this->faker->sentence(),
            'icono' => $this->faker->randomElement($iconos),
            'url' => $this->faker->url(),
            'color' => $this->faker->randomElement($colores),
            'orden' => $this->faker->numberBetween(1, 10),
            'activo' => $this->faker->boolean(90)
        ];
    }

    public function activo(): static
    {
        return $this->state(fn(array $attributes) => [
            'activo' => true,
        ]);
    }

    public function inactivo(): static
    {
        return $this->state(fn(array $attributes) => [
            'activo' => false,
        ]);
    }
}
