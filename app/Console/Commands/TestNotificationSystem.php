<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\TipoNotificacion;
use App\Notifications\RecordatorioTarea;
use App\Notifications\CotizacionCreada;
use App\Models\Cotizacion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class TestNotificationSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:test 
                            {--user=1 : ID del usuario al que enviar las notificaciones}
                            {--type=all : Tipo de notificación a probar (all, recordatorio, cotizacion)}
                            {--count=5 : Cantidad de notificaciones de prueba a crear}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prueba el sistema de notificaciones agrupadas por tipos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->option('user');
        $type = $this->option('type');
        $count = (int) $this->option('count');

        $this->info("Iniciando prueba del sistema de notificaciones...");

        // Verificar que el usuario existe
        $user = User::find($userId);
        if (!$user) {
            $this->error("Usuario con ID {$userId} no encontrado.");
            return 1;
        }

        $this->info("Enviando notificaciones a: {$user->name} ({$user->email})");

        // Verificar que los tipos de notificación existen
        $this->checkNotificationTypes();

        // Enviar notificaciones según el tipo solicitado
        match($type) {
            'recordatorio' => $this->sendRecordatorioNotifications($user, $count),
            'cotizacion' => $this->sendCotizacionNotifications($user, $count),
            'all' => $this->sendAllTypeNotifications($user, $count),
            default => $this->error("Tipo de notificación '{$type}' no válido. Usa: all, recordatorio, cotizacion")
        };

        $this->newLine();
        $this->info("✅ Prueba completada exitosamente!");
        $this->info("Puedes revisar las notificaciones en:");
        $this->info("- API: GET /api/notifications/grouped");
        $this->info("- API: GET /api/notifications/resumen");

        return 0;
    }

    /**
     * Verificar que los tipos de notificación existen
     */
    private function checkNotificationTypes(): void
    {
        $tipos = ['RECORDATORIO_TAREA', 'COTIZACION_CREADA'];
        $faltantes = [];

        foreach ($tipos as $codigo) {
            $tipo = TipoNotificacion::where('codigo', $codigo)->first();
            if (!$tipo) {
                $faltantes[] = $codigo;
            }
        }

        if (!empty($faltantes)) {
            $this->warn("Tipos de notificación faltantes: " . implode(', ', $faltantes));
            $this->info("Ejecuta: php artisan db:seed --class=TiposNotificacionSeeder");
        }
    }

    /**
     * Enviar notificaciones de recordatorio
     */
    private function sendRecordatorioNotifications(User $user, int $count): void
    {
        $this->info("Enviando {$count} notificaciones de recordatorio...");

        $tareas = [
            ['nombre' => 'Revisar documentos pendientes', 'prioridad' => 'alta'],
            ['nombre' => 'Actualizar inventario', 'prioridad' => 'media'],
            ['nombre' => 'Contactar proveedores', 'prioridad' => 'baja'],
            ['nombre' => 'Preparar reporte mensual', 'prioridad' => 'alta'],
            ['nombre' => 'Verificar pagos pendientes', 'prioridad' => 'media'],
        ];

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        for ($i = 0; $i < $count; $i++) {
            $tarea = $tareas[$i % count($tareas)];
            $fechaVencimiento = now()->addDays(rand(-2, 7))->format('Y-m-d H:i:s');
            
            $notification = new RecordatorioTarea(
                $tarea['nombre'] . " #" . ($i + 1),
                $fechaVencimiento,
                $tarea['prioridad'],
                "Descripción detallada de la tarea #" . ($i + 1),
                $i + 1
            );

            $user->notify($notification);
            $bar->advance();
            
            // Pequeña pausa para evitar sobrecarga
            usleep(100000); // 0.1 segundos
        }

        $bar->finish();
        $this->newLine();
    }

    /**
     * Enviar notificaciones de cotización
     */
    private function sendCotizacionNotifications(User $user, int $count): void
    {
        $this->info("Enviando {$count} notificaciones de cotización...");

        // Para las notificaciones de cotización, necesitamos crear cotizaciones de prueba o simular
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        for ($i = 0; $i < $count; $i++) {
            // Crear una cotización simulada (en un caso real, esto vendría de un evento)
            $fechaCotizacion = now()->subDays(rand(0, 5));
            $fechaVencimiento = now()->addDays(rand(15, 30));
            $detalles = collect(range(1, rand(3, 8)));
            
            // Crear objeto simulado simple
            $cotizacion = (object) [
                'id' => $i + 1000,
                'fecha_cotizacion' => $fechaCotizacion,
                'fecha_vencimiento' => $fechaVencimiento,
                'total' => rand(1000, 50000),
                'proveedor_id' => 1,
                'detalles' => $detalles,
            ];

            // Simular usuario solicitante
            $solicitante = (object) [
                'id' => 1,
                'name' => 'Sistema de Construcción',
                'email' => 'construccion@empresa.com',
            ];

            $notification = new CotizacionCreada($cotizacion, $solicitante, 'construccion');

            $user->notify($notification);
            $bar->advance();
            
            usleep(100000); // 0.1 segundos
        }

        $bar->finish();
        $this->newLine();
    }

    /**
     * Enviar notificaciones de todos los tipos
     */
    private function sendAllTypeNotifications(User $user, int $count): void
    {
        $this->info("Enviando {$count} notificaciones mixtas...");

        $recordatorioCount = (int) ceil($count / 2);
        $cotizacionCount = $count - $recordatorioCount;

        $this->sendRecordatorioNotifications($user, $recordatorioCount);
        $this->sendCotizacionNotifications($user, $cotizacionCount);
    }
}
