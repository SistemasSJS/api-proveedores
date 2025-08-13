<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

/**
 * Response para el endpoint de carga CSV
 * POST /api/proveedor/{id}/csv-import
 */
class CsvUploadResponse
{
    public function __construct(
        public int $auditId,
        public string $jobId,
        public string $previewToken,
        public array $fileInfo,
        public array $headers,
        public array $previewData,
        public array $validationSummary,
        public array $qualityMetrics,
        public array $processingInfo,
        public string $estado = 'preview',
        public string $mensaje = 'Archivo CSV analizado correctamente. Revise los datos de vista previa antes de confirmar la importación.'
    ) {}

    /**
     * Convierte la respuesta a array para ApiResponse
     */
    public function toArray(): array
    {
        return [
            'audit_id' => $this->auditId,
            'job_id' => $this->jobId,
            'preview_token' => $this->previewToken,
            'file_info' => $this->fileInfo,
            'headers' => $this->headers,
            'preview_data' => $this->previewData,
            'validation_summary' => $this->validationSummary,
            'quality_metrics' => $this->qualityMetrics,
            'processing_info' => $this->processingInfo,
            'estado' => $this->estado,
            'mensaje' => $this->mensaje
        ];
    }

    /**
     * Crear desde array de resultados del procesador CSV
     */
    public static function fromProcessorResult(int $auditId, string $jobId, array $processingResult): self
    {
        return new self(
            auditId: $auditId,
            jobId: $jobId,
            previewToken: $processingResult['preview_token'],
            fileInfo: $processingResult['file_info'],
            headers: $processingResult['headers'],
            previewData: $processingResult['preview_data'],
            validationSummary: $processingResult['validation_summary'],
            qualityMetrics: $processingResult['quality_metrics'],
            processingInfo: $processingResult['processing_info']
        );
    }
}
