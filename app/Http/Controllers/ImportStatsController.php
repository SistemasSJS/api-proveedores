<?php

namespace App\Http\Controllers;

use App\Enums\EstadoImportacion;
use App\Models\ImportAudit;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ImportStatsController extends Controller
{
    use ApiResponse;

    /**
     * Get detailed statistics for a specific import audit
     */
    public function show(ImportAudit $importAudit)
    {
        return $this->success([
            'audit_info' => [
                'id' => $importAudit->id,
                'proveedor_id' => $importAudit->proveedor_id,
                'tipo' => $importAudit->tipo,
                'estado' => $importAudit->estado,
                'fase' => $importAudit->fase,
                'created_at' => $importAudit->created_at,
                'inicio_proceso' => $importAudit->inicio_proceso,
                'fin_proceso' => $importAudit->fin_proceso,
            ],
            'summary' => $importAudit->getImportSummary(),
            'performance_metrics' => $importAudit->getPerformanceMetrics(),
            'error_statistics' => $importAudit->getErrorStatistics(),
            'has_critical_errors' => $importAudit->hasCriticalErrors(),
        ]);
    }

    /**
     * Get logs for a specific import audit
     */
    public function logs(Request $request, ImportAudit $importAudit)
    {
        $level = $request->input('level');
        $limit = $request->input('limit', 100);

        $logs = $importAudit->getStructuredLogs($level, $limit);

        return $this->success([
            'logs' => $logs,
            'total_logs' => count($importAudit->logs ?? []),
            'filtered_count' => count($logs),
            'filters_applied' => [
                'level' => $level,
                'limit' => $limit
            ]
        ]);
    }

    /**
     * Get global import statistics dashboard
     */
    public function dashboard(Request $request)
    {
        $days = $request->input('days', 7);
        $proveedorId = $request->input('proveedor_id');

        $query = ImportAudit::where('created_at', '>=', now()->subDays($days));

        if ($proveedorId) {
            $query->where('proveedor_id', $proveedorId);
        }

        $audits = $query->get();

        // Statistics
        $totalImports = $audits->count();
        $completedImports = $audits->where(
            'estado',
            EstadoImportacion::COMPLETADO->value,
        )->count();
        $failedImports = $audits->where(
            'estado',
            EstadoImportacion::ERROR->value,
        )->count();
        $inProgressImports = $audits->whereIn('estado', [
            EstadoImportacion::PROCESANDO->value,
            EstadoImportacion::PENDIENTE->value,
        ])->count();

        $totalRecords = $audits->sum('total_registros');
        $totalSuccessful = $audits->sum('nuevos') + $audits->sum('actualizados');
        $totalErrors = $audits->sum('errores');

        $successRate = $totalRecords > 0 ? round(($totalSuccessful / $totalRecords) * 100, 2) : 0;

        // Performance metrics
        $avgProcessingTime = $audits->where('processing_time', '>', 0)->avg('processing_time') ?? 0;
        $avgMemoryUsage = $audits->where('memory_usage', '>', 0)->avg('memory_usage') ?? 0;

        // Error types frequency
        $errorTypesFrequency = [];
        foreach ($audits as $audit) {
            $errorTypes = $audit->error_types ?? [];
            foreach ($errorTypes as $type) {
                $errorTypesFrequency[$type] = ($errorTypesFrequency[$type] ?? 0) + 1;
            }
        }

        // Sort error types by frequency
        arsort($errorTypesFrequency);

        // Daily statistics
        $dailyStats = $audits->groupBy(function ($audit) {
            return $audit->created_at->format('Y-m-d');
        })->map(function ($dayAudits) {
            return [
                'total_imports' => $dayAudits->count(),
                'completed' => $dayAudits->where('estado', EstadoImportacion::COMPLETADO->value,)->count(),
                'failed' => $dayAudits->where('estado', EstadoImportacion::ERROR->value,)->count(),
                'total_records' => $dayAudits->sum('total_registros'),
                'successful_records' => $dayAudits->sum('nuevos') + $dayAudits->sum('actualizados'),
                'error_records' => $dayAudits->sum('errores'),
            ];
        });

        return $this->success([
            'period' => [
                'days' => $days,
                'start_date' => now()->subDays($days)->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d'),
                'proveedor_id' => $proveedorId
            ],
            'overview' => [
                'total_imports' => $totalImports,
                'completed_imports' => $completedImports,
                'failed_imports' => $failedImports,
                'in_progress_imports' => $inProgressImports,
                'success_rate' => round(($completedImports / max($totalImports, 1)) * 100, 2),
                'total_records_processed' => $totalRecords,
                'total_successful_records' => $totalSuccessful,
                'total_error_records' => $totalErrors,
                'overall_success_rate' => $successRate
            ],
            'performance' => [
                'avg_processing_time_seconds' => round($avgProcessingTime, 2),
                'avg_memory_usage_mb' => round($avgMemoryUsage, 2),
                'avg_records_per_second' => $avgProcessingTime > 0 ? round($totalRecords / ($avgProcessingTime * $totalImports), 2) : 0
            ],
            'error_analysis' => [
                'most_common_error_types' => array_slice($errorTypesFrequency, 0, 10, true),
                'total_unique_error_types' => count($errorTypesFrequency),
                'imports_with_errors' => $audits->where('errores', '>', 0)->count()
            ],
            'daily_statistics' => $dailyStats,
            'recent_imports' => $audits->sortByDesc('created_at')
                ->take(10)
                ->map(function ($audit) {
                    return [
                        'id' => $audit->id,
                        'tipo' => $audit->tipo,
                        'estado' => $audit->estado,
                        'total_registros' => $audit->total_registros,
                        'errores' => $audit->errores,
                        'success_rate' => $audit->total_registros > 0
                            ? round((($audit->nuevos + $audit->actualizados) / $audit->total_registros) * 100, 2)
                            : 0,
                        'processing_time' => $audit->processing_time,
                        'created_at' => $audit->created_at
                    ];
                })
                ->values()
        ]);
    }

    /**
     * Get error details for a specific import audit
     */
    public function errors(Request $request, ImportAudit $importAudit)
    {
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 50);
        $errorType = $request->input('error_type');

        $errorDetails = $importAudit->errores_detalle ?? [];

        // Filter by error type if specified
        if ($errorType) {
            $errorDetails = array_filter($errorDetails, function ($error) use ($errorType) {
                return ($error['error_type'] ?? '') === $errorType;
            });
        }

        $total = count($errorDetails);
        $offset = ($page - 1) * $perPage;
        $paginatedErrors = array_slice($errorDetails, $offset, $perPage);

        return $this->success([
            'errors' => $paginatedErrors,
            'pagination' => [
                'current_page' => (int)$page,
                'per_page' => (int)$perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
                'has_more' => ($offset + $perPage) < $total
            ],
            'filters' => [
                'error_type' => $errorType
            ],
            'error_statistics' => $importAudit->getErrorStatistics()
        ]);
    }
}
