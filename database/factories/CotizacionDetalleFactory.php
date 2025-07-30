<?php

namespace Database\Factories;

use App\Models\CotizacionDetalle;
use App\Models\Cotizacion;
use App\Models\RequisicionDetalle;
use Illuminate\Database\Eloquent\Factories\Factory;

class CotizacionDetalleFactory extends Factory
{
    protected $model = CotizacionDetalle::class;

    public function definition()
    {
        $cantidad = $this->faker->numberBetween(1, 50);
        $precioUnitario = $this->faker->numberBetween(100, 10000);
        $descuento = $this->faker->numberBetween(0, 10);
        $total = $cantidad * $precioUnitario * (1 - $descuento/100);
        
        return [
            'cotizacion_id' => Cotizacion::factory(),
            'requisicion_detalle_id' => RequisicionDetalle::factory(),
            'producto_id' => RequisicionDetalle::factory(),
            'cantidad_cotizada' => $cantidad,
            'precio_unitario' => $precioUnitario,
            'descuento_detalle' => $descuento,
            'total' => $total,
            'tiempo_entrega_dias' => $this->faker->numberBetween(1, 15),
            'observaciones' => $this->faker->optional()->sentence(8),
            'disponibilidad' => $this->faker->randomElement(['inmediata', 'bajo_pedido', 'importacion']),
            'minimo_compra' => $this->faker->optional()->numberBetween(1, 10),
            'unidad_medida' => $this->faker->randomElement(['pza', 'kg', 'm', 'm2', 'm3', 'lts'])
        ];
    }

    public function disponibilidadInmediata()
    {
        return $this->state(function (array $attributes) {
            return [
                'disponibilidad' => 'inmediata',
                'tiempo_entrega_detalle' => '1-2 días',
            ];
        });
    }

    public function bajoPedido()
    {
        return $this->state(function (array $attributes) {
            return [
                'disponibilidad' => 'bajo_pedido',
                'tiempo_entrega_detalle' => $this->faker->numberBetween(7, 21) . ' días',
            ];
        });
    }
}
