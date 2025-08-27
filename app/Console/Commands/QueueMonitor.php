<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\ImportAudit;
use Carbon\Carbon;

class QueueMonitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:monitor 
                          {--refresh=0 : Auto-refresh every N seconds (0 = no refresh)}
                          {--detailed : Show detailed information}
                          {--json : Output as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitorea el estado de las colas y jobs de Laravel';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $refreshInterval = (int) $this->option('refresh');
        $detailed = $this->option('detailed');
        $jsonOutput = $this->option('json');

        if ($refreshInterval > 0) {
            $this->info("Auto-refresh activado cada {$refreshInterval} segundos. Presiona Ctrl+C para salir.");
            $this->info("");
            
            while (true) {
                // Clear screen in non-JSON mode
                if (!$jsonOutput && PHP_OS_FAMILY === 'Windows') {
                    system('cls');
                } elseif (!$jsonOutput) {
                    system('clear');
                }
                
                $this->displayMonitor($detailed, $jsonOutput);
                
                sleep($refreshInterval);
            }
        } else {
            $this->displayMonitor($detailed, $jsonOutput);
        }

        return Command::SUCCESS;
    }

    /**
     * Display the queue monitor information
     */
    protected function displayMonitor($detailed = false, $jsonOutput = false): void
    {
        $data = $this->gatherQueueData($detailed);

        if ($jsonOutput) {
            $this->line(json_encode($data, JSON_PRETTY_PRINT));
            return;
        }

        $this->displayFormattedOutput($data, $detailed);
    }

    /**
     * Gather all queue-related data
     */
    protected function gatherQueueData($detailed = false): array
    {
        // Basic queue statistics
        $pendingJobs = DB::table('jobs')->count();
        $failedJobs = DB::table('failed_jobs')->count();
        $processingImports = ImportAudit::where('estado', 'procesando')->count();

        // Recent imports
        $recentImports = ImportAudit::orderBy('created_at', 'desc')
            ->take(5)
            ->get(['id', 'estado', 'progreso', 'created_at', 'archivo', 'proveedor_id']);

        $data = [
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'queue_stats' => [
                'pending_jobs' => $pendingJobs,
                'failed_jobs' => $failedJobs,
                'processing_imports' => $processingImports,
            ],
            'recent_imports' => $recentImports->toArray(),
        ];

        if ($detailed) {
            $data = array_merge($data, $this->getDetailedStats());
        }

        return $data;
    }

    /**
     * Get detailed statistics
     */
    protected function getDetailedStats(): array
    {
        // Import states distribution
        $importStates = ImportAudit::select('estado', DB::raw('COUNT(*) as count'))
            ->groupBy('estado')
            ->pluck('count', 'estado')
            ->toArray();

        // Recent failed jobs
        $recentFailedJobs = DB::table('failed_jobs')
            ->select('payload', 'exception', 'failed_at')
            ->orderBy('failed_at', 'desc')
            ->take(3)
            ->get()
            ->map(function ($job) {
                $payload = json_decode($job->payload, true);
                return [
                    'job_class' => $payload['displayName'] ?? 'Unknown',
                    'exception' => substr($job->exception, 0, 200) . '...',
                    'failed_at' => $job->failed_at,
                ];
            });

        // System stats
        $systemStats = [
            'memory_usage' => memory_get_usage(true) / 1024 / 1024, // MB
            'memory_peak' => memory_get_peak_usage(true) / 1024 / 1024, // MB
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ];

        // Jobs by queue
        $jobsByQueue = DB::table('jobs')
            ->select('queue', DB::raw('COUNT(*) as count'))
            ->groupBy('queue')
            ->pluck('count', 'queue')
            ->toArray();

        return [
            'import_states' => $importStates,
            'recent_failed_jobs' => $recentFailedJobs->toArray(),
            'system_stats' => $systemStats,
            'jobs_by_queue' => $jobsByQueue,
        ];
    }

    /**
     * Display formatted output to console
     */
    protected function displayFormattedOutput($data, $detailed = false): void
    {
        // Header
        $this->info('==========================================');
        $this->info('     Monitor de Colas Laravel - API');
        $this->info('==========================================');
        $this->line("Actualizado: {$data['timestamp']}");
        $this->newLine();

        // Queue Statistics
        $this->info('📊 Estadísticas de Colas');
        $this->line("Jobs pendientes: {$data['queue_stats']['pending_jobs']}");
        $this->line("Jobs fallidos: {$data['queue_stats']['failed_jobs']}");
        $this->line("Importaciones en proceso: {$data['queue_stats']['processing_imports']}");
        $this->newLine();

        // Recent Imports Table
        if (!empty($data['recent_imports'])) {
            $this->info('📋 Últimas 5 importaciones');
            
            $tableData = collect($data['recent_imports'])->map(function ($import) {
                return [
                    $import['id'],
                    $this->colorizeState($import['estado']),
                    $import['progreso'] . '%',
                    substr($import['archivo'] ?? 'N/A', 0, 20),
                    Carbon::parse($import['created_at'])->format('d/m H:i'),
                ];
            })->toArray();

            $this->table(
                ['ID', 'Estado', 'Progreso', 'Archivo', 'Fecha'],
                $tableData
            );
        }

        // Detailed information
        if ($detailed) {
            $this->displayDetailedInfo($data);
        }

        // Status indicators
        $this->displayStatusIndicators($data['queue_stats']);
    }

    /**
     * Display detailed information
     */
    protected function displayDetailedInfo($data): void
    {
        $this->newLine();
        $this->info('🔍 Información Detallada');

        // Import states
        if (!empty($data['import_states'])) {
            $this->line('Estados de importación:');
            foreach ($data['import_states'] as $state => $count) {
                $this->line("  - {$this->colorizeState($state)}: {$count}");
            }
        }

        // System stats
        if (!empty($data['system_stats'])) {
            $this->newLine();
            $this->line('Sistema:');
            $this->line("  - Memoria actual: " . round($data['system_stats']['memory_usage'], 2) . " MB");
            $this->line("  - Memoria pico: " . round($data['system_stats']['memory_peak'], 2) . " MB");
            $this->line("  - PHP: " . $data['system_stats']['php_version']);
            $this->line("  - Laravel: " . $data['system_stats']['laravel_version']);
        }

        // Recent failed jobs
        if (!empty($data['recent_failed_jobs'])) {
            $this->newLine();
            $this->error('❌ Jobs Fallidos Recientes:');
            foreach ($data['recent_failed_jobs'] as $failedJob) {
                $this->line("  - {$failedJob['job_class']} ({$failedJob['failed_at']})");
                $this->line("    Error: " . $failedJob['exception']);
            }
        }
    }

    /**
     * Display status indicators
     */
    protected function displayStatusIndicators($queueStats): void
    {
        $this->newLine();
        $this->info('🚦 Estado del Sistema');

        // Queue health
        if ($queueStats['pending_jobs'] === 0) {
            $this->line('✅ Cola vacía - Sistema inactivo');
        } elseif ($queueStats['pending_jobs'] < 10) {
            $this->line('🟡 Pocos jobs pendientes - Sistema funcionando normalmente');
        } else {
            $this->line('🔴 Muchos jobs pendientes - Posible retraso en el procesamiento');
        }

        // Failed jobs alert
        if ($queueStats['failed_jobs'] > 0) {
            $this->error("⚠️  Hay {$queueStats['failed_jobs']} jobs fallidos que requieren atención");
        } else {
            $this->line('✅ No hay jobs fallidos');
        }

        // Processing status
        if ($queueStats['processing_imports'] > 0) {
            $this->line("🔄 {$queueStats['processing_imports']} importaciones en progreso");
        }
    }

    /**
     * Colorize state names for better visibility
     */
    protected function colorizeState($state): string
    {
        $colors = [
            'completado' => '<info>%s</info>',
            'procesando' => '<comment>%s</comment>',
            'error' => '<error>%s</error>',
            'pendiente' => '<question>%s</question>',
        ];

        return sprintf(
            $colors[$state] ?? '%s',
            $state
        );
    }
}
