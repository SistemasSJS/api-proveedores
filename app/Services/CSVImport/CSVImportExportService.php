<?php

namespace App\Services\CSVImport;

use App\Models\ImportAudit;
use Illuminate\Support\Collection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Exception;

class CSVImportExportService
{
    /**
     * Exportar resultados de importación en el formato especificado
     *
     * @param ImportAudit $audit
     * @param string $format Formato: xlsx, csv, pdf
     * @param string $type Tipo: report, data, summary, errors
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function exportImportResults(ImportAudit $audit, string $format = 'xlsx', string $type = 'report'): \Symfony\Component\HttpFoundation\Response
    {
        try {
            // Validar formato
            $validFormats = ['xlsx', 'csv', 'pdf'];
            if (!in_array($format, $validFormats)) {
                throw new Exception("Formato no soportado: {$format}");
            }

            // Validar tipo
            $validTypes = ['report', 'data', 'summary', 'errors'];
            if (!in_array($type, $validTypes)) {
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
                'trace' => $e->getTraceAsString()
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
            case 'errors':
                return $this->prepareErrorsData($audit, $baseData);
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
                'tasa_exito' => $this->calculateSuccessRate($audit)
            ],
            'breakdown_catalogos' => [
                'marcas' => [
                    'importadas' => $audit->marca_imported ?? 0,
                    'errores' => $audit->marca_errors ?? 0,
                    'total' => $audit->marca_total ?? 0
                ],
                'categorias' => [
                    'importadas' => $audit->categoria_imported ?? 0,
                    'errores' => $audit->categoria_errors ?? 0,
                    'total' => $audit->categoria_total ?? 0
                ],
                'unidades' => [
                    'importadas' => $audit->unidad_imported ?? 0,
                    'errores' => $audit->unidad_errors ?? 0,
                    'total' => $audit->unidad_total ?? 0
                ]
            ],
            'errores_detalle' => $audit->errores_detalle ?? [],
            'logs' => $audit->logs ?? []
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
                'duracion' => $this->formatProcessingTime($audit)
            ]
        ]);
    }

    /**
     * Preparar datos específicamente para errores
     */
    private function prepareErrorsData(ImportAudit $audit, array $baseData): array
    {
        $erroresDetalle = $audit->errores_detalle ?? [];
        $registrosConError = [];

        // Procesar los errores detallados para crear una estructura tabular
        if (is_array($erroresDetalle) || is_object($erroresDetalle)) {
            foreach ($erroresDetalle as $index => $error) {
                if (is_array($error)) {
                    $registrosConError[] = [
                        'fila' => $error['fila'] ?? $index + 1,
                        'codigo_producto' => $error['codigo'] ?? $error['codigo_producto'] ?? 'N/A',
                        'nombre_producto' => $error['nombre'] ?? $error['producto'] ?? 'N/A',
                        'categoria' => $error['categoria'] ?? 'N/A',
                        'marca' => $error['marca'] ?? 'N/A',
                        'unidad' => $error['unidad'] ?? $error['unidad_medida'] ?? 'N/A',
                        'precio' => $error['precio'] ?? 'N/A',
                        'error_tipo' => $error['tipo_error'] ?? $error['error_type'] ?? 'Validation Error',
                        'error_mensaje' => $error['mensaje'] ?? $error['message'] ?? $error['error'] ?? 'Error no especificado',
                        'campos_afectados' => is_array($error['campos'] ?? null) ? implode(', ', $error['campos']) : ($error['campo'] ?? 'N/A'),
                        'datos_originales' => isset($error['data']) ? json_encode($error['data']) : 'N/A',
                        'timestamp' => $error['timestamp'] ?? $error['created_at'] ?? now()->toISOString()
                    ];
                } else {
                    // Si el error es un string simple
                    $registrosConError[] = [
                        'fila' => $index + 1,
                        'codigo_producto' => 'N/A',
                        'nombre_producto' => 'N/A',
                        'categoria' => 'N/A',
                        'marca' => 'N/A',
                        'unidad' => 'N/A',
                        'precio' => 'N/A',
                        'error_tipo' => 'General Error',
                        'error_mensaje' => (string) $error,
                        'campos_afectados' => 'N/A',
                        'datos_originales' => 'N/A',
                        'timestamp' => now()->toISOString()
                    ];
                }
            }
        }

        return array_merge($baseData, [
            'total_errores' => count($registrosConError),
            'estadisticas_errores' => [
                'validation_errors' => count(array_filter($registrosConError, fn($r) => $r['error_tipo'] === 'Validation Error')),
                'general_errors' => count(array_filter($registrosConError, fn($r) => $r['error_tipo'] === 'General Error')),
                'database_errors' => count(array_filter($registrosConError, fn($r) => str_contains(strtolower($r['error_tipo']), 'database'))),
            ],
            'registros_errores' => $registrosConError,
            'columnas_export' => [
                'fila',
                'codigo_producto',
                'nombre_producto',
                'categoria',
                'marca',
                'unidad',
                'precio',
                'error_tipo',
                'error_mensaje',
                'campos_afectados',
                'datos_originales',
                'timestamp'
            ]
        ]);
    }

    /**
     * Exportar a Excel (usando CSV como base)
     */
    private function exportToExcel(array $data, ImportAudit $audit, string $type): StreamedResponse
    {
        // Para Excel, usaremos CSV con extensión .xlsx para compatibilidad
        return $this->exportToCsv($data, $audit, $type, 'xlsx');
    }

    /**
     * Exportar a CSV usando funcionalidad nativa de PHP
     */
    private function exportToCsv(array $data, ImportAudit $audit, string $type, string $extension = 'csv'): StreamedResponse
    {
        $filename = $this->generateFilename($audit, $type, $extension);

        return new StreamedResponse(function () use ($data, $type) {
            $handle = fopen('php://output', 'w');

            // Agregar BOM para UTF-8 (para que Excel abra correctamente caracteres especiales)
            fwrite($handle, "\xEF\xBB\xBF");

            // Generar contenido según el tipo
            $this->generateCsvContent($handle, $data, $type);

            fclose($handle);
        }, 200, [
            'Content-Type' => $extension === 'xlsx' ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' : 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ]);
    }

    /**
     * Exportar a PDF (usando CSV como base)
     */
    private function exportToPdf(array $data, ImportAudit $audit, string $type): StreamedResponse
    {
        // Por ahora, devolver CSV hasta implementar PDF específico
        return $this->exportToCsv($data, $audit, $type, 'pdf');
    }

    /**
     * Generar contenido CSV según el tipo de reporte
     */
    private function generateCsvContent($handle, array $data, string $type): void
    {
        switch ($type) {
            case 'report':
                $this->generateReportCsv($handle, $data);
                break;
            case 'summary':
                $this->generateSummaryCsv($handle, $data);
                break;
            case 'errors':
                $this->generateErrorsCsv($handle, $data);
                break;
            default:
                $this->generateBasicCsv($handle, $data);
                break;
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

    /**
     * Generar CSV para reporte completo
     */
    private function generateReportCsv($handle, array $data): void
    {
        // Título
        fputcsv($handle, ['Reporte de Importación CSV']);
        fputcsv($handle, []);

        // Información básica
        fputcsv($handle, ['ID Audit:', $data['audit_id']]);
        fputcsv($handle, ['Proveedor ID:', $data['proveedor_id']]);
        fputcsv($handle, ['Estado:', $data['estado']]);
        fputcsv($handle, ['Archivo:', basename($data['archivo'] ?? 'N/A')]);
        fputcsv($handle, ['Tiempo procesamiento:', ($data['processing_time'] ?? 0) . 's']);
        fputcsv($handle, []);

        // Estadísticas
        fputcsv($handle, ['Estadísticas:']);
        $stats = $data['estadisticas'] ?? [];
        fputcsv($handle, ['Total procesados:', $stats['total_procesados'] ?? 0]);
        fputcsv($handle, ['Nuevos:', $stats['nuevos'] ?? 0]);
        fputcsv($handle, ['Actualizados:', $stats['actualizados'] ?? 0]);
        fputcsv($handle, ['Errores:', $stats['errores'] ?? 0]);
        fputcsv($handle, ['Tasa de éxito (%):', $stats['tasa_exito'] ?? 0]);
        fputcsv($handle, []);

        // Breakdown por catálogos
        $breakdown = $data['breakdown_catalogos'] ?? [];
        if (!empty($breakdown)) {
            fputcsv($handle, ['Catálogos:']);
            foreach ($breakdown as $catalogo => $info) {
                fputcsv($handle, [ucfirst($catalogo) . ' importadas:', $info['importadas'] ?? 0]);
                fputcsv($handle, [ucfirst($catalogo) . ' errores:', $info['errores'] ?? 0]);
            }
        }
    }

    /**
     * Generar CSV para resumen
     */
    private function generateSummaryCsv($handle, array $data): void
    {
        // Título
        fputcsv($handle, ['Resumen de Importación']);
        fputcsv($handle, []);

        // Información de resumen
        $resumen = $data['resumen'] ?? [];
        fputcsv($handle, ['Audit ID:', $data['audit_id']]);
        fputcsv($handle, ['Total registros:', $resumen['total_registros'] ?? 0]);
        fputcsv($handle, ['Exitosos:', $resumen['exitosos'] ?? 0]);
        fputcsv($handle, ['Errores:', $resumen['errores'] ?? 0]);
        fputcsv($handle, ['Tasa de éxito (%):', $resumen['tasa_exito'] ?? 0]);
        fputcsv($handle, ['Duración:', $resumen['duracion'] ?? 'N/A']);
        fputcsv($handle, ['Estado:', $data['estado']]);
    }

    /**
     * Generar CSV para errores en formato tabular
     */
    private function generateErrorsCsv($handle, array $data): void
    {
        // Título e información del encabezado
        fputcsv($handle, ['Registros con Errores - Importación CSV']);
        fputcsv($handle, ['Audit ID: ' . $data['audit_id'] . ' | Proveedor: ' . $data['proveedor_id'] . ' | Total Errores: ' . ($data['total_errores'] ?? 0)]);
        fputcsv($handle, []);

        // Estadísticas de errores
        if (!empty($data['estadisticas_errores'])) {
            $stats = $data['estadisticas_errores'];
            fputcsv($handle, ['Resumen de Errores:']);
            fputcsv($handle, ['Errores de Validación:', $stats['validation_errors'] ?? 0]);
            fputcsv($handle, ['Errores Generales:', $stats['general_errors'] ?? 0]);
            fputcsv($handle, ['Errores de Base de Datos:', $stats['database_errors'] ?? 0]);
            fputcsv($handle, []);
        }

        // Encabezados de la tabla de errores
        $headers = [
            'Fila',
            'Código Producto',
            'Nombre Producto',
            'Categoría',
            'Marca',
            'Unidad',
            'Precio',
            'Tipo Error',
            'Mensaje Error',
            'Campos Afectados',
            'Datos Originales',
            'Timestamp'
        ];

        fputcsv($handle, $headers);

        // Datos de errores
        $registrosErrores = $data['registros_errores'] ?? [];

        foreach ($registrosErrores as $error) {
            fputcsv($handle, [
                $error['fila'] ?? 'N/A',
                $error['codigo_producto'] ?? 'N/A',
                $error['nombre_producto'] ?? 'N/A',
                $error['categoria'] ?? 'N/A',
                $error['marca'] ?? 'N/A',
                $error['unidad'] ?? 'N/A',
                $error['precio'] ?? 'N/A',
                $error['error_tipo'] ?? 'N/A',
                $error['error_mensaje'] ?? 'N/A',
                $error['campos_afectados'] ?? 'N/A',
                $error['datos_originales'] ?? 'N/A',
                $error['timestamp'] ?? 'N/A'
            ]);
        }

        // Si no hay errores registrados, mostrar mensaje
        if (empty($registrosErrores)) {
            fputcsv($handle, ['No se encontraron registros con errores detallados.']);
        }

        // Información adicional al final
        fputcsv($handle, []);
        fputcsv($handle, ['Información Adicional:']);
        fputcsv($handle, ['Archivo Procesado:', basename($data['archivo'] ?? 'N/A')]);
        fputcsv($handle, ['Tiempo de Procesamiento:', ($data['processing_time'] ?? 0) . 's']);
        fputcsv($handle, ['Estado del Proceso:', $data['estado'] ?? 'N/A']);
        fputcsv($handle, ['Fecha de Generación:', now()->format('Y-m-d H:i:s')]);
    }

    /**
     * Generar CSV básico
     */
    private function generateBasicCsv($handle, array $data): void
    {
        fputcsv($handle, ['Datos de Importación']);
        fputcsv($handle, []);

        foreach ($data as $key => $value) {
            if (!is_array($value)) {
                fputcsv($handle, [$key, $value]);
            }
        }
    }
}
