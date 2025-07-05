<?php


namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pedido;
use App\Models\PedidoDetalle;
use App\Models\Cotizacion;
use App\Models\CotizacionDetalle;

class PedidosSeeder extends Seeder
{
  public function run()
  {
    $cotizaciones = Cotizacion::where('estatus', 'aceptada')->get();

    foreach ($cotizaciones as $cotizacion) {
      // 70% de probabilidad de que una cotización aceptada genere un pedido
      if (rand(1, 100) <= 70) {
        $fechaConfirmacion = now()->subDays(rand(1, 30));
        $fechaEntregaEstimada = $fechaConfirmacion->copy()->addDays(rand(7, 21));

        $pedido = Pedido::create([
          'requisicion_id' => $cotizacion->requisicion_id,
          'cotizacion_id' => $cotizacion->id,
          'numero_pedido' => 'PED-' . now()->format('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
          'fecha_confirmacion' => $fechaConfirmacion,
          'fecha_entrega_estimada' => $fechaEntregaEstimada,
          'fecha_entrega_real' => rand(1, 100) <= 60 ? $fechaEntregaEstimada->copy()->addDays(rand(-2, 5)) : null,
          'estatus' => collect(['confirmado', 'en_preparacion', 'en_transito', 'entregado', 'facturado', 'cancelado'])->random(),
          'subtotal' => $cotizacion->subtotal,
          'descuento' => $cotizacion->descuento,
          'impuestos' => $cotizacion->impuestos,
          'total' => $cotizacion->total,
          'metodo_pago' => collect(['efectivo', 'transferencia', 'cheque', 'credito'])->random(),
          'condiciones_pago' => $cotizacion->condiciones_pago ?? '30 días contado',
          'direccion_entrega' => $cotizacion->requisicion->direccion_entrega,
          'contacto_entrega' => $cotizacion->requisicion->contacto_entrega,
          'telefono_entrega' => $cotizacion->requisicion->telefono_entrega,
          'observaciones' => 'Pedido generado desde cotización ' . $cotizacion->numero_cotizacion,
          'tracking_number' => 'TRK-' . strtoupper(substr(md5(time()), 0, 10)),
          'factura_numero' => rand(1, 100) <= 50 ? 'FACT-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT) : null
        ]);

        // Crear detalles del pedido
        $detallesCotizacion = CotizacionDetalle::where('cotizacion_id', $cotizacion->id)->get();

        foreach ($detallesCotizacion as $detalleCot) {
          // La cantidad confirmada puede ser menor o igual a la cotizada
          $cantidadConfirmada = $detalleCot->cantidad_cotizada * (rand(90, 100) / 100);

          PedidoDetalle::create([
            'pedido_id' => $pedido->id,
            'cotizacion_detalle_id' => $detalleCot->id,
            'producto_id' => $detalleCot->producto->id,
            'cantidad_confirmada' => $cantidadConfirmada,
            'cantidad_entregada' => $pedido->estatus === 'entregado' ? $cantidadConfirmada : 0,
            'precio_unitario_final' => $detalleCot->precio_unitario,
            'descuento_aplicado' => $detalleCot->descuento_detalle,
            'total_detalle' => $cantidadConfirmada * $detalleCot->precio_unitario * (1 - $detalleCot->descuento_detalle / 100),
            'observaciones' => 'Detalle del pedido confirmado'
          ]);
        }
      }
    }
  }
}
