<?php

namespace App\Services;

use App\Models\Requisicion;
use App\Models\Producto;
use App\Models\RequisicionDetalle;
use Illuminate\Support\Facades\DB;

class RequisicionService
{
  /**
   * Crear una nueva requisición con sus detalles
   */
  public function crear(array $data, int $usuarioId): Requisicion
  {
    return DB::transaction(function () use ($data, $usuarioId) {
      $requisicion = Requisicion::create([
        'usuario_id' => $usuarioId,
        'proveedor_id' => $data['proveedor_id'],
        'fecha_requerida' => $data['fecha_requerida'],
        'observaciones' => $data['observaciones'] ?? null,
        'estatus' => 'pendiente',
        'total_estimado' => 0,
      ]);

      $totalEstimado = 0;
      foreach ($data['productos'] as $productoData) {
        $producto = Producto::find($productoData['producto_id']);
        $subtotal = $producto->precio_base * $productoData['cantidad'];
        $totalEstimado += $subtotal;

        RequisicionDetalle::create([
          'requisicion_id' => $requisicion->id,
          'producto_id' => $productoData['producto_id'],
          'cantidad' => $productoData['cantidad'],
          'precio_unitario_estimado' => $producto->precio_base,
          'subtotal_estimado' => $subtotal,
          'observaciones' => $productoData['observaciones'] ?? null,
        ]);
      }

      $requisicion->update(['total_estimado' => $totalEstimado]);
      return $requisicion->load(['proveedor', 'detalles.producto']);
    });
  }

  /**
   * Cambiar estatus de requisición con validaciones
   */
  public function cambiarEstatus(Requisicion $requisicion, string $nuevoEstatus, ?string $observaciones = null): bool
  {
    $estatusPermitidos = [
      'pendiente' => ['en_proceso', 'rechazada'],
      'en_proceso' => ['cotizada', 'rechazada', 'entregada'],
      'cotizada' => ['entregada', 'rechazada'],
    ];

    if (
      !isset($estatusPermitidos[$requisicion->estatus]) ||
      !in_array($nuevoEstatus, $estatusPermitidos[$requisicion->estatus])
    ) {
      return false;
    }

    $requisicion->update([
      'estatus' => $nuevoEstatus,
      'observaciones_proveedor' => $observaciones,
    ]);

    return true;
  }

  /**
   * Cancelar requisición
   */
  public function cancelar(Requisicion $requisicion, string $motivo): bool
  {
    if (!in_array($requisicion->estatus, ['pendiente', 'en_proceso'])) {
      return false;
    }

    $requisicion->update([
      'estatus' => 'cancelada',
      'fecha_cancelacion' => now(),
      'motivo_cancelacion' => $motivo,
    ]);

    return true;
  }

  /**
   * Obtener estadísticas de requisiciones para un usuario
   */
  public function getEstadisticasParaUsuario(int $usuarioId): array
  {
    $requisiciones = Requisicion::where('usuario_id', $usuarioId);

    return [
      'total' => $requisiciones->count(),
      'pendientes' => $requisiciones->where('estatus', 'pendiente')->count(),
      'en_proceso' => $requisiciones->where('estatus', 'en_proceso')->count(),
      'cotizadas' => $requisiciones->where('estatus', 'cotizada')->count(),
      'entregadas' => $requisiciones->where('estatus', 'entregada')->count(),
      'canceladas' => $requisiciones->where('estatus', 'cancelada')->count(),
      'rechazadas' => $requisiciones->where('estatus', 'rechazada')->count(),
    ];
  }


  /**
   * MÉTODO FALTANTE: Obtener estadísticas de requisiciones para un proveedor
   */
  public function getEstadisticasParaProveedor(int $proveedorId): array
  {
    $requisiciones = Requisicion::where('proveedor_id', $proveedorId);

    $stats = [
      'total' => $requisiciones->count(),
      'pendientes' => $requisiciones->where('estatus', 'pendiente')->count(),
      'en_proceso' => $requisiciones->where('estatus', 'en_proceso')->count(),
      'cotizadas' => $requisiciones->where('estatus', 'cotizada')->count(),
      'entregadas' => $requisiciones->where('estatus', 'entregada')->count(),
      'canceladas' => $requisiciones->where('estatus', 'cancelada')->count(),
      'rechazadas' => $requisiciones->where('estatus', 'rechazada')->count(),
      'este_mes' => $requisiciones->whereMonth('created_at', now()->month)->count(),
      'total_monto' => $requisiciones->whereIn('estatus', ['cotizada', 'entregada'])->sum('total_estimado'),
    ];

    // Estadísticas adicionales para proveedor
    $stats['promedio_tiempo_respuesta'] = $this->calcularTiempoPromedioRespuesta($proveedorId);
    $stats['tasa_conversion'] = $this->calcularTasaConversion($proveedorId);

    return $stats;
  }

  /**
   * Calcular tiempo promedio de respuesta del proveedor
   */
  private function calcularTiempoPromedioRespuesta(int $proveedorId): float
  {
    $requisiciones = Requisicion::where('proveedor_id', $proveedorId)
      ->where('estatus', '!=', 'pendiente')
      ->whereNotNull('updated_at')
      ->get();

    if ($requisiciones->isEmpty()) {
      return 0;
    }

    $tiempos = $requisiciones->map(function ($req) {
      return $req->created_at->diffInHours($req->updated_at);
    });

    return round($tiempos->avg(), 2);
  }

  /**
   * Calcular tasa de conversión del proveedor
   */
  private function calcularTasaConversion(int $proveedorId): float
  {
    $totalRequisiciones = Requisicion::where('proveedor_id', $proveedorId)->count();

    if ($totalRequisiciones === 0) {
      return 0;
    }

    $exitosas = Requisicion::where('proveedor_id', $proveedorId)
      ->whereIn('estatus', ['cotizada', 'entregada'])
      ->count();

    return round(($exitosas / $totalRequisiciones) * 100, 2);
  }
}
