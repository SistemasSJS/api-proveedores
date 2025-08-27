<?php

use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\Requisicion;
use App\Models\Producto;
use App\Models\ImportAudit;
use Illuminate\Support\Facades\DB;
use App\Models\Notificacion;
use App\Services\AuditService;
use Carbon\Carbon;
use App\Console\Commands\QueueMonitor;

// Comando original
Artisan::command('inspire', function () {
    /** @var ClosureCommand $this */
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Nota: En Laravel moderno, los comandos se auto-registran automáticamente
// El comando QueueMonitor debería estar disponible automáticamente
