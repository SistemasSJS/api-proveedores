<?php

namespace App\Listeners;

use App\Events\RequisicionCreated;
use App\Services\NotificacionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendRequisicionNotification
{
    public function handle(RequisicionCreated $event)
    {
        NotificacionService::enviarNuevaRequisicion($event->requisicion);
    }
}
