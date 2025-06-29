<?php

namespace App\Listeners;

use App\Events\CotizacionGenerated;
use App\Services\NotificacionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendCotizacionNotification
{
    public function handle(CotizacionGenerated $event)
    {
        NotificacionService::enviarCotizacionGenerada($event->requisicion);
    }
}
