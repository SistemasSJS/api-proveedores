<?php

namespace App\Console\Commands;

use App\Models\Notificacion;
use App\Models\Requisicion;
use App\Services\AuditService;
use Illuminate\Console\Command;

class CleanupNotifications extends Command
{
    protected $signature = 'notifications:cleanup {--days=30}';
    protected $description = 'Limpiar notificaciones antiguas leídas';

    public function handle()
    {
        $days = $this->option('days');

        $deleted = Notificacion::where('created_at', '<', now()->subDays($days))
            ->where('leida', true)
            ->delete();

        $this->info("Se eliminaron {$deleted} notificaciones antiguas");

        AuditService::logAction(
            'cleanup_notifications',
            'Command',
            0,
            [
                'deleted_count' => $deleted,
                'days_old' => $days,
                'criteria' => 'leidas y antiguas'
            ]
        );
    }
}
