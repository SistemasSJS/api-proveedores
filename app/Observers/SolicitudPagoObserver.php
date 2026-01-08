<?php

namespace App\Observers;

use App\Models\SolicitudPago;
use App\Events\SpChangeEstadoGeneralEvent;

class SolicitudPagoObserver
{
    public function updated(SolicitudPago $sp)
    {
        // Solo disparar si cambió el estado
        if ($sp->isDirty('estado_solicitud')) {
            $estadoAnterior = $sp->getOriginal('estado_solicitud');
            $estadoNuevo = $sp->estado_solicitud;

            // Evitar disparar si es el mismo valor
            if ($estadoAnterior !== $estadoNuevo) {
                event(new SpChangeEstadoGeneralEvent($sp, $estadoAnterior, $estadoNuevo));
            }
        }
    }
}
