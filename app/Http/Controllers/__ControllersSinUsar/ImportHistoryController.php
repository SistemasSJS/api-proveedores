<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportHistory\ImportHistoryIndexRequest;
use App\Http\Requests\ImportHistory\ImportHistoryStoreRequest;
use App\Http\Requests\ImportHistory\ImportPreviewRequest;
use App\Http\Requests\ImportHistory\ImportConfirmRequest;
use App\Http\Resources\ImportHistoryResource;
use App\Http\Resources\ImportHistoryCollection;
use App\Models\ImportAudit;
use App\Models\Proveedor;
use App\Services\ImportService;
use App\Services\CSVProcessorService;
use App\Services\ProductImportValidator;
use App\Jobs\ImportProcessorJob;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ImportHistoryController extends Controller
{
    use ApiResponse;

    protected ImportService $importService;

    public function __construct(ImportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * Listar historial de importaciones
     */
    public function index(ImportHistoryIndexRequest $request, Proveedor $proveedor)
    {
        $filters = $request->getFilters();
        $paginator = ImportAudit::query()
            ->where('proveedor_id', $proveedor->id)
            ->filter($filters)
            ->orderBy($request->input('sort_by', 'created_at'), $request->input('order', 'desc'))
            ->paginate($request->input('per_page', 15));

        return new ImportHistoryCollection($paginator);
    }

    /**
     * Mostrar detalle de una importación específica
     */
    public function show(Request $request, Proveedor $proveedor, ImportAudit $importHistory)
    {
        // Verificar que la importación pertenezca al proveedor
        if ($importHistory->proveedor_id !== $proveedor->id) {
            return $this->error('La importación no pertenece a este proveedor.', 403);
        }

        return $this->success(new ImportHistoryResource($importHistory));
    }

    /**
     * Crear nueva entrada de importación
     */
    public function store(ImportHistoryStoreRequest $request, Proveedor $proveedor)
    {
        $data = $request->validated();
        $data['proveedor_id'] = $proveedor->id;

        $importAudit = ImportAudit::create($data);

        return $this->success(
            new ImportHistoryResource($importAudit),
            'Registro de importación creado correctamente.'
        );
    }

    /**
     * Actualizar una entrada de importación
     */
    public function update(ImportHistoryStoreRequest $request, Proveedor $proveedor, ImportAudit $importHistory)
    {
        // Verificar que la importación pertenezca al proveedor
        if ($importHistory->proveedor_id !== $proveedor->id) {
            return $this->error('La importación no pertenece a este proveedor.', 403);
        }

        $importHistory->update($request->validated());

        return $this->success(
            new ImportHistoryResource($importHistory->fresh()),
            'Registro de importación actualizado correctamente.'
        );
    }

    /**
     * Eliminar una entrada de importación
     */
    public function destroy(Request $request, Proveedor $proveedor, ImportAudit $importHistory)
    {
        // Verificar que la importación pertenezca al proveedor
        if ($importHistory->proveedor_id !== $proveedor->id) {
            return $this->error('La importación no pertenece a este proveedor.', 403);
        }

        $importHistory->delete();

        return $this->success(null, 'Registro de importación eliminado correctamente.');
    }

    /**
     * Ejecutar importación de productos
     */
    public function import(Request $request, Proveedor $proveedor)
    {
        // Delegar al servicio de importación
        return $this->importService->processImport($request, $proveedor);
    }

    /**
     * Procesar CSV y retornar preview con validaciones
     * POST /productos/import-preview
     */
    public function importPreview(ImportPreviewRequest $request, Proveedor $proveedor)
    {
        try {
            $options = $request->getValidatedWithDefaults();
            $csvFile = $request->file('csv_file');

            // Initialize processor
            $processor = new CSVProcessorService();

            // Process CSV and get preview
            $result = $processor->processCSVPreview($csvFile, $proveedor->id, $options);

            if (!$result['success']) {
                return $this->error(
                    $result['error'],
                    422,
                    ['error_type' => $result['error_type'] ?? 'processing_error']
                );
            }

            // Log preview generation
            Log::info('CSV import preview generated', [
                'proveedor_id' => $proveedor->id,
                'preview_token' => $result['preview_token'],
                'file_name' => $result['file_info']['name'],
                'total_rows' => $result['file_info']['total_rows'],
                'quality_score' => $result['quality_metrics']['quality_score']
            ]);

            return $this->success($result, 'Preview de importación generado correctamente.');
        } catch (\Throwable $e) {
            Log::error('Error generating import preview', [
                'proveedor_id' => $proveedor->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->error(
                'Error interno del servidor al generar preview.',
                500,
                ['error_detail' => config('app.debug') ? $e->getMessage() : null]
            );
        }
    }

    /**
     * Confirmar importación por bloques
     * POST /productos/import-confirm
     */
    public function importConfirm(ImportConfirmRequest $request, Proveedor $proveedor)
    {
        try {
            $config = $request->getImportConfiguration();
            $previewToken = $request->input('preview_token');

            // Initialize processor
            $processor = new CSVProcessorService();

            // Retrieve cached preview data
            $previewData = $processor->getCachedPreviewData($previewToken);

            if (!$previewData) {
                return $this->error(
                    'Token de previsualización inválido o expirado.',
                    ['error_type' => 'token_expired'],
                    404,
                );
            }

            // Verify proveedor matches
            if ($previewData['proveedor_id'] !== $proveedor->id) {
                return $this->error(
                    'Token de previsualización no válido para este proveedor.',
                    ['error_type' => 'unauthorized_token'],
                    403,
                );
            }

            // Create import audit entry
            $importAudit = $this->createImportAuditForConfirmation(
                $proveedor,
                $previewData,
                $config
            );

            // Process import based on configuration
            if ($config['process_async']) {
                // Queue the job for async processing
                ImportProcessorJob::dispatch(
                    $importAudit->id,
                    $previewData['full_data'],
                    $config
                )->onQueue('imports');

                $message = 'Importación iniciada. Se procesará en segundo plano.';
                $responseData = [
                    'import_id' => $importAudit->id,
                    'status' => 'queued',
                    'processing_mode' => 'async',
                    'estimated_time' => $this->estimateProcessingTime(count($previewData['full_data'])),
                    'can_track' => true
                ];
            } else {
                // Process synchronously for small datasets
                $result = $this->importService->processImportWithAudit(
                    $importAudit,
                    $previewData['full_data'],
                    $config
                );

                $message = 'Importación completada exitosamente.';
                $responseData = array_merge($result, [
                    'import_id' => $importAudit->id,
                    'status' => 'completed',
                    'processing_mode' => 'sync'
                ]);
            }

            // Log import confirmation
            Log::info('Import confirmed and processing started', [
                'proveedor_id' => $proveedor->id,
                'import_audit_id' => $importAudit->id,
                'preview_token' => $previewToken,
                'processing_mode' => $config['process_async'] ? 'async' : 'sync',
                'total_rows' => count($previewData['full_data']),
                'chunk_size' => $config['chunk_size']
            ]);

            return $this->success($responseData, $message);
        } catch (\Throwable $e) {
            Log::error('Error confirming import', [
                'proveedor_id' => $proveedor->id,
                'preview_token' => $request->input('preview_token'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->error(
                'Error interno del servidor al procesar la importación.',
                ['error_detail' => config('app.debug') ? $e->getMessage() : null],
                500,
            );
        }
    }

    /**
     * Obtener detalle de importación específica con información extendida
     * GET /import-history/{id}
     */
    public function showDetailed(Request $request, Proveedor $proveedor, ImportAudit $importHistory)
    {
        // Verificar que la importación pertenezca al proveedor
        if ($importHistory->proveedor_id !== $proveedor->id) {
            return $this->error('La importación no pertenece a este proveedor.', 403);
        }

        // Build detailed response with additional metrics
        $resource = new ImportHistoryResource($importHistory);
        $detailedData = $resource->toArray($request);

        // Add extended information
        $detailedData['extended_info'] = [
            'error_statistics' => $importHistory->getErrorStatistics(),
            'performance_metrics' => $importHistory->getPerformanceMetrics(),
            'import_summary' => $importHistory->getImportSummary(),
            'structured_logs' => $importHistory->getStructuredLogs(null, 50),
            'has_critical_errors' => $importHistory->hasCriticalErrors(),
            'can_rollback' => $this->canRollbackImport($importHistory)
        ];

        // Add rollback information if available
        if ($importHistory->supports_rollback && $importHistory->rollback_data) {
            $detailedData['rollback_info'] = [
                'available' => true,
                'expires_at' => $importHistory->rollback_expires_at,
                'affected_records' => count($importHistory->rollback_data['created_ids'] ?? []) +
                    count($importHistory->rollback_data['updated_ids'] ?? []),
                'can_execute' => $importHistory->rollback_expires_at > now()
            ];
        }

        return $this->success($detailedData, 'Detalle de importación obtenido correctamente.');
    }

    /**
     * Create import audit entry for confirmation
     */
    private function createImportAuditForConfirmation(
        Proveedor $proveedor,
        array $previewData,
        array $config
    ): ImportAudit {
        return ImportAudit::create([
            'proveedor_id' => $proveedor->id,
            'tipo' => 'productos',
            'archivo' => $previewData['file_info']['original_name'],
            'formato' => 'csv',
            'import_source' => 'csv_upload',
            'estado' => 'pendiente',
            'fase' => 'queued',
            'total_registros' => count($previewData['full_data']),
            'progreso' => 0,
            'import_configuration' => $config,
            'supports_rollback' => true,
            'inicio_proceso' => now(),
            'queued_at' => now()
        ]);
    }

    /**
     * Estimate processing time for import
     */
    private function estimateProcessingTime(int $totalRows): string
    {
        $seconds = ceil($totalRows / 100); // Aproximadamente 100 registros por segundo

        if ($seconds < 60) {
            return "{$seconds} segundos";
        } elseif ($seconds < 3600) {
            $minutes = ceil($seconds / 60);
            return "{$minutes} minutos";
        } else {
            $hours = floor($seconds / 3600);
            $remainingMinutes = ceil(($seconds % 3600) / 60);
            return "{$hours}h {$remainingMinutes}m";
        }
    }

    /**
     * Check if import can be rolled back
     */
    private function canRollbackImport(ImportAudit $importAudit): bool
    {
        return $importAudit->supports_rollback &&
            $importAudit->rollback_expires_at &&
            $importAudit->rollback_expires_at > now() &&
            !empty($importAudit->rollback_data);
    }
}
