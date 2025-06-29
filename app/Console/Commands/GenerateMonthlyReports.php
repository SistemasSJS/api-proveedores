<?php

namespace App\Console\Commands;

use App\Services\ReporteService;
use App\Services\DashboardService;
use App\Services\AuditService;
use App\Models\Proveedor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateMonthlyReports extends Command
{
  protected $signature = 'reports:generate-monthly {--proveedor_id=} {--all}';
  protected $description = 'Generar reportes mensuales automáticamente';

  public function handle(ReporteService $reporteService)
  {
    $proveedorId = $this->option('proveedor_id');
    $all = $this->option('all');

    if ($all) {
      $proveedores = Proveedor::where('estatus', 'activo')->get();

      foreach ($proveedores as $proveedor) {
        $this->generarReporteProveedor($reporteService, $proveedor->id);
      }

      $this->info("Reportes generados para {$proveedores->count()} proveedores");
    } elseif ($proveedorId) {
      $this->generarReporteProveedor($reporteService, $proveedorId);
      $this->info("Reporte generado para proveedor {$proveedorId}");
    } else {
      $this->error('Especifica --proveedor_id=X o --all');
      return 1;
    }

    AuditService::logAction(
      'generate_monthly_reports',
      'Command',
      0,
      [
        'proveedor_id' => $proveedorId,
        'all' => $all,
        'timestamp' => now()
      ]
    );

    return 0;
  }

  private function generarReporteProveedor(ReporteService $reporteService, int $proveedorId)
  {
    $reportes = [
      'ventas' => $reporteService->reporteVentasProveedor($proveedorId, 'mes'),
      'productos_populares' => $reporteService->getProductosPopulares($proveedorId, 30),
      'estadisticas_generales' => $reporteService->reporteEstadisticasGenerales($proveedorId),
    ];

    // Guardar reportes
    foreach ($reportes as $tipo => $reporte) {
      Storage::disk('local')->put(
        "reportes/proveedor_{$proveedorId}_{$tipo}_" . now()->format('Y-m') . ".json",
        json_encode($reporte, JSON_PRETTY_PRINT)
      );
    }
  }
}
