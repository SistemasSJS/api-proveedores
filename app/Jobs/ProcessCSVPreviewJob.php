<?php

namespace App\Jobs;

use App\Models\ImportAudit;
use App\Models\ImportValidationCache;
use App\Services\CSVProcessorService;
use App\Services\ProductImportValidator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Exception;

class ProcessCSVPreviewJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 300; // 5 minutos para archivos grandes

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 10;

    protected $auditId;
    protected $filePath;
    protected $proveedorId;
    protected $options;
    protected $token;

    /**
     * Create a new job instance.
     *
     * @param int $auditId
     * @param string $filePath
     * @param int $proveedorId
     * @param array $options
     * @param string $token
     */
    public function __construct(int $auditId, string $filePath, int $proveedorId, array $options, string $token)
    {
        $this->auditId = $auditId;
        $this->filePath = $filePath;
        $this->proveedorId = $proveedorId;
        $this->options = $options;
        $this->token = $token;
        
        // Configurar la cola según el tamaño del archivo
        $fileSize = Storage::disk('local')->size($filePath);
        if ($fileSize > 10 * 1024 * 1024) { // > 10MB
            $this->onQueue('large-imports');
        } else {
            $this->onQueue('imports');
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $startTime = microtime(true);
        
        try {
            Log::info('Iniciando procesamiento de preview CSV', [
                'audit_id' => $this->auditId,
                'proveedor_id' => $this->proveedorId,
                'file_path' => $this->filePath,
                'token' => $this->token
            ]);

            // Obtener el audit
            $audit = ImportAudit::findOrFail($this->auditId);
            
            // Actualizar estado a procesando
            $audit->update([
                'estado' => 'processing_preview',
                'progreso' => 10
            ]);
            
            $audit->appendLog('Iniciando procesamiento de preview en background', [
                'job_id' => $this->job->getJobId() ?? 'N/A',
                'queue' => $this->job->getQueue() ?? 'default'
            ]);

            // Obtener el archivo desde storage
            if (!Storage::disk('local')->exists($this->filePath)) {
                throw new Exception("Archivo no encontrado: {$this->filePath}");
            }
            
            // Crear un UploadedFile temporal desde el archivo almacenado
            $tempPath = Storage::disk('local')->path($this->filePath);
            $originalName = basename($this->filePath);
            $mimeType = Storage::disk('local')->mimeType($this->filePath);
            
            $file = new UploadedFile(
                $tempPath,
                $originalName,
                $mimeType,
                null,
                true // test mode para evitar validaciones de upload
            );

            // Actualizar progreso
            $audit->update(['progreso' => 20]);

            // Procesar el CSV usando el servicio
            $csvProcessor = new CSVProcessorService();
            
            // Procesar directamente los datos sin generar preview limitado
            $csvData = $this->processFullCSV($file, $this->options);
            
            // Actualizar progreso
            $audit->update(['progreso' => 50]);
            
            // Validar los datos
            $validator = new ProductImportValidator($this->proveedorId);
            $validationResults = $this->validateAllData($csvData['data'], $validator);
            
            // Actualizar progreso
            $audit->update(['progreso' => 70]);
            
            // Generar métricas y análisis
            $qualityMetrics = $csvProcessor->generateQualityMetrics($validationResults, $csvData);
            $catalogBreakdown = $csvProcessor->generateCatalogBreakdown($csvData['data'], $this->proveedorId);
            $catalogosData = $csvProcessor->getArrayCatalogos($csvData['data']);
            
            // Actualizar progreso
            $audit->update(['progreso' => 90]);
            
            // Preparar datos para almacenar en tabla temporal
            $cacheData = [
                'full_data' => $csvData['data'],
                'catalogos_data' => $catalogosData,
                'headers' => $csvData['headers'] ?? [],
                'options' => $this->options,
                'proveedor_id' => $this->proveedorId,
                'file_info' => [
                    'original_name' => $originalName,
                    'size' => Storage::disk('local')->size($this->filePath),
                    'mime_type' => $mimeType
                ],
                'created_at' => now()->toISOString()
            ];
            
            // Almacenar en tabla temporal
            $this->storeInTempTable($this->token, $cacheData);
            
            // Actualizar audit con los resultados del preview
            $processingTime = round((microtime(true) - $startTime), 2);
            
            $audit->update([
                'estado' => 'preview_ready',
                'progreso' => 100,
                'preview_data' => [
                    'file_info' => [
                        'name' => $originalName,
                        'size' => Storage::disk('local')->size($this->filePath),
                        'total_rows' => count($csvData['data']),
                        'encoding' => $this->options['encoding'],
                        'delimiter' => $this->options['delimiter']
                    ],
                    'headers' => [
                        'detected' => $csvData['headers'] ?? [],
                        'validation' => $validator->validateHeaders($csvData['headers'] ?? [])
                    ],
                    'preview_data' => array_slice($csvData['data'], 0, 100), // Solo primeras 100 filas para el frontend
                    'validation_summary' => $validationResults['summary'],
                    'quality_metrics' => $qualityMetrics,
                    'catalogos' => $catalogBreakdown,
                    'preview_token' => $this->token,
                    'processing_time' => $processingTime,
                    'can_proceed' => $qualityMetrics['can_proceed'] ?? false
                ],
                'total_registros' => count($csvData['data'])
            ]);
            
            $audit->appendLog('Preview generado exitosamente', [
                'total_rows' => count($csvData['data']),
                'processing_time' => $processingTime,
                'memory_used' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB'
            ]);
            
            Log::info('Preview CSV procesado exitosamente', [
                'audit_id' => $this->auditId,
                'token' => $this->token,
                'total_rows' => count($csvData['data']),
                'processing_time' => $processingTime
            ]);
            
        } catch (Exception $e) {
            Log::error('Error procesando preview CSV', [
                'audit_id' => $this->auditId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Actualizar audit con error
            $audit = ImportAudit::find($this->auditId);
            if ($audit) {
                $audit->update([
                    'estado' => 'error',
                    'progreso' => 0,
                    'errores_detalle' => [
                        'error' => $e->getMessage(),
                        'type' => 'preview_processing_error'
                    ]
                ]);
                
                $audit->appendLog('Error procesando preview', [
                    'error' => $e->getMessage()
                ], 'error');
            }
            
            // Re-lanzar la excepción para que el job falle
            throw $e;
        }
    }

    /**
     * Procesar el CSV completo sin límite de filas
     */
    private function processFullCSV(UploadedFile $file, array $options): array
    {
        $delimiter = $options['delimiter'];
        $encoding = $options['encoding'];
        $hasHeader = $options['has_header'];

        // Leer contenido del archivo
        $content = file_get_contents($file->getRealPath());

        // Convertir la codificación si es necesario
        if ($encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }

        // Separar en líneas
        $lines = array_filter(array_map('trim', explode("\n", $content)));

        if (empty($lines)) {
            throw new Exception('El archivo CSV está vacío');
        }

        $headers = [];
        $data = [];
        $startIndex = 0;

        // Extraer encabezados si existen
        if ($hasHeader && count($lines) > 0) {
            $headers = str_getcsv($lines[0], $delimiter);
            $headers = array_map('trim', $headers);
            $startIndex = 1;
        }

        // Procesar todas las líneas de datos
        for ($i = $startIndex; $i < count($lines); $i++) {
            $row = str_getcsv($lines[$i], $delimiter);
            
            if (empty(array_filter($row))) {
                continue; // Saltar filas vacías
            }

            // Mapear a estructura esperada
            $mappedRow = $this->mapRowToStructure($row, $headers);
            if ($mappedRow) {
                $data[] = $mappedRow;
            }
        }

        return [
            'headers' => $headers,
            'data' => $data,
            'total_rows' => count($data)
        ];
    }

    /**
     * Mapear fila a estructura esperada
     */
    private function mapRowToStructure(array $row, array $headers): ?array
    {
        if (empty($headers)) {
            return null;
        }

        $mapped = [];
        $headerMap = [
            'codigo' => ['codigo', 'code', 'sku', 'codigo_interno'],
            'producto' => ['producto', 'product', 'nombre', 'name', 'descripcion_corta'],
            'descripcion' => ['descripcion', 'description', 'detalle'],
            'marca' => ['marca', 'brand', 'fabricante'],
            'categoria' => ['categoria', 'category'],
            'subcategoria' => ['subcategoria', 'subcategory', 'sub_categoria'],
            'unidad_medida' => ['unidad_medida', 'unidad', 'unit', 'uom'],
            'precio' => ['precio', 'price', 'precio_base'],
            'precio_mayoreo' => ['precio_mayoreo', 'wholesale_price'],
            'precio_menudeo' => ['precio_menudeo', 'retail_price', 'precio_publico']
        ];

        foreach ($headerMap as $field => $possibleHeaders) {
            $value = null;
            foreach ($possibleHeaders as $header) {
                $index = array_search($header, array_map('strtolower', $headers));
                if ($index !== false && isset($row[$index])) {
                    $value = trim($row[$index]);
                    break;
                }
            }
            $mapped[$field] = $value;
        }

        return $mapped;
    }

    /**
     * Validar todos los datos
     */
    private function validateAllData(array $data, ProductImportValidator $validator): array
    {
        $validationResults = [];
        $summary = [
            'total_rows' => count($data),
            'valid_rows' => 0,
            'error_rows' => 0,
            'warning_rows' => 0,
            'errors_by_type' => []
        ];

        foreach ($data as $index => $row) {
            $result = $validator->validateRow($row, $index + 1);
            
            if (empty($result['errors'])) {
                $summary['valid_rows']++;
            } else {
                $summary['error_rows']++;
                foreach ($result['errors'] as $error) {
                    $type = $error['type'] ?? 'unknown';
                    if (!isset($summary['errors_by_type'][$type])) {
                        $summary['errors_by_type'][$type] = 0;
                    }
                    $summary['errors_by_type'][$type]++;
                }
            }

            if (!empty($result['warnings'])) {
                $summary['warning_rows']++;
            }

            // Solo guardar los primeros 100 resultados con errores para no sobrecargar
            if (!empty($result['errors']) && count($validationResults) < 100) {
                $validationResults[] = [
                    'row' => $index + 1,
                    'data' => $row,
                    'errors' => $result['errors'],
                    'warnings' => $result['warnings'] ?? []
                ];
            }
        }

        return [
            'summary' => $summary,
            'validation_results' => $validationResults
        ];
    }

    /**
     * Almacenar datos en tabla temporal
     */
    private function storeInTempTable(string $token, array $data): void
    {
        $csvProcessor = new CSVProcessorService();
        
        // Usar el método privado a través de reflection (temporal)
        // En producción, hacer el método público o protected
        $reflection = new \ReflectionClass($csvProcessor);
        $method = $reflection->getMethod('storePreviewDataInTempTable');
        $method->setAccessible(true);
        $method->invoke($csvProcessor, $token, $data);
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error('Job de preview CSV falló completamente', [
            'audit_id' => $this->auditId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Actualizar audit con estado de fallo
        $audit = ImportAudit::find($this->auditId);
        if ($audit) {
            $audit->update([
                'estado' => 'failed',
                'progreso' => 0,
                'errores_detalle' => [
                    'error' => $exception->getMessage(),
                    'type' => 'job_failed'
                ]
            ]);
            
            $audit->appendLog('Job de preview falló después de múltiples intentos', [
                'error' => $exception->getMessage(),
                'attempts' => $this->attempts()
            ], 'error');
        }
    }
}
