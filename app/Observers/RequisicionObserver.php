<?php

namespace App\Observers;

use App\Models\Requisicion;
use App\Events\RequisicionCreated;
use App\Events\RequisicionStatusChanged;
use App\Services\AuditService;
use App\Services\NotificacionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RequisicionObserver
{
    public function created(Requisicion $requisicion)
    {
        // Usar NotificacionService estático
        NotificacionService::enviarNuevaRequisicion($requisicion);

        // Usar AuditService para logging
        AuditService::logAction(
            'created',
            'Requisicion',
            $requisicion->id,
            [
                'usuario_id' => $requisicion->usuario_id,
                'proveedor_id' => $requisicion->proveedor_id,
                'total_estimado' => $requisicion->total_estimado,
                'productos_count' => $requisicion->detalles()->count(),
            ]
        );

        // Disparar evento
        event(new RequisicionCreated($requisicion));
    }

    public function updating(Requisicion $requisicion)
    {
        $requisicion->_previousStatus = $requisicion->getOriginal('estatus');
    }

    public function updated(Requisicion $requisicion)
    {
        if ($requisicion->isDirty('estatus')) {
            $previousStatus = $requisicion->_previousStatus;

            // Usar NotificacionService
            NotificacionService::enviarCambioEstatusRequisicion($requisicion);

            // Usar AuditService
            AuditService::logSensitiveChange(
                'Requisicion',
                $requisicion->id,
                [
                    'estatus_anterior' => $previousStatus,
                    'estatus_nuevo' => $requisicion->estatus,
                    'observaciones_proveedor' => $requisicion->observaciones_proveedor,
                ]
            );

            // Disparar evento
            event(new RequisicionStatusChanged($requisicion, $previousStatus));
        }
    }

    public function deleted(Requisicion $requisicion)
    {
        AuditService::logAction(
            'deleted',
            'Requisicion',
            $requisicion->id,
            [
                'numero_requisicion' => $requisicion->numero_requisicion,
                'estatus_final' => $requisicion->estatus,
            ]
        );
    }
}
