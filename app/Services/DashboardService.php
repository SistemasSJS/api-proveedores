<?php

namespace App\Services;

use App\Models\User;
use App\Models\Proveedor;
use App\Models\Producto;
use App\Models\Requisicion;
use App\Models\Sucursal;
use Illuminate\Support\Facades\DB;

class DashboardService
{
  /**
   * Estadísticas para dashboard de cliente
   */
  public function getStatsCliente(int $usuarioId): array
  {
    $user = User::findOrFail($usuarioId);

    // Estadísticas básicas
    $stats = [
      'requisiciones' => [
        'total' => $user->requisiciones()->count(),
        'pendientes' => $user->requisiciones()->where('estatus', 'pendiente')->count(),
        'en_proceso' => $user->requisiciones()->where('estatus', 'en_proceso')->count(),
        'cotizadas' => $user->requisiciones()->where('estatus', 'cotizada')->count(),
        'entregadas' => $user->requisiciones()->where('estatus', 'entregada')->count(),
        'canceladas' => $user->requisiciones()->where('estatus', 'cancelada')->count(),
        'este_mes' => $user->requisiciones()->whereMonth('created_at', now()->month)->count(),
      ],
      'notificaciones' => [
        'total' => $user->notificaciones()->count(),
        'no_leidas' => $user->notificaciones()->where('leida', false)->count(),
        'hoy' => $user->notificaciones()->whereDate('created_at', today())->count(),
      ],
      'actividad_reciente' => [
        'ultima_requisicion' => $user->requisiciones()->latest()->first()?->created_at,
        'proxima_entrega' => $user->requisiciones()
          ->where('estatus', 'en_proceso')
          ->orderBy('fecha_requerida', 'asc')
          ->first()?->fecha_requerida,
      ]
    ];

    return $stats;
  }

  /**
   * Estadísticas para dashboard de proveedor
   */
  public function getStatsProveedor(int $proveedorId): array
  {
    $proveedor = Proveedor::findOrFail($proveedorId);

    $stats = [
      'productos_activos' => $proveedor->productos()->where('activo', true)->count(),
      'sucursales_activas' => $proveedor->sucursales()->count(),
      'requisiciones_pendientes' => $proveedor->requisiciones()->where('estatus', 'pendiente')->count(),
      'requisiciones_mes' => $proveedor->requisiciones()->whereMonth('created_at', now()->month)->count(),
      'valor_total_requisiciones' => $proveedor->requisiciones()
        ->whereIn('estatus', ['cotizada', 'entregada'])
        ->sum('total_estimado'),
    ];

    return $stats;
  }

  /**
   * Estadísticas completas para administrador
   */
  public function getStatsAdmin(): array
  {
    $stats = [
      'usuarios' => [
        'total' => User::count(),
        'activos' => User::whereNotNull('email_verified_at')->count(),
        'nuevos_este_mes' => User::whereMonth('created_at', now()->month)->count(),
        'por_rol' => User::join('roles', 'users.role_id', '=', 'roles.id')
          ->select('roles.name', DB::raw('count(*) as cantidad'))
          ->groupBy('roles.name')
          ->get()
          ->pluck('cantidad', 'name'),
      ],
      'proveedores' => [
        'total' => Proveedor::count(),
        'activos' => Proveedor::where('estatus', 'activo')->count(),
        'pendientes' => Proveedor::where('estatus', 'pendiente')->count(),
        'con_productos' => Proveedor::has('productos')->count(),
        'con_sucursales' => Proveedor::has('sucursales')->count(),
      ],
      'productos' => [
        'total' => Producto::count(),
        'activos' => Producto::where('activo', true)->count(),
        'con_stock' => Producto::where('stock', '>', 0)->count(),
        'sin_stock' => Producto::where('stock', '=', 0)->count(),
      ],
      'requisiciones' => [
        'total' => Requisicion::count(),
        'este_mes' => Requisicion::whereMonth('created_at', now()->month)->count(),
        'pendientes' => Requisicion::where('estatus', 'pendiente')->count(),
        'en_proceso' => Requisicion::where('estatus', 'en_proceso')->count(),
        'completadas' => Requisicion::where('estatus', 'entregada')->count(),
      ],
      'sucursales' => [
        'total' => Sucursal::count(),
        'activas' => Sucursal::where('activa', true)->count(),
      ],
    ];

    return $stats;
  }

  /**
   * Crecimiento mensual del sistema
   */
  public function getCrecimientoMensual(int $meses = 12): array
  {
    $crecimiento = collect();

    for ($i = $meses - 1; $i >= 0; $i--) {
      $fecha = now()->subMonths($i);
      $mes = $fecha->format('M Y');

      $crecimiento->push([
        'mes' => $mes,
        'usuarios' => User::whereYear('created_at', $fecha->year)
          ->whereMonth('created_at', $fecha->month)->count(),
        'proveedores' => Proveedor::whereYear('created_at', $fecha->year)
          ->whereMonth('created_at', $fecha->month)->count(),
        'requisiciones' => Requisicion::whereYear('created_at', $fecha->year)
          ->whereMonth('created_at', $fecha->month)->count(),
        'productos' => Producto::whereYear('created_at', $fecha->year)
          ->whereMonth('created_at', $fecha->month)->count(),
      ]);
    }

    return $crecimiento->toArray();
  }

  /**
   * Top proveedores por actividad
   */
  public function getTopProveedores(int $limite = 10): array
  {
    return Proveedor::withCount(['requisiciones', 'productos'])
      ->orderBy('requisiciones_count', 'desc')
      ->limit($limite)
      ->get()
      ->map(function ($proveedor) {
        return [
          'id' => $proveedor->id,
          'nombre' => $proveedor->nombre_comercial,
          'requisiciones' => $proveedor->requisiciones_count,
          'productos' => $proveedor->productos_count,
          'estatus' => $proveedor->estatus,
        ];
      })->toArray();
  }
}
