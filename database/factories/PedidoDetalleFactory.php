<?php

namespace Database\Factories;

use App\Models\PedidoDetalle;
use App\Models\Pedido;
use App\Models\CotizacionDetalle;
use Illuminate\Database\Eloquent\Factories\Factory;

class PedidoDetalleFactory extends Factory
{
    protected $model = PedidoDetalle::class;

    public function definition()
    {
        $cantidadConfirmada = $this->faker->numberBetween(1, 50);
        $cantidadEntregada = $this->faker->numberBetween(0, $cantidadConfirmada);
        $precioUnitario = $this->faker->numberBetween(100, 10000);
        $descuento = $this->faker->numberBetween(0, 10);
        $total = $cantidadConfirmada * $precioUnitario * (1 - $descuento/100);
        
        return [
            'pedido_id' => Pedido::factory(),
            'cotizacion_detalle_id' => CotizacionDetalle::factory(),
            'producto_id' => CotizacionDetalle::factory(),
            'cantidad_confirmada' => $cantidadConfirmada,
            'cantidad_entregada' => $cantidadEntregada,
            'precio_unitario_final' => $precioUnitario,
            'descuento_aplicado' => $descuento,
            'total_detalle' => $total,
            'observaciones' => $this->faker->optional()->sentence(8),
            'lote_numero' => $this->faker->optional()->bothify('LOTE-###??'),
            'fecha_vencimiento' => $this->faker->optional()->dateTimeBetween('+6 months', '+2 years'),
            'condicion_producto' => $this->faker->randomElement(['nuevo', 'usado', 'reacondicionado'])
        ];
    }

    public function entregadoCompleto()
    {
        return $this->state(function (array $attributes) {
            return [
                'cantidad_entregada' => $attributes['cantidad_confirmada'],
            ];
        });
    }

    public function entregadoParcial()
    {
        return $this->state(function (array $attributes) {
            return [
                'cantidad_entregada' => $this->faker->numberBetween(1, $attributes['cantidad_confirmada'] - 1),
            ];
        });
    }

    public function sinEntregar()
    {
        return $this->state(function (array $attributes) {
            return [
                'cantidad_entregada' => 0,
            ];
        });
    }
}
