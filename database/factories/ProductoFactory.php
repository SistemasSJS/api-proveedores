<?php

namespace Database\Factories;

use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\UnidadMedida;
use App\Models\Grupo;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductoFactory extends Factory
{
    protected $model = Producto::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->words(3, true),
            'descripcion' => $this->faker->sentence(10),
            'codigo_interno' => strtoupper('PRD-' . $this->faker->unique()->numberBetween(1000, 9999)),
            'precio_unitario' => $this->faker->randomFloat(2, 50, 500),
            'disponible' => $this->faker->boolean(80),
            'proveedor_id' => Proveedor::inRandomOrder()->first()?->id ?? 1,
            'unidad_medida_id' => UnidadMedida::inRandomOrder()->first()?->id ?? 1,
            'grupo_id' => Grupo::inRandomOrder()->first()?->id ?? 1,
        ];
    }
}
