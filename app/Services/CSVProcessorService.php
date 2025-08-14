<?php

namespace App\Services;

use App\Models\ImportValidationCache;
use App\Models\Marca;
use App\Models\Categoria;
use App\Models\UnidadMedida;
use App\Models\Producto;
use App\Services\ProductImportValidator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

/**
 * Servicio para procesar archivos CSV y generar una vista previa
 *
 * Este servicio se encarga de:
 * - Leer y parsear el archivo CSV.
 * - Validar encabezados y filas (usando ProductImportValidator).
 * - Calcular métricas de calidad, desglose de catálogo y sugerencias.
 * - Cachear datos para confirmación.
 * - Retornar una estructura de datos que el controlador puede presentar al usuario.
 *
 * El controlador que lo consume debe:
 * - Invocar `processCSVPreview(...)`.
 * - Procesar el arreglo resultante (éxito, datos para vista previa, métricas, token, etc.).
 * - Manejar errores según el valor de retorno (`success => false`) o excepciones lanzadas.
 */
class CSVProcessorService
{

    /**
     * Procesa un archivo CSV generando una vista previa y validaciones.
     *
     * @param UploadedFile $csvFile Archivo CSV subido.
     * @param int $proveedorId ID del proveedor para contexto.
     * @param array $options Opciones de parseo y validación (delimiter, encoding, etc.).
     *
     * @return array Estructura con:
     *  - success: bool
     *  - preview_token: string|null
     *  - file_info: array
     *  - headers: array ('detected', 'validation', 'mapping_suggestions')
     *  - preview_data: array (primeras filas)
     *  - validation_summary: array
     *  - validation_details: array
     *  - quality_metrics: array (incluye can_proceed, quality_score, recommendation)
     *  - processing_info: array (tiempo, memoria, can_proceed)
     *  - catalogos: array (desglose de productos, marcas, categorías, etc.)
     *  - error y error_type en caso de fallo
     *
     * @throws \Throwable En caso de error inesperado durante el procesamiento.
     */
    public function processCSVPreview(
        UploadedFile $csvFile,
        int $proveedorId,
        array $options = []
    ): array {
        $startTime = microtime(true);
        $token = $this->generatePreviewToken();

        // Opciones por defecto
        $options = array_merge([
            'delimiter' => ',',
            'encoding' => 'UTF-8',
            'has_header' => true,
            'preview_rows' => -1,
            'strict_validation' => false,
            'auto_create_relations' => true
        ], $options);

        try {
            // Crear validador para este proveedor
            $validator = new ProductImportValidator($proveedorId);

            // Parsear archivo CSV
            $csvData = $this->parseCSV($csvFile, $options);

            // Validar encabezados si existen
            $headerValidation = [];
            if ($options['has_header'] && !empty($csvData['headers'])) {
                $headerValidation = $validator->validateHeaders($csvData['headers']);
            }

            // Obtener datos de vista previa (filas limitadas)
            // si es -1, la vista previa no limita la cantidad de registros
            if ($options['preview_rows'] >= 0) {
                $previewData = array_slice($csvData['data'], 0, $options['preview_rows']);
            } else {
                $previewData = $csvData;
            }

            // Validar datos de la vista previa
            $validationResults = $this->validateBatch($previewData, $validator);

            // Generar métricas de calidad
            $qualityMetrics = $this->generateQualityMetrics($validationResults, $csvData);

            // Generar análisis del desglose de catálogo
            $catalogBreakdown = $this->generateCatalogBreakdown($csvData['data'], $proveedorId);

            // Cachear los datos completos para confirmación posterior
            $cacheData = [
                'full_data' => $csvData['data'],
                'headers' => $csvData['headers'] ?? [],
                'options' => $options,
                'proveedor_id' => $proveedorId,
                'file_info' => [
                    'original_name' => $csvFile->getClientOriginalName(),
                    'size' => $csvFile->getSize(),
                    'mime_type' => $csvFile->getMimeType()
                ],
                'created_at' => now()->toISOString()
            ];

            $this->cachePreviewData($token, $cacheData);

            $processingTime = round((microtime(true) - $startTime), 2);

            return [
                'success' => true,
                'preview_token' => $token,
                'file_info' => [
                    'name' => $csvFile->getClientOriginalName(),
                    'size' => $csvFile->getSize(),
                    'total_rows' => count($csvData['data']),
                    'preview_rows' => count($previewData),
                    'encoding' => $options['encoding'],
                    'delimiter' => $options['delimiter']
                ],
                'headers' => [
                    'detected' => $csvData['headers'] ?? [],
                    'validation' => $headerValidation,
                    'mapping_suggestions' => $this->generateHeaderMappingSuggestions($csvData['headers'] ?? [], $validator)
                ],
                'preview_data' => $previewData, // Solo primeras 10 para el frontend
                'validation_summary' => $validationResults['summary'],
                'validation_details' => $validationResults['validation_results'], // Primeros 20 errores
                'quality_metrics' => $qualityMetrics,
                'processing_info' => [
                    'processing_time' => $processingTime,
                    'memory_used' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB',
                    'can_proceed' => $qualityMetrics['can_proceed'] ?? false
                ],
                'catalogos' => $catalogBreakdown
            ];
        } catch (\Throwable $e) {
            Log::error('Error procesando la vista previa del CSV', [
                'error' => $e->getMessage(),
                'file' => $csvFile->getClientOriginalName(),
                'proveedor_id' => $proveedorId,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Error procesando el archivo CSV: ' . $e->getMessage(),
                'error_type' => 'processing_error'
            ];
        }
    }

    /**
     * Parsea un CSV según las opciones dadas.
     *
     * @param UploadedFile $file
     * @param array $options
     * @return array ['headers' => array, 'data' => array, 'total_rows' => int]
     * @throws \Exception Si el archivo está vacío.
     */
    private function parseCSV(UploadedFile $file, array $options): array
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
            throw new \Exception('El archivo CSV está vacío');
        }

        $data = [];
        $headers = [];

        foreach ($lines as $index => $line) {
            // Parsear línea del CSV
            $row = str_getcsv($line, $delimiter);

            if ($index === 0 && $hasHeader) {
                $headers = array_map('trim', $row);
                continue;
            }

            // Convertir la fila a array asociativo si hay encabezados
            if (!empty($headers)) {
                $associativeRow = [];
                foreach ($headers as $headerIndex => $header) {
                    $associativeRow[$header] = isset($row[$headerIndex]) ? trim($row[$headerIndex]) : '';
                }
                $data[] = $associativeRow;
            } else {
                $data[] = array_map('trim', $row);
            }
        }

        return [
            'headers' => $headers,
            'data' => $data,
            'total_rows' => count($data)
        ];
    }

    /**
     * Genera métricas de calidad para los datos importados
     */
    private function generateQualityMetrics(array $validationResults, array $csvData): array
    {
        $summary = $validationResults['summary'];
        $totalRows = $summary['total_records'];

        if ($totalRows === 0) {
            return ['can_proceed' => false, 'quality_score' => 0];
        }

        $successRate = ($summary['correct_records'] / $totalRows) * 100;
        $warningRate = ($summary['warning_records'] / $totalRows) * 100;
        $errorRate = ($summary['error_records'] / $totalRows) * 100;

        $qualityScore = 0;
        if ($successRate >= 90) $qualityScore += 50;
        elseif ($successRate >= 70) $qualityScore += 30;
        elseif ($successRate >= 50) $qualityScore += 15;

        if ($warningRate <= 20) $qualityScore += 25;
        elseif ($warningRate <= 40) $qualityScore += 15;

        if ($errorRate <= 10) $qualityScore += 25;
        elseif ($errorRate <= 30) $qualityScore += 10;

        return [
            'quality_score' => round($qualityScore, 1),
            'success_rate' => round($successRate, 2),
            'warning_rate' => round($warningRate, 2),
            'error_rate' => round($errorRate, 2),
            'can_proceed' => $errorRate <= 50, // Puede proceder si menos del 50% tiene errores
            'recommendation' => $this->getQualityRecommendation($qualityScore, $errorRate),
            'estimated_processing_time' => $this->estimateProcessingTime($totalRows),
            'error_distribution' => $summary['errors_by_type'] ?? []
        ];
    }

    /**
     * Valida un lote de filas para generar un resumen y detalles.
     *
     * @param array $previewData
     * @param ProductImportValidator $validator
     * @return array ['summary' => array, 'validation_results' => array]
     */
    private function validateBatch(array $previewData, ProductImportValidator $validator): array
    {
        $validationResults = [];
        $summary = [
            'total_records' => count($previewData),
            'correct_records' => 0,
            'warning_records' => 0,
            'error_records' => 0,
            'errors_by_type' => []
        ];

        foreach ($previewData as $index => $row) {
            $rowValidation = $validator->validateRow($row, $index + 1);

            $status = 'correcto';
            if (!empty($rowValidation['errors'])) {
                $status = 'error';
                $summary['error_records']++;
            } elseif (!empty($rowValidation['warnings'])) {
                $status = 'advertencia';
                $summary['warning_records']++;
            } else {
                $summary['correct_records']++;
            }

            $validationResults[] = [
                'row_index' => $index + 1,
                'status' => $status,
                'errors' => $rowValidation['errors'],
                'warnings' => $rowValidation['warnings'],
                'data' => $row
            ];
        }

        return [
            'summary' => $summary,
            'validation_results' => $validationResults
        ];
    }

    /**
     * Genera sugerencias de mapeo de encabezados
     */
    private function generateHeaderMappingSuggestions(array $detectedHeaders, ProductImportValidator $validator): array
    {
        $expectedHeaders = $validator->getExpectedHeaders();
        $suggestions = [];

        foreach ($detectedHeaders as $detectedHeader) {
            $bestMatch = $this->findBestHeaderMatch($detectedHeader, array_keys($expectedHeaders));
            if ($bestMatch) {
                $suggestions[] = [
                    'detected' => $detectedHeader,
                    'suggested' => $bestMatch,
                    'confidence' => $this->calculateHeaderSimilarity($detectedHeader, $bestMatch),
                    'required' => $expectedHeaders[$bestMatch] === 'required'
                ];
            }
        }

        return $suggestions;
    }

    /**
     * Encuentra la mejor coincidencia para un encabezado usando similitud
     */
    private function findBestHeaderMatch(string $detectedHeader, array $expectedHeaders): ?string
    {
        $bestMatch = null;
        $highestSimilarity = 0;

        $normalizedDetected = strtolower(trim($detectedHeader));

        foreach ($expectedHeaders as $expectedHeader) {
            $normalizedExpected = strtolower($expectedHeader);

            // Coincidencia exacta
            if ($normalizedDetected === $normalizedExpected) {
                return $expectedHeader;
            }

            // Coincidencia parcial
            similar_text($normalizedDetected, $normalizedExpected, $similarity);

            if ($similarity > $highestSimilarity && $similarity > 60) {
                $highestSimilarity = $similarity;
                $bestMatch = $expectedHeader;
            }
        }

        return $bestMatch;
    }

    /**
     * Calcula el porcentaje de similitud entre dos encabezados
     */
    private function calculateHeaderSimilarity(string $header1, string $header2): float
    {
        similar_text(strtolower($header1), strtolower($header2), $percent);
        return round($percent, 1);
    }

    /**
     * Obtiene la recomendación de calidad según la puntuación y el porcentaje de error
     */
    private function getQualityRecommendation(float $score, float $errorRate): string
    {
        if ($score >= 80) {
            return 'Excelente calidad de datos. Recomendado proceder con la importación.';
        } elseif ($score >= 60) {
            return 'Buena calidad de datos. Revisar advertencias antes de proceder.';
        } elseif ($score >= 40) {
            return 'Calidad moderada. Revisar y corregir errores antes de importar.';
        } else {
            return 'Baja calidad de datos. Se recomienda revisar y corregir el archivo antes de importar.';
        }
    }

    /**
     * Estima el tiempo de procesamiento basado en la cantidad de filas
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
     * Guarda en caché los datos de vista previa para confirmación posterior
     */
    private function cachePreviewData(string $token, array $data): void
    {
        // Guardar en Redis/base de datos por 1 hora
        Cache::put("csv_preview:{$token}", $data, 3600);

        // También guardar en ImportValidationCache para persistencia
        ImportValidationCache::create([
            'token' => $token,
            'proveedor_id' => $data['proveedor_id'],
            'file_name' => $data['file_info']['original_name'],
            'total_rows' => count($data['full_data']),
            'validation_data' => $data,
            'expires_at' => now()->addHour()
        ]);
    }

    /**
     * Recupera los datos de vista previa en caché
     */
    public function getCachedPreviewData(string $token): ?array
    {
        // Intentar primero desde la caché
        $data = Cache::get("csv_preview:{$token}");

        if (!$data) {
            // Intentar desde la base de datos
            $cached = ImportValidationCache::where('token', $token)
                ->where('expires_at', '>', now())
                ->first();

            if ($cached) {
                $data = $cached->validation_data;
                // Volver a cachear para acceso rápido
                Cache::put("csv_preview:{$token}", $data, 3600);
            }
        }

        return $data;
    }

    /**
     * Genera un token único para la vista previa
     */
    private function generatePreviewToken(): string
    {
        return hash('sha256', Str::random(32) . microtime(true));
    }

    /**
     * Elimina datos de vista previa expirados
     */
    public function cleanupExpiredPreviews(): int
    {
        return ImportValidationCache::where('expires_at', '<', now())->delete();
    }

    /**
     * Genera un desglose detallado del catálogo con análisis de existentes vs nuevos
     *
     * @param array $csvData Datos de las filas del CSV
     * @param int $proveedorId ID del proveedor para las consultas
     * @return array Desglose detallado con análisis de catálogo
     */
    public function generateCatalogBreakdown(array $csvData, int $proveedorId): array
    {
        // Extraer valores únicos del CSV
        $csvMarcas = $this->extractUniqueValues($csvData, 'marca');
        $csvCategorias = $this->extractUniqueValues($csvData, 'categoria');
        $csvSubcategorias = $this->extractUniqueValues($csvData, 'subcategoria');
        $csvUnidades = $this->extractUniqueValues($csvData, 'unidad_medida');
        $csvProductos = $this->extractUniqueValues($csvData, 'codigo');

        // Obtener catálogos existentes desde la base de datos
        $existingMarcas = $this->getExistingMarcas($proveedorId);
        $existingCategorias = $this->getExistingCategorias($proveedorId);
        $existingSubcategorias = $this->getExistingSubcategorias($proveedorId);
        $existingUnidades = $this->getExistingUnidades($proveedorId);
        $existingProductos = $this->getExistingProductos($proveedorId);

        return [
            'productos' => $this->analyzeProductos($csvProductos, $existingProductos, $csvData),
            'marcas' => $this->analyzeMarcas($csvMarcas, $existingMarcas),
            'categorias' => $this->analyzeCategorias($csvCategorias, $existingCategorias),
            'subcategorias' => $this->analyzeSubcategorias($csvSubcategorias, $existingSubcategorias),
            'unidades' => $this->analyzeUnidades($csvUnidades, $existingUnidades),
        ];
    }

    /**
     * Extrae valores únicos de un campo específico en los datos del CSV
     */
    private function extractUniqueValues(array $csvData, string $field): array
    {
        $values = collect($csvData)
            ->pluck($field)
            ->filter(function ($value) {
                return !empty($value) && trim($value) !== '';
            })
            ->map(function ($value) {
                return trim($value);
            })
            ->unique()
            ->values()
            ->toArray();

        return $values;
    }

    /**
     * Obtiene las marcas existentes para el proveedor
     */
    private function getExistingMarcas(int $proveedorId): Collection
    {
        return Marca::where('proveedor_id', $proveedorId)
            ->where('activo', true)
            ->select('id', 'nombre', 'descripcion')
            ->get();
    }

    /**
     * Obtiene las categorías existentes para el proveedor
     */
    private function getExistingCategorias(int $proveedorId): Collection
    {
        return Categoria::where('proveedor_id', $proveedorId)
            ->where('activo', true)
            ->whereNull('parent_id') // Solo categorías principales
            ->select('id', 'nombre', 'descripcion', 'nivel')
            ->get();
    }

    /**
     * Obtiene las subcategorías existentes para el proveedor
     */
    private function getExistingSubcategorias(int $proveedorId): Collection
    {


        return Categoria::where('proveedor_id', $proveedorId)
            ->where('activo', true)
            ->whereNotNull('parent_id') // Solo subcategorías
            ->select('id', 'nombre', 'descripcion', 'parent_id', 'nivel')
            ->with('parent:id,nombre')
            ->get();
    }

    /**
     * Get existing unidades de medida for the proveedor
     */
    private function getExistingUnidades(int $proveedorId): Collection
    {
        return UnidadMedida::where('proveedor_id', $proveedorId)
            ->where('estatus', 'activo')
            ->select('id', 'nombre', 'clave', 'descripcion')
            ->get();
    }

    /**
     * Get existing productos for the proveedor
     */
    private function getExistingProductos(int $proveedorId): Collection
    {
        return Producto::where('proveedor_id', $proveedorId)
            ->select('id', 'codigo_interno', 'sku', 'nombre')
            ->get();
    }

    /**
     * Analyze productos: existing vs new vs duplicates
     */
    private function analyzeProductos(array $csvProductos, Collection $existingProductos, array $csvData): array
    {
        $existingCodigos = $existingProductos->pluck('codigo_interno')->toArray();

        $nuevos = array_diff($csvProductos, $existingCodigos);
        $existentes = array_intersect($csvProductos, $existingCodigos);

        // Detect duplicates within CSV data
        $codigoCounts = array_count_values($csvProductos);
        $duplicados = array_filter($codigoCounts, function ($count) {
            return $count > 1;
        });

        return [
            'total' => count($csvProductos),
            'nuevos' => count($nuevos),
            'existentes' => count($existentes),
            'duplicados' => count($duplicados),
            'duplicados_detail' => array_keys($duplicados)
        ];
    }

    /**
     * Analyze marcas: existing vs new
     */
    private function analyzeMarcas(array $csvMarcas, Collection $existingMarcas): array
    {
        $existingNames = $existingMarcas->pluck('nombre')->map('strtolower')->toArray();
        $csvMarcasLower = array_map('strtolower', $csvMarcas);

        $nuevas = [];
        $existentes = [];

        foreach ($csvMarcas as $marca) {
            if (in_array(strtolower($marca), $existingNames)) {
                $existentes[] = $marca;
            } else {
                $nuevas[] = $marca;
            }
        }

        // Merge existing data with new items
        $mergedData = $existingMarcas->map(function ($marca) {
            return [
                'id' => $marca->id,
                'nombre' => $marca->nombre,
                'descripcion' => $marca->descripcion,
                'es_nueva' => false
            ];
        })->toArray();

        // Add new marcas
        foreach ($nuevas as $marca) {
            $mergedData[] = [
                'id' => null,
                'nombre' => $marca,
                'descripcion' => null,
                'es_nueva' => true
            ];
        }

        return [
            'total' => count($csvMarcas),
            'nuevas' => count($nuevas),
            'existentes' => count($existentes),
            'data' => $mergedData
        ];
    }

    /**
     * Analyze categorias: existing vs new
     */
    private function analyzeCategorias(array $csvCategorias, Collection $existingCategorias): array
    {
        $existingNames = $existingCategorias->pluck('nombre')->map('strtolower')->toArray();

        $nuevas = [];
        $existentes = [];

        foreach ($csvCategorias as $categoria) {
            if (in_array(strtolower($categoria), $existingNames)) {
                $existentes[] = $categoria;
            } else {
                $nuevas[] = $categoria;
            }
        }

        // Merge existing data with new items
        $mergedData = $existingCategorias->map(function ($categoria) {
            return [
                'id' => $categoria->id,
                'nombre' => $categoria->nombre,
                'descripcion' => $categoria->descripcion,
                'nivel' => $categoria->nivel,
                'es_nueva' => false
            ];
        })->toArray();

        // Add new categorias
        foreach ($nuevas as $categoria) {
            $mergedData[] = [
                'id' => null,
                'nombre' => $categoria,
                'descripcion' => null,
                'nivel' => 1,
                'es_nueva' => true
            ];
        }

        return [
            'total' => count($csvCategorias),
            'nuevas' => count($nuevas),
            'existentes' => count($existentes),
            'data' => $mergedData
        ];
    }

    /**
     * Analyze subcategorias: existing vs new
     */
    private function analyzeSubcategorias(array $csvSubcategorias, Collection $existingSubcategorias): array
    {
        if (empty($csvSubcategorias)) {
            return [
                'total' => 0,
                'nuevas' => 0,
                'existentes' => 0,
                'data' => []
            ];
        }

        $existingNames = $existingSubcategorias->pluck('nombre')->map('strtolower')->toArray();

        $nuevas = [];
        $existentes = [];

        foreach ($csvSubcategorias as $subcategoria) {
            if (in_array(strtolower($subcategoria), $existingNames)) {
                $existentes[] = $subcategoria;
            } else {
                $nuevas[] = $subcategoria;
            }
        }

        // Merge existing data with new items
        $mergedData = $existingSubcategorias->map(function ($subcategoria) {
            return [
                'id' => $subcategoria->id,
                'nombre' => $subcategoria->nombre,
                'descripcion' => $subcategoria->descripcion,
                'parent_id' => $subcategoria->parent_id,
                'parent_nombre' => $subcategoria->parent ? $subcategoria->parent->nombre : null,
                'nivel' => $subcategoria->nivel,
                'es_nueva' => false
            ];
        })->toArray();

        // Add new subcategorias
        foreach ($nuevas as $subcategoria) {
            $mergedData[] = [
                'id' => null,
                'nombre' => $subcategoria,
                'descripcion' => null,
                'parent_id' => null,
                'parent_nombre' => null,
                'nivel' => 2,
                'es_nueva' => true
            ];
        }

        return [
            'total' => count($csvSubcategorias),
            'nuevas' => count($nuevas),
            'existentes' => count($existentes),
            'data' => $mergedData
        ];
    }

    /**
     * Analyze unidades de medida: existing vs new
     */
    private function analyzeUnidades(array $csvUnidades, Collection $existingUnidades): array
    {
        $existingNames = $existingUnidades->pluck('nombre')->map('strtolower')->toArray();

        $nuevas = [];
        $existentes = [];

        foreach ($csvUnidades as $unidad) {
            if (in_array(strtolower($unidad), $existingNames)) {
                $existentes[] = $unidad;
            } else {
                $nuevas[] = $unidad;
            }
        }

        // Merge existing data with new items
        $mergedData = $existingUnidades->map(function ($unidad) {
            return [
                'id' => $unidad->id,
                'nombre' => $unidad->nombre,
                'clave' => $unidad->clave,
                'descripcion' => $unidad->descripcion,
                'es_nueva' => false
            ];
        })->toArray();

        // Add new unidades
        foreach ($nuevas as $unidad) {
            $mergedData[] = [
                'id' => null,
                'nombre' => $unidad,
                'clave' => null,
                'descripcion' => null,
                'es_nueva' => true
            ];
        }

        return [
            'total' => count($csvUnidades),
            'nuevas' => count($nuevas),
            'existentes' => count($existentes),
            'data' => $mergedData
        ];
    }
}
