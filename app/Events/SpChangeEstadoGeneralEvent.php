<?php

namespace App\Events;

use App\Models\SolicitudPago;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SpChangeEstadoGeneralEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public SolicitudPago $sp,
        public string $estadoAnterior,
        public string $estadoNuevo
    ) {}
}
