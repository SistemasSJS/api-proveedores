<?php

namespace App\Listeners;

use App\Events\SpChangeEstadoGeneralEvent;
use Illuminate\Support\Facades\Log;

class NotifyProveedorOnSpEstado
{
  public function handle(SpChangeEstadoGeneralEvent $event): void
  {
    // aquí SÍ se decide notificar
    // aquí SÍ se crea la notificación
    // aquí SÍ se guarda notification_id


    Log::info('Listeners NotificarEvento::SPChangeEstado', [
      'sp_id' => $event->sp->id,
      'de' => $event->estadoAnterior,
      'a' => $event->estadoNuevo,
    ]);
  }
}
