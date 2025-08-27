<?php

namespace App\Listeners;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Exception;

class QueueFailedListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(JobFailed $event): void
    {
        // Log detallado del fallo
        Log::channel('queue')->error('Job failed', [
            'connectionName' => $event->connectionName,
            'job' => $event->job->getName(),
            'jobId' => $event->job->getJobId(),
            'exception' => $event->exception->getMessage(),
            'trace' => $event->exception->getTraceAsString(),
            'failedAt' => now()->toISOString(),
            'data' => $event->data,
        ]);

        // Log también en el canal principal por seguridad
        Log::error('Laravel Queue Job Failed', [
            'job' => $event->job->getName(),
            'connection' => $event->connectionName,
            'exception' => $event->exception->getMessage(),
        ]);

        // Enviar alerta por email si está configurado
        if (config('queue.alert_on_failure', false)) {
            $this->sendFailureAlert($event);
        }

        // Enviar notificación a sistemas internos si está configurado
        if (config('queue.internal_notification', false)) {
            $this->sendInternalNotification($event);
        }
    }

    /**
     * Send email alert for job failure
     */
    protected function sendFailureAlert(JobFailed $event): void
    {
        try {
            $emailTo = config('queue.alert_email');
            
            if (!$emailTo) {
                Log::warning('Queue failure alert email not configured');
                return;
            }

            $subject = '🚨 Alert: Laravel Queue Job Failed - API Proveedores';
            
            $body = $this->buildEmailBody($event);

            Mail::raw($body, function ($message) use ($emailTo, $subject) {
                $message->to($emailTo)
                       ->subject($subject)
                       ->priority(1); // High priority
            });

            Log::info('Job failure alert sent successfully', [
                'job' => $event->job->getName(),
                'email' => $emailTo
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send job failure alert email', [
                'error' => $e->getMessage(),
                'job' => $event->job->getName()
            ]);
        }
    }

    /**
     * Build email body for failure alert
     */
    protected function buildEmailBody(JobFailed $event): string
    {
        $jobName = $event->job->getName();
        $connection = $event->connectionName;
        $exception = $event->exception->getMessage();
        $failedAt = now()->format('Y-m-d H:i:s');
        
        return "
🚨 ALERTA: Job de Laravel Falló
=====================================

📋 DETALLES DEL JOB:
- Nombre: {$jobName}
- Conexión: {$connection}
- Fecha/Hora: {$failedAt}

❌ ERROR:
{$exception}

🔧 ACCIONES RECOMENDADAS:
1. Revisar los logs detallados en: storage/logs/queue/
2. Verificar el estado del Queue Worker
3. Ejecutar: php artisan queue:monitor
4. Reiniciar worker si es necesario: scripts/restart-queue-worker.bat

📊 MONITOREO:
- Queue Worker Supervisor: C:\\repositorio\\app\\api-proveedores\\storage\\logs\\queue-supervisor.log
- Laravel Queue Logs: C:\\repositorio\\app\\api-proveedores\\storage\\logs\\queue\\

🏥 SERVIDOR:
- IP/Host: " . ($_SERVER['SERVER_ADDR'] ?? 'N/A') . "
- Proyecto: API Proveedores
- Ambiente: " . config('app.env') . "

=====================================
Este es un mensaje automático del sistema de monitoreo de colas.
";
    }

    /**
     * Send internal notification (webhook, Slack, etc.)
     */
    protected function sendInternalNotification(JobFailed $event): void
    {
        try {
            $webhookUrl = config('queue.webhook_url');
            
            if (!$webhookUrl) {
                return;
            }

            $payload = [
                'type' => 'job_failed',
                'job' => $event->job->getName(),
                'connection' => $event->connectionName,
                'error' => $event->exception->getMessage(),
                'failed_at' => now()->toISOString(),
                'server' => $_SERVER['SERVER_NAME'] ?? 'Unknown',
                'project' => 'API Proveedores'
            ];

            // Aquí podrías usar Guzzle HTTP client para enviar a webhook
            // Http::post($webhookUrl, $payload);

            Log::info('Internal notification would be sent', [
                'webhook' => $webhookUrl,
                'payload' => $payload
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send internal notification', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
