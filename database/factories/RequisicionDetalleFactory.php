<?php

namespace Database\Factories;

use App\Models\RequisicionDetalle;
use App\Models\Requisicion;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

class RequisicionDetalleFactory extends Factory
{
    protected $model = RequisicionDetalle::class;

    public function definition()
    {
        $cantidad = $this->faker->numberBetween(1, 50);
        $precio = $this->faker->numberBetween(100, 10000);
        
        return [
            'requisicion_id' => Requisicion::factory(),
            'producto_id' => Producto::factory(),
            'cantidad' => $cantidad,
            'precio_unitario' => $precio,
            'subtotal_estimado' => $cantidad * $precio,
            'observaciones' => $this->faker->optional()->sentence(8),
        ];
    }

    public function urgente()
    {
        return $this->state(function (array $attributes) {
            return [
                'urgente' => true,
            ];
        });
    }

    public function conAlternativas()
    {
        return $this->state(function (array $attributes) {
            return [
                'alternativas_aceptadas' => true,
            ];
        });
    }
}
