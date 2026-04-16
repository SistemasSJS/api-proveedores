<?php

namespace App\Http\Controllers;

use App\Models\Presupuesto;
use App\Models\Proveedor;
use App\Models\SolicitudPago;
use App\Models\User;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;


class MetricasLookerstudioController extends Controller
{

  use ApiResponse;
  /**
   * metricas de gestion pro:
   *  - usuario activos con actividad de los últimos 15 días, divididos por dia 
   * 
   */
  public function metricasLookerstudio(Request $request)
  {
    $fechaLimite = now()->subDays(6);

    // -------------------------
    // UPDATE PROVEEDOR
    // -------------------------
    $update_data = Proveedor::where('updated_at', '>=', $fechaLimite)
      ->get(['id', 'updated_at'])
      ->map(function ($item) {
        return [
          'user_id' => optional($item->usuarioPrincipal())->id,
          'fecha' => $item->updated_at?->format('Y-m-d'),
        ];
      });

    // -------------------------
    // UPDATE USERS
    // -------------------------
    $update_data_users = User::where('updated_at', '>=', $fechaLimite)
      ->get(['id', 'updated_at'])
      ->map(function ($item) {
        return [
          'user_id' => $item->id,
          'fecha' => $item->updated_at?->format('Y-m-d'),
        ];
      });

    // -------------------------
    // CUENTAS BANCARIAS
    // -------------------------
    $cuentas_bancarias = Proveedor::whereHas('cuentasBancarias', function ($query) use ($fechaLimite) {
      $query->where('updated_at', '>=', $fechaLimite);
    })
      ->get()
      ->map(function ($item) {
        $cuenta = $item->cuentasBancarias()->latest()->first();

        return [
          'user_id' => optional($item->usuarioPrincipal())->id,
          'fecha' => $cuenta?->updated_at?->format('Y-m-d'),
        ];
      });

    // -------------------------
    // SPP
    // -------------------------
    $spp = SolicitudPago::where('updated_at', '>=', $fechaLimite)
      ->whereNotNull('usuario_creador_id')
      ->get(['usuario_creador_id', 'updated_at'])
      ->map(function ($item) {
        return [
          'user_id' => $item->usuario_creador_id,
          'fecha' => $item->updated_at?->format('Y-m-d'),
        ];
      });

    // -------------------------
    // PRESUPUESTOS
    // -------------------------
    $presupuestos = Presupuesto::where('updated_at', '>=', $fechaLimite)
      ->whereNotNull('user_id')
      ->get(['user_id', 'updated_at'])
      ->map(function ($item) {
        return [
          'user_id' => $item->user_id,
          'fecha' => $item->updated_at?->format('Y-m-d'),
        ];
      });

    // -------------------------
    // UNIFICAR TODO
    // -------------------------
    $acciones = collect()
      ->concat($spp)
      ->concat($presupuestos)
      ->concat($update_data)
      ->concat($update_data_users)
      ->concat($cuentas_bancarias)
      ->filter(fn($item) => !empty($item['user_id']) && !empty($item['fecha']))
      ->map(function ($item) {
        return [
          'user_id' => $item['user_id'],
          'fecha' => \Carbon\Carbon::parse($item['fecha'])->toDateString(),
        ];
      });

    // -------------------------
    // AGRUPAR Y CONTAR
    // -------------------------
    $result = $acciones
      ->groupBy('fecha')
      ->map(function ($items, $fecha) {
        return [
          'fecha' => $fecha,
          'usuarios' => collect($items)->pluck('user_id')->unique()->count(),
        ];
      })
      ->sortBy('fecha')
      ->values();

    // -------------------------
    // GENERAR RANGO FIJO (7 días)
    // -------------------------
    $rangos = collect();

    for ($i = 6; $i >= 0; $i--) {
      $fecha = now()->subDays($i)->toDateString();
      $rangos[$fecha] = 0;
    }

    // -------------------------
    // SOBRESCRIBIR DATOS
    // -------------------------
    foreach ($result as $item) {
      $rangos[$item['fecha']] = $item['usuarios'];
    }

    // -------------------------
    // FORMATO FINAL
    // -------------------------
    $result = collect($rangos)
      ->map(fn($usuarios, $fecha) => [
        'fecha' => $fecha,
        'usuarios' => $usuarios,
      ])
      ->values();

    return $this->success($result, 'Metricas obtenidas correctamente');
  }
}
