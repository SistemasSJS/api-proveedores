<?php

namespace App\Listeners;

use App\Events\RequisicionStatusChanged;
use App\Services\NotificacionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendStatusChangeNotification
{
    public function handle(RequisicionStatusChanged $event)
    {
        NotificacionService::enviarCambioEstatusRequisicion($event->requisicion);
    }
}
