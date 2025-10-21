<?php

namespace App\Services\CSVImport;

use App\Models\ImportAudit;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class CSVImportExportService
{
    /**
     * Exportar resultados de importación en el formato especificado
     *
     * @param  string  $format  Formato: xlsx, csv, pdf
     * @param  string  $type  Tipo: report, data, summary
     */
    public function exportImportResults(ImportAudit $audit, string $format = 'xlsx', string $type = 'report'): Response
    {
        try {
            // Validar formato
            $validFormats = ['xlsx', 'csv', 'pdf'];
            if (! in_array($format, $validFormats)) {
                throw new Exception("Formato no soportado: {$format}");
            }

            // Validar tipo
            $validTypes = ['report', 'data', 'summary'];
            if (! in_array($type, $validTypes)) {
                throw new Exception("Tipo de exportación no soportado: {$type}");
            }

            // Preparar datos según el tipo
            $data = $this->prepareDataForExport($audit, $type);

            // Generar el archivo según el formato
            switch ($format) {
                case 'xlsx':
                    return $this->exportToExcel($data, $audit, $type);
                case 'csv':
                    return $this->exportToCsv($data, $audit, $type);
                case 'pdf':
                    return $this->exportToPdf($data, $audit, $type);
                default:
                    throw new Exception("Formato no implementado: {$format}");
            }
        } catch (Exception $e) {
            Log::error('Error exportando resultados de importación', [
                'audit_id' => $audit->id,
                'format' => $format,
                'type' => $type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Preparar datos para exportación según el tipo
     */
    private function prepareDataForExport(ImportAudit $audit, string $type): array
    {
        $baseData = [
            'audit_id' => $audit->id,
            'proveedor_id' => $audit->proveedor_id,
            'archivo' => $audit->archivo,
            'estado' => $audit->estado,
            'fecha_creacion' => $audit->created_at,
            'inicio_proceso' => $audit->inicio_proceso,
            'fin_proceso' => $audit->fin_proceso,
            'processing_time' => $this->calculateProcessingTime($audit),
        ];

        switch ($type) {
            case 'report':
                return $this->prepareReportData($audit, $baseData);
            case 'data':
                return $this->prepareDetailedData($audit, $baseData);
            case 'summary':
                return $this->prepareSummaryData($audit, $baseData);
            default:
                return $baseData;
        }
    }

    /**
     * Preparar datos para reporte completo
     */
    private function prepareReportData(ImportAudit $audit, array $baseData): array
    {
        return array_merge($baseData, [
            'estadisticas' => [
                'total_procesados' => $audit->total_registros ?? 0,
                'nuevos' => $audit->nuevos ?? 0,
                'actualizados' => $audit->actualizados ?? 0,
                'errores' => $audit->errores ?? 0,
                'tasa_exito' => $this->calculateSuccessRate($audit),
            ],
            'breakdown_catalogos' => [
                'marcas' => [
                    'importadas' => $audit->marca_imported ?? 0,
                    'errores' => $audit->marca_errors ?? 0,
                    'total' => $audit->marca_total ?? 0,
                ],
                'categorias' => [
                    'importadas' => $audit->categoria_imported ?? 0,
                    'errores' => $audit->categoria_errors ?? 0,
                    'total' => $audit->categoria_total ?? 0,
                ],
                'unidades' => [
                    'importadas' => $audit->unidad_imported ?? 0,
                    'errores' => $audit->unidad_errors ?? 0,
                    'total' => $audit->unidad_total ?? 0,
                ],
            ],
            'errores_detalle' => $audit->errores_detalle ?? [],
            'logs' => $audit->logs ?? [],
        ]);
    }

    /**
     * Preparar datos detallados
     */
    private function prepareDetailedData(ImportAudit $audit, array $baseData): array
    {
        $data = $this->prepareReportData($audit, $baseData);

        // Agregar datos más detallados si están disponibles
        if ($audit->preview_data) {
            $data['preview_data'] = $audit->preview_data;
        }

        return $data;
    }

    /**
     * Preparar datos de resumen
     */
    private function prepareSummaryData(ImportAudit $audit, array $baseData): array
    {
        return array_merge($baseData, [
            'resumen' => [
                'total_registros' => $audit->total_registros ?? 0,
                'exitosos' => ($audit->nuevos ?? 0) + ($audit->actualizados ?? 0),
                'errores' => $audit->errores ?? 0,
                'tasa_exito' => $this->calculateSuccessRate($audit),
                'duracion' => $this->formatProcessingTime($audit),
            ],
        ]);
    }

    /**
     * Exportar a Excel
     */
    private function exportToExcel(array $data, ImportAudit $audit, string $type): Response
    {
        $filename = $this->generateFilename($audit, $type, 'xlsx');

        // Usar Excel::create para versión 1.1.5
        return Excel::create($filename, function ($excel) use ($data, $type) {
            $excel->sheet('Resumen', function ($sheet) use ($data, $type) {
                $this->populateSheet($sheet, $data, $type);
            });
        })->export('xlsx');
    }

    /**
     * Exportar a CSV
     */
    private function exportToCsv(array $data, ImportAudit $audit, string $type): Response
    {
        $filename = $this->generateFilename($audit, $type, 'csv');

        return Excel::create($filename, function ($excel) use ($data, $type) {
            $excel->sheet('Datos', function ($sheet) use ($data, $type) {
                $this->populateSheet($sheet, $data, $type);
            });
        })->export('csv');
    }

    /**
     * Exportar a PDF (placeholder - requiere implementación adicional)
     */
    private function exportToPdf(array $data, ImportAudit $audit, string $type): Response
    {
        // Por ahora, devolver CSV hasta implementar PDF específico
        return $this->exportToCsv($data, $audit, $type);
    }

    /**
     * Poblar hoja de Excel con datos
     */
    private function populateSheet($sheet, array $data, string $type): void
    {
        switch ($type) {
            case 'report':
                $this->populateReportSheet($sheet, $data);
                break;
            case 'summary':
                $this->populateSummarySheet($sheet, $data);
                break;
            default:
                $this->populateBasicSheet($sheet, $data);
                break;
        }
    }

    /**
     * Poblar hoja de reporte completo
     */
    private function populateReportSheet($sheet, array $data): void
    {
        $sheet->row(1, ['Reporte de Importación CSV']);
        $sheet->row(2, []);

        $row = 3;
        $sheet->row($row++, ['ID Audit:', $data['audit_id']]);
        $sheet->row($row++, ['Proveedor ID:', $data['proveedor_id']]);
        $sheet->row($row++, ['Estado:', $data['estado']]);
        $sheet->row($row++, ['Archivo:', basename($data['archivo'] ?? 'N/A')]);
        $sheet->row($row++, ['Tiempo procesamiento:', ($data['processing_time'] ?? 0).'s']);

        $row++;
        $sheet->row($row++, ['Estadísticas:']);

        $stats = $data['estadisticas'] ?? [];
        $sheet->row($row++, ['Total procesados:', $stats['total_procesados'] ?? 0]);
        $sheet->row($row++, ['Nuevos:', $stats['nuevos'] ?? 0]);
        $sheet->row($row++, ['Actualizados:', $stats['actualizados'] ?? 0]);
        $sheet->row($row++, ['Errores:', $stats['errores'] ?? 0]);
        $sheet->row($row++, ['Tasa de éxito (%):', $stats['tasa_exito'] ?? 0]);

        $breakdown = $data['breakdown_catalogos'] ?? [];
        if (! empty($breakdown)) {
            $row++;
            $sheet->row($row++, ['Catálogos:']);
            foreach ($breakdown as $catalogo => $info) {
                $sheet->row($row++, [ucfirst($catalogo).' importadas:', $info['importadas'] ?? 0]);
                $sheet->row($row++, [ucfirst($catalogo).' errores:', $info['errores'] ?? 0]);
            }
        }
    }

    /**
     * Poblar hoja de resumen
     */
    private function populateSummarySheet($sheet, array $data): void
    {
        $sheet->row(1, ['Resumen de Importación']);
        $sheet->row(2, []);

        $resumen = $data['resumen'] ?? [];
        $sheet->row(3, ['Audit ID:', $data['audit_id']]);
        $sheet->row(4, ['Total registros:', $resumen['total_registros'] ?? 0]);
        $sheet->row(5, ['Exitosos:', $resumen['exitosos'] ?? 0]);
        $sheet->row(6, ['Errores:', $resumen['errores'] ?? 0]);
        $sheet->row(7, ['Tasa de éxito (%):', $resumen['tasa_exito'] ?? 0]);
        $sheet->row(8, ['Duración:', $resumen['duracion'] ?? 'N/A']);
        $sheet->row(9, ['Estado:', $data['estado']]);
    }

    /**
     * Poblar hoja básica
     */
    private function populateBasicSheet($sheet, array $data): void
    {
        $sheet->row(1, ['Datos de Importación']);
        $sheet->row(2, []);

        $row = 3;
        foreach ($data as $key => $value) {
            if (! is_array($value)) {
                $sheet->row($row++, [$key, $value]);
            }
        }
    }

    /**
     * Generar nombre de archivo
     */
    private function generateFilename(ImportAudit $audit, string $type, string $extension): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');

        return "import_{$type}_audit_{$audit->id}_proveedor_{$audit->proveedor_id}_{$timestamp}.{$extension}";
    }

    /**
     * Calcular tiempo de procesamiento
     */
    private function calculateProcessingTime(ImportAudit $audit): float
    {
        if ($audit->inicio_proceso && $audit->fin_proceso) {
            return round($audit->fin_proceso->diffInSeconds($audit->inicio_proceso, true), 2);
        }

        return 0.0;
    }

    /**
     * Formatear tiempo de procesamiento
     */
    private function formatProcessingTime(ImportAudit $audit): string
    {
        $seconds = $this->calculateProcessingTime($audit);

        if ($seconds < 60) {
            return "{$seconds}s";
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            $remainingSeconds = $seconds % 60;

            return "{$minutes}m {$remainingSeconds}s";
        } else {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);

            return "{$hours}h {$minutes}m";
        }
    }

    /**
     * Calcular tasa de éxito
     */
    private function calculateSuccessRate(ImportAudit $audit): float
    {
        $total = $audit->total_registros ?? 0;
        if ($total === 0) {
            return 0.0;
        }

        $successful = ($audit->nuevos ?? 0) + ($audit->actualizados ?? 0);

        return round(($successful / $total) * 100, 2);
    }
}
