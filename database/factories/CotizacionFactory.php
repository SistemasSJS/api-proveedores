<?php

namespace Database\Factories;

use App\Models\Cotizacion;
use App\Models\Requisicion;
use Illuminate\Database\Eloquent\Factories\Factory;

class CotizacionFactory extends Factory
{
    protected $model = Cotizacion::class;

    public function definition()
    {
        $fechaCotizacion = $this->faker->dateTimeBetween('-3 months', 'now');
        $fechaVencimiento = $this->faker->dateTimeBetween($fechaCotizacion, '+45 days');
        $subtotal = $this->faker->numberBetween(10000, 500000);
        $descuento = $this->faker->numberBetween(0, 15);
        $impuestos = 16;
        $total = $subtotal * (1 - $descuento/100) * (1 + $impuestos/100);
        
        return [
            'requisicion_id' => Requisicion::factory(),
            'numero_cotizacion' => 'COT-' . date('Y') . '-' . str_pad($this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'fecha_cotizacion' => $fechaCotizacion,
            'fecha_vencimiento' => $fechaVencimiento,
            'estatus' => $this->faker->randomElement(['pendiente', 'enviada', 'aceptada', 'rechazada', 'vencida']),
            'subtotal' => $subtotal,
            'descuento' => $descuento,
            'impuestos' => $impuestos,
            'total' => $total,
            'observaciones' => $this->faker->optional()->paragraph(2),
            'condiciones_pago' => $this->faker->randomElement(['Contado', '15 días', '30 días', '45 días', '60 días']),
            'tiempo_entrega' => $this->faker->numberBetween(3, 21) . ' días hábiles',
            'validez_oferta' => $fechaVencimiento->format('Y-m-d'),
            'garantia' => $this->faker->randomElement(['6 meses', '1 año', '2 años', 'Sin garantía']),
            'descuento_por_volumen' => $this->faker->optional()->numberBetween(5, 20),
            'created_at' => $fechaCotizacion,
            'updated_at' => $fechaCotizacion
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

    public function enviada()
    {
        return $this->state(function (array $attributes) {
            return [
                'estatus' => 'enviada',
            ];
        });
    }

    public function aceptada()
    {
        return $this->state(function (array $attributes) {
            return [
                'estatus' => 'aceptada',
            ];
        });
    }

    public function vencida()
    {
        return $this->state(function (array $attributes) {
            return [
                'estatus' => 'vencida',
                'fecha_vencimiento' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
            ];
        });
    }
}
