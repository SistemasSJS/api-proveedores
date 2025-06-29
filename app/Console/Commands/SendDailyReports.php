<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;


class SendDailyReports extends Command
{
    protected $signature = 'reports:send-daily';
    protected $description = 'Envía reportes diarios a gerentes de proveedores';

    public function handle()
    {
        // Lógica para enviar reportes diarios
        $this->info('Reportes diarios enviados correctamente.');
    }
}