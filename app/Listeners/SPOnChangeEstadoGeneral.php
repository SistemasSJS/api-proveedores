<?php

namespace App\Listeners;

use App\Events\SpChangeEstadoGeneralEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SPOnChangeEstadoGeneral
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    public function handle(SpChangeEstadoGeneralEvent $event): void
    {
        // 👉 Se ejecuta SIEMPRE que cambie estado_general

        Log::info('Listeners==>>Evento::SPChangeEstado', [
            'sp_id' => $event->sp->id,
            'de' => $event->estadoAnterior,
            'a' => $event->estadoNuevo,
        ]);

        // acción común:
        // - auditoría
        // - historial
        // - sync externo
        // $event->sp->markRead();
        $event->sp->addNotification();
    }
}
