<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AuditService;
use Carbon\Carbon;



/**
 * Comando para realizar backup de la base de datos
 */
class BackupDatabase extends Command
{
  protected $signature = 'db:backup 
                            {--compress : Comprimir el archivo de backup}
                            {--path= : Ruta personalizada para el backup}
                            {--tables= : Tablas específicas a respaldar (separadas por coma)}';

  protected $description = 'Realiza un backup completo de la base de datos';

  public function handle()
  {
    $compress = $this->option('compress');
    $customPath = $this->option('path');
    $specificTables = $this->option('tables');

    $this->info("Iniciando backup de la base de datos...");

    $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
    $dbName = config('database.connections.mysql.database');
    $fileName = "backup_{$dbName}_{$timestamp}.sql";

    if ($compress) {
      $fileName .= '.gz';
    }

    $backupPath = $customPath ?: storage_path('app/backups');

    if (!is_dir($backupPath)) {
      mkdir($backupPath, 0755, true);
    }

    $fullPath = $backupPath . '/' . $fileName;

    try {
      $host = config('database.connections.mysql.host');
      $username = config('database.connections.mysql.username');
      $password = config('database.connections.mysql.password');
      $database = config('database.connections.mysql.database');
      $port = config('database.connections.mysql.port', 3306);

      // Construir comando mysqldump
      $command = "mysqldump --host={$host} --port={$port} --user={$username} --password={$password} ";

      // Opciones adicionales
      $command .= "--single-transaction --routines --triggers ";

      // Tablas específicas
      if ($specificTables) {
        $tables = explode(',', $specificTables);
        $command .= $database . ' ' . implode(' ', array_map('trim', $tables));
      } else {
        $command .= $database;
      }

      // Redirección y compresión
      if ($compress) {
        $command .= " | gzip > {$fullPath}";
      } else {
        $command .= " > {$fullPath}";
      }

      $this->line("Ejecutando backup...");

      exec($command, $output, $returnCode);

      if ($returnCode === 0) {
        $size = $this->formatBytes(filesize($fullPath));
        $this->info("✓ Backup completado exitosamente");
        $this->info("Archivo: {$fullPath}");
        $this->info("Tamaño: {$size}");

        // Limpiar backups antiguos (mantener solo los últimos 10)
        $this->cleanOldBackups($backupPath);

        try {
          AuditService::logAction(
            'database_backup',
            'Command',
            0,
            [
              'file_path' => $fullPath,
              'file_size' => filesize($fullPath),
              'compressed' => $compress,
              'database' => $database,
              'tables' => $specificTables ?: 'all'
            ]
          );
        } catch (\Exception $e) {
          $this->warn("No se pudo registrar la auditoría: {$e->getMessage()}");
        }
      } else {
        $this->error("✗ Error al realizar el backup");
        $this->error("Código de error: {$returnCode}");
        return 1;
      }
    } catch (\Exception $e) {
      $this->error("✗ Error: {$e->getMessage()}");
      return 1;
    }

    return 0;
  }

  private function cleanOldBackups($backupPath)
  {
    $files = glob($backupPath . '/backup_*.sql*');

    if (count($files) > 10) {
      // Ordenar por fecha de modificación
      usort($files, function ($a, $b) {
        return filemtime($b) - filemtime($a);
      });

      // Eliminar archivos antiguos
      $toDelete = array_slice($files, 10);

      foreach ($toDelete as $file) {
        unlink($file);
        $this->line("Eliminado backup antiguo: " . basename($file));
      }
    }
  }

  private function formatBytes($size, $precision = 2)
  {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
      $size /= 1024;
    }

    return round($size, $precision) . ' ' . $units[$i];
  }
}
