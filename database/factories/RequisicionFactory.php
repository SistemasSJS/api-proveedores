<?php

namespace Database\Factories;

use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Requisicion>
 */
class RequisicionFactory extends Factory
{
    public function definition()
    {
        return [
            'usuario_id' => User::factory(),
            'proveedor_id' => Proveedor::factory(),
            'estatus' => $this->faker->randomElement(['pendiente', 'en_proceso', 'cotizada']),
            'fecha_requerida' => $this->faker->dateTimeBetween('+1 day', '+30 days'),
            'observaciones' => $this->faker->optional()->paragraph,
            'total_estimado' => $this->faker->randomFloat(2, 100, 10000),
        ];
    }

    public function pendiente()
    {
        return $this->state(['estatus' => 'pendiente']);
    }

    public function cotizada()
    {
        return $this->state(['estatus' => 'cotizada']);
    }

    public function cancelada()
    {
        return $this->state([
            'estatus' => 'cancelada',
            'fecha_cancelacion' => now(),
            'motivo_cancelacion' => $this->faker->sentence,
        ]);
    }
}
