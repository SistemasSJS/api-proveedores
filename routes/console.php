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

// Comando original
Artisan::command('inspire', function () {
    /** @var ClosureCommand $this */
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Comando para limpiar notificaciones antiguas
Artisan::command('notifications:cleanup {--days=30}', function () {
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
})->purpose('Limpiar notificaciones antiguas leídas');

// Comando para procesar requisiciones pendientes
Artisan::command('requisiciones:process-pending {--limit=50} {--status=pendiente} {--dry-run}', function () {
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

    return 0;
})->purpose('Procesa las requisiciones pendientes del sistema');

// Comando para actualizar precios de productos
Artisan::command('productos:update-prices {--proveedor-id=} {--factor=1.0} {--tipo=todos} {--dry-run}', function () {
    $proveedorId = $this->option('proveedor-id');
    $factor = (float) $this->option('factor');
    $tipo = $this->option('tipo');
    $dryRun = $this->option('dry-run');

    $this->info("Iniciando actualización de precios...");

    if ($dryRun) {
        $this->warn("MODO DRY-RUN: No se realizarán cambios en la base de datos");
    }

    $query = Producto::query();

    if ($proveedorId) {
        $query->where('proveedor_id', $proveedorId);
        $this->info("Filtrado por proveedor ID: {$proveedorId}");
    }

    $productos = $query->get();

    if ($productos->isEmpty()) {
        $this->info("No se encontraron productos para actualizar");
        return 0;
    }

    $this->info("Encontrados {$productos->count()} productos para actualizar");
    $this->info("Factor de multiplicación: {$factor}");
    $this->info("Tipo de precio: {$tipo}");

    if (!$dryRun && !$this->confirm('¿Continuar con la actualización?')) {
        $this->info('Operación cancelada');
        return 0;
    }

    $updated = 0;
    $errors = 0;

    foreach ($productos as $producto) {
        try {
            $this->line("Actualizando producto: {$producto->nombre} (SKU: {$producto->sku})");

            if (!$dryRun) {
                $updateData = [];

                switch ($tipo) {
                    case 'base':
                        if ($producto->precio_base) {
                            $updateData['precio_base'] = $producto->precio_base * $factor;
                        }
                        break;
                    case 'lista':
                        if ($producto->precio_de_lista) {
                            $updateData['precio_de_lista'] = $producto->precio_de_lista * $factor;
                        }
                        break;
                    case 'publico':
                        if ($producto->precio_publico) {
                            $updateData['precio_publico'] = $producto->precio_publico * $factor;
                        }
                        break;
                    case 'mayoreo':
                        if ($producto->precio_mayoreo) {
                            $updateData['precio_mayoreo'] = $producto->precio_mayoreo * $factor;
                        }
                        break;
                    case 'todos':
                    default:
                        if ($producto->precio_base) $updateData['precio_base'] = $producto->precio_base * $factor;
                        if ($producto->precio_de_lista) $updateData['precio_de_lista'] = $producto->precio_de_lista * $factor;
                        if ($producto->precio_publico) $updateData['precio_publico'] = $producto->precio_publico * $factor;
                        if ($producto->precio_mayoreo) $updateData['precio_mayoreo'] = $producto->precio_mayoreo * $factor;
                        if ($producto->precio_con_IVA) $updateData['precio_con_IVA'] = $producto->precio_con_IVA * $factor;
                        if ($producto->precio_sin_IVA) $updateData['precio_sin_IVA'] = $producto->precio_sin_IVA * $factor;
                        if ($producto->precio_promocional) $updateData['precio_promocional'] = $producto->precio_promocional * $factor;
                        if ($producto->precio_distribuidor) $updateData['precio_distribuidor'] = $producto->precio_distribuidor * $factor;
                        if ($producto->precio_especial) $updateData['precio_especial'] = $producto->precio_especial * $factor;
                        break;
                }

                if (!empty($updateData)) {
                    $updateData['updated_at'] = now();
                    $producto->update($updateData);
                }
            }

            $updated++;
            $this->info("✓ Producto {$producto->sku} actualizado");
        } catch (\Exception $e) {
            $errors++;
            $this->error("✗ Error actualizando producto {$producto->sku}: {$e->getMessage()}");
        }
    }

    $this->info("\n=== RESUMEN ===");
    $this->info("Actualizados: {$updated}");
    $this->error("Errores: {$errors}");

    return 0;
})->purpose('Actualiza los precios de productos masivamente');

// Comando para realizar backup de la base de datos
Artisan::command('db:backup {--compress} {--path=} {--tables=}', function () {
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
            $size = number_format(filesize($fullPath) / 1024 / 1024, 2);
            $this->info("✓ Backup completado exitosamente");
            $this->info("Archivo: {$fullPath}");
            $this->info("Tamaño: {$size} MB");

            // Limpiar backups antiguos (mantener solo los últimos 10)
            $files = glob($backupPath . '/backup_*.sql*');

            if (count($files) > 10) {
                usort($files, function ($a, $b) {
                    return filemtime($b) - filemtime($a);
                });

                $toDelete = array_slice($files, 10);

                foreach ($toDelete as $file) {
                    unlink($file);
                    $this->line("Eliminado backup antiguo: " . basename($file));
                }
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
})->purpose('Realiza un backup completo de la base de datos');

// Comando para limpiar auditorías de importación antiguas
Artisan::command('import:clean-audits {--days=30}', function () {
    $days = $this->option('days');

    $this->info("Limpiando auditorías de importación más antiguas que {$days} días...");

    $deleted = ImportAudit::where('created_at', '<', Carbon::now()->subDays($days))->delete();

    $this->info("✓ Eliminadas {$deleted} auditorías antiguas");

    return 0;
})->purpose('Limpia auditorías de importación antiguas');

// Comando para estadísticas del sistema
Artisan::command('system:stats', function () {
    $this->info("=== ESTADÍSTICAS DEL SISTEMA ===\n");

    // Estadísticas de productos
    $totalProductos = Producto::count();
    $productosActivos = Producto::where('activo', true)->count();

    $this->info("PRODUCTOS:");
    $this->line("  Total: {$totalProductos}");
    $this->line("  Activos: {$productosActivos}");
    $this->line("  Inactivos: " . ($totalProductos - $productosActivos));

    // Estadísticas de requisiciones
    $totalRequisiciones = Requisicion::count();
    $requisicionesPendientes = Requisicion::where('estado', 'pendiente')->count();
    $requisicionesProcesando = Requisicion::where('estado', 'procesando')->count();
    $requisicionesCompletadas = Requisicion::where('estado', 'completada')->count();

    $this->info("\nREQUISICIONES:");
    $this->line("  Total: {$totalRequisiciones}");
    $this->line("  Pendientes: {$requisicionesPendientes}");
    $this->line("  Procesando: {$requisicionesProcesando}");
    $this->line("  Completadas: {$requisicionesCompletadas}");

    // Estadísticas de importaciones
    $totalImportaciones = ImportAudit::count();
    $importacionesExitosas = ImportAudit::where('estado', 'completado')->count();
    $importacionesError = ImportAudit::where('estado', 'error')->count();

    $this->info("\nIMPORTACIONES:");
    $this->line("  Total: {$totalImportaciones}");
    $this->line("  Exitosas: {$importacionesExitosas}");
    $this->line("  Con errores: {$importacionesError}");

    return 0;
})->purpose('Muestra estadísticas generales del sistema');
