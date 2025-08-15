<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\Linea;
use App\Models\Marca;
use App\Models\Proveedor;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductoFactory extends Factory
{
    public function definition()
    {
        return [
            'codigo' => $this->faker->unique()->ean8(),
            'nombre' => $this->faker->words(3, true),
            'descripcion' => $this->faker->paragraph(),
            'precio_base' => $this->faker->randomFloat(2, 10, 1000),
            'imagen_principal' => null,
            'proveedor_id' => Proveedor::factory(),
            'categoria_id' => Categoria::factory(),
            'marca_id' => Marca::factory(),
            'linea_id' => Linea::factory(),

            'activo' => true,
            'stock' => $this->faker->numberBetween(0, 100),
            'destacado' => $this->faker->boolean(20),
            'peso_kg' => $this->faker->randomFloat(3, 0.1, 50),
            'dimensiones' => $this->faker->regexify('\d{1,2}x\d{1,2}x\d{1,2} cm'),
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
            'precio' => $this->faker->randomFloat(2, 1000, 5000),
        ]);
    }

    public function precioBajo(): static
    {
        return $this->state(fn(array $attributes) => [
            'precio' => $this->faker->randomFloat(2, 10, 200),
        ]);
    }
}
