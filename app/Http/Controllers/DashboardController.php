<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Services\ReporteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    protected $dashboardService;

    protected $reporteService;

    public function __construct(
        DashboardService $dashboardService,
        ReporteService $reporteService
    ) {
        $this->dashboardService = $dashboardService;
        $this->reporteService = $reporteService;
    }

    /**
     * @OA\Get(
     *     path="/api/dashboard/stats",
     *     summary="Obtener estadísticas del dashboard según rol de usuario",
     *     tags={"Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Estadísticas del dashboard",
     *         @OA\JsonContent(
     *             @OA\Property(property="tipo", type="string", example="admin"),
     *             @OA\Property(property="stats", type="object")
     *         )
     *     )
     * )
     */
    public function getStats(Request $request)
    {
        $user = Auth::user();

        if ($user->role->name === 'ADMINISTRADOR') {
            $stats = $this->dashboardService->getStatsAdmin();
            $crecimiento = $this->dashboardService->getCrecimientoMensual();
            $topProveedores = $this->dashboardService->getTopProveedores();

            return response()->json([
                'success' => true,
                'data' => [
                    'tipo' => 'admin',
                    'stats' => $stats,
                    'crecimiento_mensual' => $crecimiento,
                    'top_proveedores' => $topProveedores,
                ],
            ]);
        } elseif ($user->proveedores()->exists()) {
            $proveedorId = $user->proveedores()->first()->id;
            $stats = $this->dashboardService->getStatsProveedor($proveedorId);
            $estadisticasGenerales = $this->reporteService->reporteEstadisticasGenerales($proveedorId);

            return response()->json([
                'success' => true,
                'data' => [
                    'tipo' => 'proveedor',
                    'stats' => $stats,
                    'estadisticas_generales' => $estadisticasGenerales,
                ],
            ]);
        } else {
            $stats = $this->dashboardService->getStatsCliente($user->id);

            return response()->json([
                'success' => true,
                'data' => [
                    'tipo' => 'cliente',
                    'stats' => $stats,
                ],
            ]);
        }
    }
}
