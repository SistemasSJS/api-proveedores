<?php


namespace App\Services;

use App\Models\Sucursal;
use Illuminate\Support\Facades\DB;

class SucursalService
{
  /**
   * Asignar productos a una sucursal
   */
  public function asignarProductos(int $sucursalId, array $productos): bool
  {
    return DB::transaction(function () use ($sucursalId, $productos) {
      $sucursal = Sucursal::findOrFail($sucursalId);

      foreach ($productos as $producto) {
        $sucursal->productos()->syncWithoutDetaching([
          $producto['id'] => [
            'stock_local' => $producto['stock_local'],
            'precio_local' => $producto['precio_local'] ?? null,
            'activo' => $producto['activo'] ?? true,
          ]
        ]);
      }

      return true;
    });
  }

  /**
   * Actualizar stock de múltiples productos en sucursal
   */
  public function actualizarStockMasivo(int $sucursalId, array $actualizaciones): bool
  {
    return DB::transaction(function () use ($sucursalId, $actualizaciones) {
      $sucursal = Sucursal::findOrFail($sucursalId);

      foreach ($actualizaciones as $actualizacion) {
        $sucursal->productos()->updateExistingPivot($actualizacion['producto_id'], [
          'stock_local' => $actualizacion['stock_local'],
          'precio_local' => $actualizacion['precio_local'] ?? null,
          'activo' => $actualizacion['activo'] ?? true,
        ]);
      }

      return true;
    });
  }

  /**
   * Transferir stock entre sucursales
   */
  public function transferirStock(int $sucursalOrigenId, int $sucursalDestinoId, array $transferencias): bool
  {
    return DB::transaction(function () use ($sucursalOrigenId, $sucursalDestinoId, $transferencias) {
      $sucursalOrigen = Sucursal::findOrFail($sucursalOrigenId);
      $sucursalDestino = Sucursal::findOrFail($sucursalDestinoId);

      foreach ($transferencias as $transferencia) {
        $productoId = $transferencia['producto_id'];
        $cantidad = $transferencia['cantidad'];

        // Verificar stock disponible en origen
        $stockOrigen = $sucursalOrigen->productos()
          ->where('producto_id', $productoId)
          ->first()?->pivot?->stock_local ?? 0;

        if ($stockOrigen < $cantidad) {
          throw new \Exception("Stock insuficiente en sucursal origen para producto {$productoId}");
        }

        // Reducir stock en origen
        $sucursalOrigen->productos()->updateExistingPivot($productoId, [
          'stock_local' => $stockOrigen - $cantidad,
        ]);

        // Aumentar stock en destino
        $stockDestino = $sucursalDestino->productos()
          ->where('producto_id', $productoId)
          ->first()?->pivot?->stock_local ?? 0;

        $sucursalDestino->productos()->syncWithoutDetaching([
          $productoId => [
            'stock_local' => $stockDestino + $cantidad,
            'activo' => true,
          ]
        ]);
      }

      return true;
    });
  }
}
