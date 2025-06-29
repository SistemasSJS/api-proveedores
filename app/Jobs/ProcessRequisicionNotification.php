<?php


namespace App\Jobs;

use App\Models\Requisicion;
use App\Services\RequisicionService;
use App\Services\NotificacionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessRequisicionNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private Requisicion $requisicion,
        private string $type
    ) {}

    public function handle(RequisicionService $requisicionService)
    {
        match ($this->type) {
            'nueva' => NotificacionService::enviarNuevaRequisicion($this->requisicion),
            'actualizada' => NotificacionService::enviarCambioEstatusRequisicion($this->requisicion),
            'cotizada' => NotificacionService::enviarCotizacionGenerada($this->requisicion),
            'cancelada' => NotificacionService::enviarRequisicionCancelada($this->requisicion),
        };

        // Generar estadísticas post-notificación
        $stats = $requisicionService->getEstadisticasParaUsuario($this->requisicion->usuario_id);
        Log::info('Estadísticas post-notificación', [
            'requisicion_id' => $this->requisicion->id,
            'tipo_notificacion' => $this->type,
            'stats' => $stats
        ]);
    }
}
