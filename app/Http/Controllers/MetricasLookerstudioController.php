<?php

namespace App\Http\Controllers;

use App\Models\Presupuesto;
use App\Models\Proveedor;
use App\Models\SolicitudPago;
use App\Models\User;
use Illuminate\Http\Request;


class MetricasLookerstudioController extends Controller
{

  /**
   * metricas de gestion pro:
   *  - usuario activos con actividad de los últimos 15 días, divididos por dia 
   * 
   * SQLSTATE[42S22]: Column not found: 1054 Unknown column 'user_id' in 'field list'
select `user_id`, `updated_at` from `proveedores`
   */
  public function metricasLookerstudio(Request $request)
  {
    $fechaLimite = now()->subDays(350);

    // registro de actualizacion de datos de perfil: cuentas bacncarias, datos de contacto, etc
    $update_data = Proveedor::where('updated_at', '>=', $fechaLimite)
      ->orwhere('created_at', '>=', $fechaLimite)
      ->get(['id', 'updated_at']) // 👈 corregido
      ->map(function ($item) {
        return [
          'user_id' => optional($item->usuarioPrincipal())->id, // 👈 corregido para obtener el user_id del proveedo  r
          'fecha' => $item->updated_at->format('Y-m-d'),
        ];
      });

    $update_data_users = User::where('updated_at', '>=', $fechaLimite)
      ->orwhere('created_at', '>=', $fechaLimite)
      ->get(['id', 'updated_at'])
      ->map(function ($item) {
        return [
          'user_id' => $item->id,
          'fecha' => $item->updated_at->format('Y-m-d'),
        ];
      });


    $cuentas_bancarias = Proveedor::whereHas('cuentasBancarias', function ($query) use ($fechaLimite) {
      $query
        ->where('created_at', '>=', $fechaLimite)
        ->where('updated_at', '>=', $fechaLimite);
    })->get()->map(function ($item) {
      return [
        'user_id' => optional($item->usuarioPrincipal())->id,
        'fecha' => $item->cuentasBancarias()->latest()->first()->updated_at->format('Y-m-d'),
      ];
    });

    // Obtener datos SPP
    $spp = SolicitudPago::where('created_at', '>=', $fechaLimite)
      ->orWhere('updated_at', '>=', $fechaLimite)
      ->whereNotNull('usuario_creador_id')
      ->get(['usuario_creador_id', 'created_at'])
      ->map(function ($item) {
        return [
          'user_id' => $item->usuario_creador_id,
          'fecha' => $item->created_at->format('Y-m-d'),
        ];
      });

    // Obtener datos Presupuesto
    $presupuestos = Presupuesto::where('created_at', '>=', $fechaLimite)
      ->orWhere('updated_at', '>=', $fechaLimite)
      ->whereNotNull('user_id')
      ->get(['user_id', 'created_at'])
      ->map(function ($item) {
        return [
          'user_id' => $item->user_id,
          'fecha' => $item->created_at->format('Y-m-d'),
        ];
      });

    // Unificar ambas colecciones
    $acciones = $spp->concat($presupuestos, $update_data, $update_data_users, $cuentas_bancarias);

    // Agrupar por fecha y obtener usuarios únicos por día
    $result = $acciones
      ->groupBy('fecha')
      ->map(fn($items) => collect($items)->pluck('user_id')->unique()->count())
      ->sortKeys();

    return $this->success($result, 'Métricas de usuarios activos por día en los últimos 15   días');
  }
}
