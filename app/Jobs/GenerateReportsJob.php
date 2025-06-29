<?php

use App\Services\ReporteService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateReportsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $proveedorId,
        private string $reportType,
        private array $filters = []
    ) {}

    public function handle(ReporteService $reporteService)
    {
        $reporte = match ($this->reportType) {
            'ventas_mensuales' => $reporteService->reporteVentasProveedor(
                $this->proveedorId,
                'mes'
            ),
            'productos_populares' => $reporteService->getProductosPopulares(
                $this->proveedorId,
                30
            ),
            'inventario_completo' => $reporteService->reporteInventarioSucursales(
                $this->proveedorId,
                $this->filters
            ),
            'clientes_activos' => $reporteService->reporteClientesActivos(
                $this->proveedorId,
                90,
                50
            ),
        };

        // Guardar reporte generado
        Storage::disk('local')->put(
            "reportes/proveedor_{$this->proveedorId}_{$this->reportType}_" . now()->format('Y-m-d') . ".json",
            json_encode($reporte, JSON_PRETTY_PRINT)
        );

        Log::info("Reporte {$this->reportType} generado para proveedor {$this->proveedorId}");
    }
}
