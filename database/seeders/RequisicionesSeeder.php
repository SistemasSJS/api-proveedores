<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Requisicion;
use App\Models\RequisicionDetalle;
use App\Models\User;
use App\Models\Proveedor;
use App\Models\Producto;

class RequisicionesSeeder extends Seeder
{
  public function run()
  {
    $clientes = User::where('role_id', 6)->get(); // Usuarios con rol CLIENTE
    $proveedores = Proveedor::get();

    foreach ($clientes as $cliente) {
      // Crear 2-4 requisiciones por cliente
      $numRequisiciones = rand(2, 4);

      for ($i = 0; $i < $numRequisiciones; $i++) {
        $proveedor = $proveedores->random();
        $productos = Producto::where('proveedor_id', $proveedor->id)
          ->inRandomOrder()
          ->limit(rand(2, 5))
          ->get();

        $total =  rand(50000, 500000);
        if ($productos->count() > 0) {
          $requisicion = Requisicion::create([
            'usuario_id' => $cliente->id,
            'proveedor_id' => $proveedor->id,
            'numero_requisicion' => 'REQ-' . now()->format('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
            'fecha_requerida' => now()->addDays(rand(10, 30)),
            // 'prioridad' => collect(['baja', 'media', 'alta'])->random(),
            'estatus' => collect( ['pendiente', 'en_proceso', 'cotizada', 'rechazada', 'entregada', 'cancelada'])->random(),
            'observaciones' => 'Observaciones generales de la requisición',
            'total_estimado' => $total
          ]);

          // Crear detalles de la requisición
          foreach ($productos as $producto) {
            RequisicionDetalle::create([
              'requisicion_id' => $requisicion->id,
              'producto_id' => $producto->id,
              'cantidad' => rand(1, 20),
              'precio_unitario' => $producto->precio * (1 + (rand(-10, 20) / 100)),
              'subtotal' => $total / 3,
            ]);
          }
        }
      }
    }
  }
}
