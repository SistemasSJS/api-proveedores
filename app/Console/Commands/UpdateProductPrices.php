<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AuditService;
use App\Models\Producto;


/**
 * Comando para actualizar precios de productos
 */
class UpdateProductPrices extends Command
{
  protected $signature = 'productos:update-prices 
                            {--proveedor-id= : ID del proveedor específico}
                            {--factor=1.0 : Factor de multiplicación para precios}
                            {--tipo=todos : Tipo de precio a actualizar (base,lista,publico,mayoreo,todos)}
                            {--dry-run : Ejecutar sin hacer cambios}';

  protected $description = 'Actualiza los precios de productos masivamente';

  public function handle()
  {
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

    try {
      AuditService::logAction(
        'update_product_prices',
        'Command',
        0,
        [
          'total_products' => $productos->count(),
          'updated' => $updated,
          'errors' => $errors,
          'factor' => $factor,
          'precio_tipo' => $tipo,
          'proveedor_id' => $proveedorId,
          'dry_run' => $dryRun
        ]
      );
    } catch (\Exception $e) {
      $this->warn("No se pudo registrar la auditoría: {$e->getMessage()}");
    }

    return 0;
  }
}
