<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cotizacion;
use App\Models\CotizacionDetalle;
use App\Models\Requisicion;
use App\Models\RequisicionDetalle;

class CotizacionesSeeder extends Seeder
{
  public function run()
  {
    $requisiciones = Requisicion::whereIn('estatus', ['en_proceso', 'cotizada'])->get();

    foreach ($requisiciones as $requisicion) {
      // Crear 1-2 cotizaciones por requisición
      $numCotizaciones = rand(1, 2);

      for ($i = 0; $i < $numCotizaciones; $i++) {
        $fechaVencimiento = now()->addDays(rand(15, 45));
        $descuento = rand(0, 15);

        $cotizacion = Cotizacion::create([
          'requisicion_id' => $requisicion->id,
          'fecha_cotizacion' => now()->subDays(rand(1, 5)),
          'fecha_vencimiento' => $fechaVencimiento,
          'total' => 0,
          'observaciones' => 'Cotización generada según especificaciones de la requisición',
          // 'estatus' => collect(['pendiente', 'enviada', 'aceptada', 'rechazada', 'vencida'])->random(),
          // 'subtotal' => 0,
          // 'descuento' => $descuento,
          // 'impuestos' => 16,
          // 'condiciones_pago' => '30 días contado',
          // 'tiempo_entrega' => rand(5, 15) . ' días hábiles',
          // 'validez_oferta' => $fechaVencimiento->format('Y-m-d'),
          // 'garantia' => '1 año por defectos de fabricación'
        ]);

        // Crear detalles de la cotización
        $detallesRequisicion = RequisicionDetalle::where('requisicion_id', $requisicion->id)->get();
        $subtotal = 0;

        foreach ($detallesRequisicion as $detalle) {
          $precioUnitario = $detalle->precio_estimado * (1 + (rand(-5, 15) / 100));
          $cantidadCotizada = $detalle->cantidad_solicitada * (rand(80, 100) / 100);
          $total = $precioUnitario * $cantidadCotizada;

          CotizacionDetalle::create([
            'cotizacion_id' => $cotizacion->id,
            'requisicion_detalle_id' => $detalle->id,
            'producto_id' => $detalle->producto->id,
            'cantidad_cotizada' => $cantidadCotizada,
            'precio_unitario' => $precioUnitario,
            'subtotal' => $total,
            'tiempo_entrega_dias' => rand(3, 10) . ' días',
            'observaciones' => 'Observaciones específicas del producto cotizado'
          ]);

          $subtotal += $total;
        }

        // Actualizar totales de la cotización
        $impuestos = $subtotal * 0.16;
        $totalFinal = $subtotal - ($subtotal * $descuento / 100) + $impuestos;

        $cotizacion->update([
          'total' => $totalFinal,
        ]);
      }
    }
  }
}
