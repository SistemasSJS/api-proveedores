<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notificacion>
 */
class NotificacionFactory extends Factory
{
    public function definition()
    {
        return [
            'usuario_id' => User::factory(),
            'tipo' => $this->faker->randomElement([
                'requisicion_nueva',
                'requisicion_actualizada',
                'cotizacion_recibida',
                'sistema'
            ]),
            'titulo' => $this->faker->sentence(4),
            'mensaje' => $this->faker->paragraph,
            'leida' => $this->faker->boolean(30), // 30% probabilidad de estar leída
            'fecha_lectura' => $this->faker->optional(0.3)->dateTimeThisMonth,
            'datos' => [
                'requisicion_id' => $this->faker->numberBetween(1, 100),
                'extra_info' => $this->faker->word,
            ],
        ];
    }

    public function noLeida()
    {
        return $this->state([
            'leida' => false,
            'fecha_lectura' => null,
        ]);
    }

    public function leida()
    {
        return $this->state([
            'leida' => true,
            'fecha_lectura' => $this->faker->dateTimeThisMonth,
        ]);
    }
}
