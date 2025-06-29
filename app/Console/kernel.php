<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
  protected $commands = [
    Commands\GenerateMonthlyReports::class,
    Commands\CleanupNotifications::class,
    Commands\SendDailyReports::class,
    // Commands\ProcessPendingRequisiciones::class,
    // Commands\UpdateProductPrices::class,
    // Commands\BackupDatabase::class,
  ];

  protected function schedule(Schedule $schedule): void
  {
    // Reportes mensuales (día 1 de cada mes a las 6 AM)
    $schedule->command('reports:generate-monthly --all')
      ->monthlyOn(1, '06:00')
      ->withoutOverlapping()
      ->runInBackground();

    // Limpiar notificaciones antiguas (diario a las 2 AM)
    $schedule->command('notifications:cleanup --days=30')
      ->dailyAt('02:00')
      ->withoutOverlapping();

    // Procesar requisiciones pendientes (cada hora)
    $schedule->command('requisiciones:process-pending')
      ->hourly()
      ->withoutOverlapping();

    // Backup de base de datos (diario a las 3 AM)
    $schedule->command('backup:database')
      ->dailyAt('03:00')
      ->withoutOverlapping();

    // Limpiar logs antiguos (semanal)
    $schedule->command('log:clear --days=30')
      ->weekly()
      ->sundays()
      ->at('04:00');

    // Monitor de performance (cada 15 minutos)
    $schedule->command('monitor:performance')
      ->everyFifteenMinutes()
      ->withoutOverlapping();
  }

  protected function commands(): void
  {
    $this->load(__DIR__ . '/Commands');
    require base_path('routes/console.php');
  }
}
