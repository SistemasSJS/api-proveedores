<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Notificacion;
use App\Services\AuditService;

/**
 * Comando para limpiar notificaciones antiguas leídas
 */
class CleanupNotifications extends Command
{
  protected $signature = 'notifications:cleanup {--days=30}';
  protected $description = 'Limpiar notificaciones antiguas leídas';

  public function handle()
  {
    $days = $this->option('days');

    $this->info("Iniciando limpieza de notificaciones...");
    $this->line("Eliminando notificaciones leídas de más de {$days} días");

    $deleted = Notificacion::where('created_at', '<', now()->subDays($days))
      ->where('leida', true)
      ->delete();

    $this->info("✓ Se eliminaron {$deleted} notificaciones antiguas");

    try {
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
    } catch (\Exception $e) {
      $this->warn("No se pudo registrar la auditoría: {$e->getMessage()}");
    }

    return 0;
  }
}
