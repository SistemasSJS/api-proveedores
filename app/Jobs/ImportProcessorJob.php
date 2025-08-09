<?php

namespace App\Jobs;

use App\Models\ImportAudit;
use App\Models\Proveedor;
use App\Services\ImportProcessorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportProcessorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $importAuditId;
    protected array $chunk;
    protected int $chunkIndex;
    protected int $totalChunks;
    protected bool $usePreview;

    public $timeout = 300; // 5 minutes
    public $tries = 3;

    public function __construct(int $importAuditId, array $chunk, int $chunkIndex, int $totalChunks, bool $usePreview = false)
    {
        $this->importAuditId = $importAuditId;
        $this->chunk = $chunk;
        $this->chunkIndex = $chunkIndex;
        $this->totalChunks = $totalChunks;
        $this->usePreview = $usePreview;
    }

    public function handle(ImportProcessorService $importProcessorService): void
    {
        $startTime = microtime(true);
        
        $importAudit = ImportAudit::find($this->importAuditId);
        if (!$importAudit) {
            Log::error('ImportAudit not found', ['import_audit_id' => $this->importAuditId]);
            return;
        }

        $proveedor = $importAudit->proveedor;
        if (!$proveedor) {
            Log::error('Proveedor not found for ImportAudit', ['import_audit_id' => $this->importAuditId]);
            return;
        }

        try {
            $importAudit->appendLog("Iniciando procesamiento asíncrono del chunk {$this->chunkIndex} de {$this->totalChunks}", [
                'chunk_size' => count($this->chunk),
                'job_id' => $this->job->getJobId(),
                'use_preview' => $this->usePreview
            ]);

            if ($this->usePreview) {
                $result = $this->processChunkForPreview($importAudit, $proveedor, $importProcessorService);
            } else {
                $result = $this->processChunkForExecution($importAudit, $proveedor, $importProcessorService);
            }

            $processingTime = round(microtime(true) - $startTime, 2);
            
            $importAudit->appendLog("Chunk {$this->chunkIndex} completado exitosamente", [
                'processing_time' => $processingTime . 's',
                'productos_creados' => $result['productos_creados'] ?? 0,
                'productos_actualizados' => $result['productos_actualizados'] ?? 0,
                'errores' => count($result['errores'] ?? [])
            ]);

            // Actualizar progreso global
            $this->updateGlobalProgress($importAudit, $result);

        } catch (\Throwable $e) {
            $this->handleJobError($importAudit, $e);
            throw $e;
        }
    }

    /**
     * Process chunk for preview phase
     */
    private function processChunkForPreview(ImportAudit $importAudit, Proveedor $proveedor, ImportProcessorService $importProcessorService): array
    {
        // En modo preview, solo validamos y preparamos datos sin insertar
        $result = [
            'productos_validados' => 0,
            'errores' => [],
            'error_types' => []
        ];

        foreach ($this->chunk as $item) {
            try {
                // Validar estructura del item
                $this->validateProductItem($item);
                $result['productos_validados']++;
            } catch (\Throwable $e) {
                $errorType = get_class($e);
                $result['errores'][] = [
                    'item' => $item,
                    'error' => $e->getMessage(),
                    'error_type' => $errorType,
                    'chunk' => $this->chunkIndex
                ];
                
                if (!in_array($errorType, $result['error_types'])) {
                    $result['error_types'][] = $errorType;
                }
            }
        }

        return $result;
    }

    /**
     * Process chunk for execution phase
     */
    private function processChunkForExecution(ImportAudit $importAudit, Proveedor $proveedor, ImportProcessorService $importProcessorService): array
    {
        // Usar método privado del servicio mediante reflection para reutilizar lógica
        $reflectionClass = new \ReflectionClass($importProcessorService);
        $method = $reflectionClass->getMethod('processChunkWithTransaction');
        $method->setAccessible(true);

        return $method->invoke($importProcessorService, $this->chunk, $proveedor, $importAudit, $this->chunkIndex + 1, $this->totalChunks);
    }

    /**
     * Validate product item structure
     */
    private function validateProductItem(array $item): void
    {
        $requiredFields = ['codigo', 'producto'];
        
        foreach ($requiredFields as $field) {
            if (!isset($item[$field]) || empty($item[$field])) {
                throw new \InvalidArgumentException("Campo requerido '{$field}' faltante o vacío");
            }
        }

        // Validar precio si está presente
        if (isset($item['precio']) && $item['precio'] !== null && $item['precio'] !== '') {
            if (!is_numeric($item['precio']) || $item['precio'] < 0) {
                throw new \InvalidArgumentException("El precio debe ser un número válido mayor o igual a 0");
            }
        }

        // Validar precios adicionales
        $precioFields = ['precio_mayoreo', 'precio_menuedeo'];
        foreach ($precioFields as $field) {
            if (isset($item[$field]) && $item[$field] !== null && $item[$field] !== '') {
                if (!is_numeric($item[$field]) || $item[$field] < 0) {
                    throw new \InvalidArgumentException("El {$field} debe ser un número válido mayor o igual a 0");
                }
            }
        }
    }

    /**
     * Update global progress based on completed chunks
     */
    private function updateGlobalProgress(ImportAudit $importAudit, array $result): void
    {
        $importAudit->refresh();
        
        // Calcular progreso basado en chunks completados
        $currentProgress = $importAudit->progreso ?? 0;
        $chunkProgress = (1 / $this->totalChunks) * 100;
        $newProgress = min(100, $currentProgress + $chunkProgress);
        
        // Actualizar contadores si estamos en modo ejecución
        if (!$this->usePreview && isset($result['productos_creados'], $result['productos_actualizados'])) {
            $importAudit->increment('nuevos', $result['productos_creados']);
            $importAudit->increment('actualizados', $result['productos_actualizados']);
            $importAudit->increment('errores', count($result['errores'] ?? []));
            
            // Agregar tipos de error únicos
            $currentErrorTypes = $importAudit->error_types ?? [];
            $newErrorTypes = array_unique(array_merge($currentErrorTypes, $result['error_types'] ?? []));
            
            $importAudit->update([
                'progreso' => $newProgress,
                'error_types' => $newErrorTypes
            ]);
        } else {
            $importAudit->update(['progreso' => $newProgress]);
        }

        // Si es el último chunk, marcar como completado
        if ($this->chunkIndex + 1 >= $this->totalChunks) {
            $importAudit->update([
                'estado' => 'completed',
                'fase' => 'completed',
                'progreso' => 100,
                'fin_proceso' => now()
            ]);
            
            $importAudit->appendLog('Todos los chunks procesados. Importación completada.', [
                'total_chunks' => $this->totalChunks,
                'final_stats' => [
                    'nuevos' => $importAudit->nuevos,
                    'actualizados' => $importAudit->actualizados,
                    'errores' => $importAudit->errores
                ]
            ]);
        }
    }

    /**
     * Handle job error
     */
    private function handleJobError(ImportAudit $importAudit, \Throwable $e): void
    {
        $errorType = get_class($e);
        
        $importAudit->appendLog("Error en procesamiento del chunk {$this->chunkIndex}", [
            'error' => $e->getMessage(),
            'error_type' => $errorType,
            'chunk_size' => count($this->chunk),
            'job_id' => $this->job->getJobId()
        ]);

        // Marcar items del chunk como errores
        $chunkErrors = [];
        foreach ($this->chunk as $item) {
            $chunkErrors[] = [
                'item' => $item,
                'error' => 'Error en procesamiento del chunk: ' . $e->getMessage(),
                'error_type' => $errorType,
                'chunk' => $this->chunkIndex
            ];
        }

        // Actualizar errores en el audit
        $currentErrors = $importAudit->errores_detalle ?? [];
        $importAudit->update([
            'errores_detalle' => array_merge($currentErrors, $chunkErrors),
            'errores' => count($currentErrors) + count($chunkErrors)
        ]);

        Log::error('Error en ImportProcessorJob', [
            'import_audit_id' => $this->importAuditId,
            'chunk_index' => $this->chunkIndex,
            'error' => $e->getMessage(),
            'error_type' => $errorType,
            'chunk_size' => count($this->chunk)
        ]);
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        $importAudit = ImportAudit::find($this->importAuditId);
        
        if ($importAudit) {
            $importAudit->update([
                'estado' => 'failed',
                'fase' => 'completed',
                'fin_proceso' => now()
            ]);
            
            $importAudit->appendLog("Job falló después de {$this->tries} intentos", [
                'chunk_index' => $this->chunkIndex,
                'error' => $exception->getMessage(),
                'error_type' => get_class($exception)
            ]);
        }

        Log::error('ImportProcessorJob falló completamente', [
            'import_audit_id' => $this->importAuditId,
            'chunk_index' => $this->chunkIndex,
            'exception' => $exception->getMessage()
        ]);
    }
}
