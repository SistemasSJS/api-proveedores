<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Requisicion;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;


/**
 * Comando para procesar requisiciones pendientes
 */
class ProcessPendingRequisiciones extends Command
{
  protected $signature = 'requisiciones:process-pending 
                            {--limit=50 : Número máximo de requisiciones a procesar}
                            {--status=pendiente : Estado de requisiciones a procesar}
                            {--dry-run : Ejecutar sin hacer cambios}';

  protected $description = 'Procesa las requisiciones pendientes del sistema';

  public function handle()
  {
    $limit = $this->option('limit');
    $status = $this->option('status');
    $dryRun = $this->option('dry-run');

    $this->info("Iniciando procesamiento de requisiciones...");

    if ($dryRun) {
      $this->warn("MODO DRY-RUN: No se realizarán cambios en la base de datos");
    }

    $requisiciones = Requisicion::where('estado', $status)
      ->with(['items.producto', 'proveedor'])
      ->limit($limit)
      ->get();

    if ($requisiciones->isEmpty()) {
      $this->info("No se encontraron requisiciones con estado: {$status}");
      return 0;
    }

    $this->info("Encontradas {$requisiciones->count()} requisiciones a procesar");

    $processed = 0;
    $errors = 0;

    foreach ($requisiciones as $requisicion) {
      try {
        $this->line("Procesando requisición ID: {$requisicion->id}");

        if (!$dryRun) {
          DB::transaction(function () use ($requisicion) {
            // Validar inventario disponible
            foreach ($requisicion->items as $item) {
              if ($item->producto->stock < $item->cantidad) {
                throw new \Exception("Stock insuficiente para producto {$item->producto->nombre}");
              }
            }

            // Actualizar estado de la requisición
            $requisicion->update([
              'estado' => 'procesando',
              'fecha_proceso' => now(),
              'procesado_por' => 'system'
            ]);

            // Reservar productos
            foreach ($requisicion->items as $item) {
              $item->producto->decrement('stock', $item->cantidad);
              $item->update(['estado' => 'reservado']);
            }
          });
        }

        $processed++;
        $this->info("✓ Requisición {$requisicion->id} procesada");
      } catch (\Exception $e) {
        $errors++;
        $this->error("✗ Error procesando requisición {$requisicion->id}: {$e->getMessage()}");
      }
    }

    $this->info("\n=== RESUMEN ===");
    $this->info("Procesadas: {$processed}");
    $this->error("Errores: {$errors}");

    try {
      AuditService::logAction(
        'process_pending_requisiciones',
        'Command',
        0,
        [
          'total_found' => $requisiciones->count(),
          'processed' => $processed,
          'errors' => $errors,
          'status_filter' => $status,
          'limit' => $limit,
          'dry_run' => $dryRun
        ]
      );
    } catch (\Exception $e) {
      $this->warn("No se pudo registrar la auditoría: {$e->getMessage()}");
    }

    return 0;
  }
}
