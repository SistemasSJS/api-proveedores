<?php

namespace App\Http\Controllers;

use App\Models\Presupuesto;
use App\Models\Proveedor;
use App\Models\SolicitudPago;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminHomeControler extends Controller
{
    use ApiResponse;

    /**
     * Endpoint principal del dashboard administrativo.
     * Retorna en una sola llamada:
     *  - Totales generales (usuarios registrados, empresas/proveedores registrados)
     *  - Usuarios activos en los últimos 15 días
     *  - Promedio de presupuesto por usuario (últimos 15 días)
     *  - Promedio de SPP por usuario (últimos 15 días)
     *  - Serie diaria de los últimos 15 días (usuarios activos, presupuestos, SPP)
     */
    public function dashboardDatos(Request $request)
    {
        $dias = (int) $request->input('dias', 15);
        $dias = max(1, min($dias, 90)); // entre 1 y 90
        $fechaLimite = Carbon::now()->subDays($dias)->startOfDay();

        return $this->success([
            'periodo_dias'          => $dias,
            'fecha_desde'           => $fechaLimite->toDateString(),
            'fecha_hasta'           => Carbon::now()->toDateString(),
            'totales'               => $this->getTotalesGenerales(),
            'usuarios_activos'      => $this->getUsuariosActivosDatos($fechaLimite),
            'promedio_presupuesto'  => $this->getPromedioPresupuestoPorUsuario($fechaLimite),
            'promedio_spp'          => $this->getPromedioSppPorUsuario($fechaLimite),
            'serie_diaria'          => $this->getSerieDiaria($fechaLimite, $dias),
        ]);
    }

    /**
     * Endpoint legado — métricas home (catálogos + actividad 7 días).
     */
    public function metricasHome(Request $request)
    {
        $metricas = $this->getMetricasOptimizadas();
        return $this->success($metricas);
    }

    /**
     * Endpoint legado — resumen de catálogos.
     */
    public function getCatalogosCountItems(Request $request)
    {
        $catalogos = [
            [
                'name'  => 'Proveedores',
                'count' => Proveedor::withoutGlobalScope('solo_activos')->count(),
                'route' => '/pages/panel-admin/proveedores',
                'icon'  => 'briefcase',
            ],
            [
                'name'  => 'Usuarios',
                'count' => User::count(),
                'route' => '/pages/panel-admin/usuarios',
                'icon'  => 'people',
            ],
        ];

        return $this->success($catalogos);
    }

    /**
     * Endpoint legado — usuarios activos (método público requerido por ruta).
     * Acepta el parámetro ?dias=N (por defecto 15).
     */
    public function getUsuariosActivosEndpoint(Request $request)
    {
        $dias = (int) $request->input('dias', 15);
        $dias = max(1, min($dias, 90));
        $fechaLimite = Carbon::now()->subDays($dias)->startOfDay();

        $datos = $this->getUsuariosActivosDatos($fechaLimite);

        return $this->success([
            'activos'    => $datos['total'],
            'total'      => User::count(),
            'porcentaje' => $datos['porcentaje'],
            'periodo_dias' => $dias,
        ]);
    }

    // =========================================================
    // PRIVADOS — lógica de negocio
    // =========================================================

    /**
     * Totales generales (sin filtro de fecha).
     */
    private function getTotalesGenerales(): array
    {
        $totalUsuarios   = User::count();
        $totalEmpresas   = Proveedor::withoutGlobalScope('solo_activos')->count();

        return [
            'total_usuarios'  => $totalUsuarios,
            'total_empresas'  => $totalEmpresas,
        ];
    }

    /**
     * Usuarios únicos que tuvieron actividad en el período.
     * Actividad = creó/actualizó presupuesto O creó SPP.
     */
    private function getUsuariosActivosDatos(Carbon $fechaLimite): array
    {
        $idsPresupuestos = DB::table('presupuestos')
            ->where('updated_at', '>=', $fechaLimite)
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id')
            ->toArray();

        $idsSpp = DB::table('solicitudes_pago')
            ->where('updated_at', '>=', $fechaLimite)
            ->whereNotNull('usuario_creador_id')
            ->distinct()
            ->pluck('usuario_creador_id')
            ->toArray();

        $idsActivos = array_values(array_unique(array_merge($idsPresupuestos, $idsSpp)));
        $totalActivos = count($idsActivos);
        $totalUsuarios = User::count();

        $porcentaje = $totalUsuarios > 0
            ? round(($totalActivos / $totalUsuarios) * 100, 2) . '%'
            : '0%';

        return [
            'total'       => $totalActivos,
            'porcentaje'  => $porcentaje,
        ];
    }

    /**
     * Promedio de presupuestos por usuario activo en el período.
     */
    private function getPromedioPresupuestoPorUsuario(Carbon $fechaLimite): array
    {
        $resultado = DB::table('presupuestos')
            ->selectRaw('COUNT(*) as total_presupuestos, COUNT(DISTINCT user_id) as usuarios_con_presupuesto, COALESCE(AVG(total), 0) as promedio_monto')
            ->where('updated_at', '>=', $fechaLimite)
            ->whereNotNull('user_id')
            ->first();

        $totalPresupuestos      = (int)   ($resultado->total_presupuestos ?? 0);
        $usuariosCon            = (int)   ($resultado->usuarios_con_presupuesto ?? 0);
        $promedioMonto          = (float) ($resultado->promedio_monto ?? 0);
        $promedioXUsuario       = $usuariosCon > 0 ? round($totalPresupuestos / $usuariosCon, 2) : 0;

        return [
            'total_presupuestos'          => $totalPresupuestos,
            'usuarios_con_presupuesto'    => $usuariosCon,
            'promedio_presupuestos_x_usuario' => $promedioXUsuario,
            'promedio_monto'              => round($promedioMonto, 2),
        ];
    }

    /**
     * Promedio de SPP por usuario activo en el período.
     */
    private function getPromedioSppPorUsuario(Carbon $fechaLimite): array
    {
        $resultado = DB::table('solicitudes_pago')
            ->selectRaw('COUNT(*) as total_spp, COUNT(DISTINCT usuario_creador_id) as usuarios_con_spp, COALESCE(AVG(monto_total), 0) as promedio_monto')
            ->where('updated_at', '>=', $fechaLimite)
            ->whereNotNull('usuario_creador_id')
            ->first();

        $totalSpp         = (int)   ($resultado->total_spp ?? 0);
        $usuariosCon      = (int)   ($resultado->usuarios_con_spp ?? 0);
        $promedioMonto    = (float) ($resultado->promedio_monto ?? 0);
        $promedioXUsuario = $usuariosCon > 0 ? round($totalSpp / $usuariosCon, 2) : 0;

        return [
            'total_spp'               => $totalSpp,
            'usuarios_con_spp'        => $usuariosCon,
            'promedio_spp_x_usuario'  => $promedioXUsuario,
            'promedio_monto'          => round($promedioMonto, 2),
        ];
    }

    /**
     * Serie diaria de los últimos N días.
     * Para cada día: usuarios activos (presupuesto o SPP), presupuestos creados, SPP creadas.
     */
    private function getSerieDiaria(Carbon $fechaLimite, int $dias): array
    {
        // Presupuestos por día
        $presupuestosPorDia = DB::table('presupuestos')
            ->selectRaw('DATE(updated_at) as fecha, COUNT(*) as total, COUNT(DISTINCT user_id) as usuarios')
            ->where('updated_at', '>=', $fechaLimite)
            ->groupByRaw('DATE(updated_at)')
            ->get()
            ->keyBy('fecha');

        // SPP por día
        $sppPorDia = DB::table('solicitudes_pago')
            ->selectRaw('DATE(updated_at) as fecha, COUNT(*) as total, COUNT(DISTINCT usuario_creador_id) as usuarios')
            ->where('updated_at', '>=', $fechaLimite)
            ->whereNotNull('usuario_creador_id')
            ->groupByRaw('DATE(updated_at)')
            ->get()
            ->keyBy('fecha');

        $serie = [];
        for ($i = $dias - 1; $i >= 0; $i--) {
            $fecha     = Carbon::now()->subDays($i)->toDateString();
            $presDia   = $presupuestosPorDia->get($fecha);
            $sppDia    = $sppPorDia->get($fecha);

            $usuariosPresupuesto = (int) ($presDia->usuarios ?? 0);
            $usuariosSpp         = (int) ($sppDia->usuarios ?? 0);

            // Usuarios únicos del día (aproximado — suma sin duplicar)
            // No hacemos UNION en PHP para simplificar; se muestra como "al menos N"
            $usuariosActivos = max($usuariosPresupuesto, $usuariosSpp);

            $serie[] = [
                'fecha'            => $fecha,
                'usuarios_activos' => $usuariosActivos,
                'presupuestos'     => (int) ($presDia->total ?? 0),
                'spp'              => (int) ($sppDia->total ?? 0),
            ];
        }

        return $serie;
    }

    /**
     * Métricas optimizadas para el endpoint legado (7 días).
     */
    private function getMetricasOptimizadas(): array
    {
        $fechaLimite = Carbon::now()->subDays(7)->startOfDay();

        $conteos = DB::select("
            SELECT
                (SELECT COUNT(*) FROM proveedores) as total_proveedores,
                (SELECT COUNT(*) FROM users) as total_usuarios,
                (
                    SELECT COUNT(DISTINCT u.id)
                    FROM users u
                    WHERE u.id IN (
                        SELECT DISTINCT p.user_id FROM presupuestos p
                        WHERE p.updated_at >= ? AND p.user_id IS NOT NULL
                    )
                    OR u.id IN (
                        SELECT DISTINCT sp.usuario_creador_id FROM solicitudes_pago sp
                        WHERE sp.updated_at >= ? AND sp.usuario_creador_id IS NOT NULL
                    )
                ) as usuarios_activos_7dias
        ", [$fechaLimite, $fechaLimite]);

        $conteo = $conteos[0];

        return [
            'catalogos' => [
                [
                    'name'  => 'Proveedores',
                    'count' => (int) $conteo->total_proveedores,
                    'route' => '/pages/panel-admin/proveedores',
                    'icon'  => 'briefcase',
                ],
                [
                    'name'  => 'Usuarios',
                    'count' => (int) $conteo->total_usuarios,
                    'route' => '/pages/panel-admin/usuarios',
                    'icon'  => 'people',
                ],
            ],
            'metricas_actividad' => [
                'usuarios_activos_ultimos_7_dias' => (int) $conteo->usuarios_activos_7dias,
                'porcentaje_actividad'            => $this->calcularPorcentajeActividad(
                    (int) $conteo->usuarios_activos_7dias,
                    (int) $conteo->total_usuarios
                ),
                'fecha_referencia' => Carbon::now()->subDays(7)->toDateString(),
            ],
        ];
    }

    private function calcularPorcentajeActividad(int $activos, int $total): string
    {
        if ($total === 0) {
            return '0%';
        }
        return round(($activos / $total) * 100, 2) . '%';
    }
}