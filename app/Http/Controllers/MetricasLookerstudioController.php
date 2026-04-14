<?php

namespace App\Http\Controllers;

use App\Models\Presupuesto;
use App\Models\SolicitudPago;
use Illuminate\Http\Request;


class MetricasLookerstudioController extends Controller
{

  /**
   * metricas de gestion pro:
   *  - usuario activos con actividad de los últimos 15 días
   */
  public function metricasLookerstudio(Request $request)
  {
    $fechaLimite = now()->subDays(15);

    $usuariosSPP = SolicitudPago::where('created_at', '>=', $fechaLimite)
      ->whereNotNull('usuario_creador_id')
      ->pluck('usuario_creador_id');

    $usuariosPresupuesto = Presupuesto::where('created_at', '>=', $fechaLimite)
      ->whereNotNull('user_id')
      ->pluck('user_id');

    $usuariosUnicos = $usuariosSPP
      ->merge($usuariosPresupuesto)
      ->unique()
      ->count();

    $data = [
      'usuarios' => $usuariosUnicos,
    ];

    return $this->success($data, 'correcto');
  }
}
