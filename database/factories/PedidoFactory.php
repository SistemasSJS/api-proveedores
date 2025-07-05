<?php

namespace Database\Factories;

use App\Models\Pedido;
use App\Models\Requisicion;
use App\Models\Cotizacion;
use Illuminate\Database\Eloquent\Factories\Factory;

class PedidoFactory extends Factory
{
    protected $model = Pedido::class;

    public function definition()
    {
        $fechaConfirmacion = $this->faker->dateTimeBetween('-2 months', 'now');
        $fechaEntregaEstimada = $this->faker->dateTimeBetween($fechaConfirmacion, '+21 days');
        $subtotal = $this->faker->numberBetween(10000, 500000);
        $descuento = $this->faker->numberBetween(0, 15);
        $impuestos = 16;
        $total = $subtotal * (1 - $descuento/100) * (1 + $impuestos/100);
        
        return [
            'requisicion_id' => Requisicion::factory(),
            'cotizacion_id' => Cotizacion::factory(),
            'numero_pedido' => 'PED-' . date('Y') . '-' . str_pad($this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'fecha_confirmacion' => $fechaConfirmacion,
            'fecha_entrega_estimada' => $fechaEntregaEstimada,
            'fecha_entrega_real' => $this->faker->optional(0.6)->dateTimeBetween($fechaEntregaEstimada, $fechaEntregaEstimada->modify('+5 days')),
            'estatus' => $this->faker->randomElement(['confirmado', 'en_preparacion', 'en_transito', 'entregado', 'facturado', 'cancelado']),
            'subtotal' => $subtotal,
            'descuento' => $descuento,
            'impuestos' => $impuestos,
            'total' => $total,
            'metodo_pago' => $this->faker->randomElement(['efectivo', 'transferencia', 'cheque', 'credito']),
            'condiciones_pago' => $this->faker->randomElement(['Contado', '15 días', '30 días', '45 días']),
            'direccion_entrega' => $this->faker->address(),
            'contacto_entrega' => $this->faker->name(),
            'telefono_entrega' => '667' . $this->faker->numberBetween(1000000, 9999999),
            'observaciones' => $this->faker->optional()->paragraph(2),
            'tracking_number' => 'TRK-' . strtoupper($this->faker->bothify('##??##??##')),
            'factura_numero' => $this->faker->optional(0.5)->bothify('FACT-####'),
            'motivo_cancelacion' => null,
            'created_at' => $fechaConfirmacion,
            'updated_at' => $fechaConfirmacion
        ];
    }

    public function confirmado()
    {
        return $this->state(function (array $attributes) {
            return [
                'estatus' => 'confirmado',
                'fecha_entrega_real' => null,
            ];
        });
    }

    public function entregado()
    {
        return $this->state(function (array $attributes) {
            return [
                'estatus' => 'entregado',
                'fecha_entrega_real' => $this->faker->dateTimeBetween($attributes['fecha_entrega_estimada'], '+3 days'),
                'factura_numero' => 'FACT-' . str_pad($this->faker->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            ];
        });
    }

    public function cancelado()
    {
        return $this->state(function (array $attributes) {
            return [
                'estatus' => 'cancelado',
                'motivo_cancelacion' => $this->faker->sentence(6),
                'fecha_entrega_real' => null,
            ];
        });
    }

    public function facturado()
    {
        return $this->state(function (array $attributes) {
            return [
                'estatus' => 'facturado',
                'fecha_entrega_real' => $this->faker->dateTimeBetween($attributes['fecha_entrega_estimada'], '+3 days'),
                'factura_numero' => 'FACT-' . str_pad($this->faker->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            ];
        });
    }
}
