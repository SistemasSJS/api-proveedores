<?php

namespace Database\Factories;

use App\Enums\EstadoGeneral;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Proveedor;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductoFactory extends Factory
{
    public function definition()
    {
        return [
            'imagen_principal' => null,
            'codigo' => $this->faker->unique()->ean8(),
            'proveedor_id' => Proveedor::factory(),
            'nombre' => $this->faker->words(3, true),
            'descripcion' => $this->faker->paragraph(),
            'marca_id' => Marca::factory(),
            'categoria_id' => Categoria::factory(),
            'precio_base' => $this->faker->randomFloat(2, 10, 1000),
            'precio_mayoreo' => $this->faker->randomFloat(2, 10, 1000),
            'precio_menudeo' => $this->faker->randomFloat(2, 10, 1000),
            'stock' => $this->faker->numberBetween(0, 100),
            'destacado' => $this->faker->boolean(20),
            'estatus' => EstadoGeneral::ACTIVO->value,
        ];
    }

    public function destacado(): static
    {
        return $this->state(fn(array $attributes) => [
            'destacado' => true,
            'activo' => true,
            'stock' => $this->faker->numberBetween(5, 50)
        ]);
    }

    public function activo(): static
    {
        return $this->state(fn(array $attributes) => [
            'activo' => true,
        ]);
    }

    public function conStock(): static
    {
        return $this->state(fn(array $attributes) => [
            'stock' => $this->faker->numberBetween(1, 100),
        ]);
    }

    public function sinStock(): static
    {
        return $this->state(fn(array $attributes) => [
            'stock' => 0,
        ]);
    }

    public function precioAlto(): static
    {
        return $this->state(fn(array $attributes) => [
            'precio_base' => $this->faker->randomFloat(2, 1000, 5000),
        ]);
    }

    public function precioBajo(): static
    {
        return $this->state(fn(array $attributes) => [
            'precio_base' => $this->faker->randomFloat(2, 10, 200),
        ]);
    }
}
