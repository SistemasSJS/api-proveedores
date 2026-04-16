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
    $fechaLimite = now()->subDays(350);

    // -------------------------
    // UPDATE PROVEEDOR
    // -------------------------
    $update_data = Proveedor::where(function ($q) use ($fechaLimite) {
      $q->where('updated_at', '>=', $fechaLimite)
        ->orWhere('created_at', '>=', $fechaLimite);
    })
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
    $update_data_users = User::where(function ($q) use ($fechaLimite) {
      $q->where('updated_at', '>=', $fechaLimite)
        ->orWhere('created_at', '>=', $fechaLimite);
    })
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
      $query->where(function ($q) use ($fechaLimite) {
        $q->where('created_at', '>=', $fechaLimite)
          ->orWhere('updated_at', '>=', $fechaLimite);
      });
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
    $spp = SolicitudPago::where(function ($q) use ($fechaLimite) {
      $q->where('created_at', '>=', $fechaLimite)
        ->orWhere('updated_at', '>=', $fechaLimite);
    })
      ->whereNotNull('usuario_creador_id')
      ->get(['usuario_creador_id', 'created_at'])
      ->map(function ($item) {
        return [
          'user_id' => $item->usuario_creador_id,
          'fecha' => $item->created_at?->format('Y-m-d'),
        ];
      });

    // -------------------------
    // PRESUPUESTOS
    // -------------------------
    $presupuestos = Presupuesto::where(function ($q) use ($fechaLimite) {
      $q->where('created_at', '>=', $fechaLimite)
        ->orWhere('updated_at', '>=', $fechaLimite);
    })
      ->whereNotNull('user_id')
      ->get(['user_id', 'created_at'])
      ->map(function ($item) {
        return [
          'user_id' => $item->user_id,
          'fecha' => $item->created_at?->format('Y-m-d'),
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
      ->filter(fn($item) => $item['user_id'] && $item['fecha']);

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
    // RESPUESTA LIMPIA (LOOKER)
    // -------------------------
    return $this->success($result, 'Metricas obtenidas correctamente');
  }
}
