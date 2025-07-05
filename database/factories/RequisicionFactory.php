<?php

namespace Database\Factories;

use App\Models\Requisicion;
use App\Models\User;
use App\Models\Proveedor;
use Illuminate\Database\Eloquent\Factories\Factory;

class RequisicionFactory extends Factory
{
    protected $model = Requisicion::class;

    public function definition()
    {
        $fechaCreacion = $this->faker->dateTimeBetween('-6 months', 'now');
        $fechaLimite = $this->faker->dateTimeBetween($fechaCreacion, '+30 days');

        return [
            'usuario_id' => User::factory(),
            'proveedor_id' => Proveedor::factory(),
            'numero_requisicion' => 'REQ-' . date('Y') . '-' . str_pad($this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'fecha_requerida' => $fechaLimite,
            'prioridad' => $this->faker->randomElement(['baja', 'media', 'alta']),
            'estatus' => $this->faker->randomElement(['pendiente', 'en_proceso', 'cotizada', 'finalizada', 'cancelada']),
            'observaciones' => $this->faker->optional()->paragraph(2),
            'total_estimado' => $this->faker->numberBetween(10000, 1000000),
        ];
    }

    public function pendiente()
    {
        return $this->state(function (array $attributes) {
            return [
                'estatus' => 'pendiente',
            ];
        });
    }

    public function enProceso()
    {
        return $this->state(function (array $attributes) {
            return [
                'estatus' => 'en_proceso',
            ];
        });
    }

    public function cotizada()
    {
        return $this->state(function (array $attributes) {
            return [
                'estatus' => 'cotizada',
            ];
        });
    }

    public function finalizada()
    {
        return $this->state(function (array $attributes) {
            return [
                'estatus' => 'finalizada',
            ];
        });
    }

    public function prioridadAlta()
    {
        return $this->state(function (array $attributes) {
            return [
                'prioridad' => 'alta',
            ];
        });
    }
}
