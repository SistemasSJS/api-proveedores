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
            'sku' => $this->faker->unique()->ean8(),
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
            'peso_kg' => $this->faker->randomFloat(3, 0.1, 50),
            'dimensiones' => $this->faker->regexify('\d{1,2}x\d{1,2}x\d{1,2} cm'),
        ];
    }
}
