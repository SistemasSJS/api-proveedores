<?php

namespace App\Services;

use App\Models\ImportValidationCache;
use App\Services\ProductImportValidator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CSVProcessorService
{
    private ProductImportValidator $validator;
    
    public function __construct(ProductImportValidator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * Process CSV file and generate preview with validation
     */
    public function processCSVPreview(
        UploadedFile $csvFile, 
        int $proveedorId,
        array $options = []
    ): array {
        $startTime = microtime(true);
        $token = $this->generatePreviewToken();
        
        // Default options
        $options = array_merge([
            'delimiter' => ',',
            'encoding' => 'UTF-8',
            'has_header' => true,
            'preview_rows' => 100,
            'strict_validation' => false,
            'auto_create_relations' => true
        ], $options);

        try {
            // Parse CSV file
            $csvData = $this->parseCSV($csvFile, $options);
            
            // Validate headers if present
            $headerValidation = [];
            if ($options['has_header'] && !empty($csvData['headers'])) {
                $headerValidation = $this->validator->validateHeaders($csvData['headers']);
            }

            // Get preview data (limited rows)
            $previewData = array_slice($csvData['data'], 0, $options['preview_rows']);
            
            // Validate preview data
            $validationResults = $this->validator->validateBatch($previewData);

            // Generate quality metrics
            $qualityMetrics = $this->generateQualityMetrics($validationResults, $csvData);

            // Cache the full data for later confirmation
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
                    'mapping_suggestions' => $this->generateHeaderMappingSuggestions($csvData['headers'] ?? [])
                ],
                'preview_data' => array_slice($previewData, 0, 10), // Solo primeras 10 para el frontend
                'validation_summary' => $validationResults['summary'],
                'validation_details' => array_slice($validationResults['validation_results'], 0, 20), // Primeros 20 errores
                'quality_metrics' => $qualityMetrics,
                'processing_info' => [
                    'processing_time' => $processingTime,
                    'memory_used' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB',
                    'can_proceed' => $qualityMetrics['can_proceed'] ?? false
                ]
            ];

        } catch (\Throwable $e) {
            Log::error('Error processing CSV preview', [
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
     * Parse CSV file with specified options
     */
    private function parseCSV(UploadedFile $file, array $options): array
    {
        $delimiter = $options['delimiter'];
        $encoding = $options['encoding'];
        $hasHeader = $options['has_header'];

        // Read file content
        $content = file_get_contents($file->getRealPath());
        
        // Convert encoding if needed
        if ($encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }

        // Split into lines
        $lines = array_filter(array_map('trim', explode("\n", $content)));
        
        if (empty($lines)) {
            throw new \Exception('El archivo CSV está vacío');
        }

        $data = [];
        $headers = [];

        foreach ($lines as $index => $line) {
            // Parse CSV line
            $row = str_getcsv($line, $delimiter);
            
            if ($index === 0 && $hasHeader) {
                $headers = array_map('trim', $row);
                continue;
            }

            // Convert row to associative array if headers exist
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
     * Generate quality metrics for the imported data
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
     * Generate header mapping suggestions
     */
    private function generateHeaderMappingSuggestions(array $detectedHeaders): array
    {
        $expectedHeaders = $this->validator->getExpectedHeaders();
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
     * Find best header match using similarity
     */
    private function findBestHeaderMatch(string $detectedHeader, array $expectedHeaders): ?string
    {
        $bestMatch = null;
        $highestSimilarity = 0;

        $normalizedDetected = strtolower(trim($detectedHeader));

        foreach ($expectedHeaders as $expectedHeader) {
            $normalizedExpected = strtolower($expectedHeader);
            
            // Exact match
            if ($normalizedDetected === $normalizedExpected) {
                return $expectedHeader;
            }

            // Partial match
            similar_text($normalizedDetected, $normalizedExpected, $similarity);
            
            if ($similarity > $highestSimilarity && $similarity > 60) {
                $highestSimilarity = $similarity;
                $bestMatch = $expectedHeader;
            }
        }

        return $bestMatch;
    }

    /**
     * Calculate header similarity percentage
     */
    private function calculateHeaderSimilarity(string $header1, string $header2): float
    {
        similar_text(strtolower($header1), strtolower($header2), $percent);
        return round($percent, 1);
    }

    /**
     * Get quality recommendation based on score and error rate
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
     * Estimate processing time based on number of rows
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
     * Cache preview data for later confirmation
     */
    private function cachePreviewData(string $token, array $data): void
    {
        // Cache in Redis/database for 1 hour
        Cache::put("csv_preview:{$token}", $data, 3600);
        
        // Also store in ImportValidationCache for persistence
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
     * Retrieve cached preview data
     */
    public function getCachedPreviewData(string $token): ?array
    {
        // Try cache first
        $data = Cache::get("csv_preview:{$token}");
        
        if (!$data) {
            // Try database
            $cached = ImportValidationCache::where('token', $token)
                ->where('expires_at', '>', now())
                ->first();
                
            if ($cached) {
                $data = $cached->validation_data;
                // Re-cache for faster access
                Cache::put("csv_preview:{$token}", $data, 3600);
            }
        }

        return $data;
    }

    /**
     * Generate unique preview token
     */
    private function generatePreviewToken(): string
    {
        return hash('sha256', Str::random(32) . microtime(true));
    }

    /**
     * Clean up expired preview data
     */
    public function cleanupExpiredPreviews(): int
    {
        return ImportValidationCache::where('expires_at', '<', now())->delete();
    }
}
