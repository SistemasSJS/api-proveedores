<?php

namespace App\Observers;

use App\Models\Producto;
use App\Services\AuditService;

class ProductoObserver
{
  public function updated(Producto $producto)
  {
    if ($producto->isDirty('stock')) {
      AuditService::logSensitiveChange(
        'Producto',
        $producto->id,
        [
          'sku' => $producto->sku,
          'stock_anterior' => $producto->getOriginal('stock'),
          'stock_nuevo' => $producto->stock,
          'diferencia' => $producto->stock - $producto->getOriginal('stock'),
        ]
      );
    }

    if ($producto->isDirty('activo') && !$producto->activo) {
      AuditService::logAction(
        'deactivated',
        'Producto',
        $producto->id,
        [
          'sku' => $producto->sku,
          'nombre' => $producto->nombre,
          'proveedor_id' => $producto->proveedor_id,
        ]
      );
    }
  }
}
