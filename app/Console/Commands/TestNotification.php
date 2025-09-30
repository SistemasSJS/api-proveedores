<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\PushNotification;
use Illuminate\Console\Command;

class TestNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:test {userId=13} {--message=} {--type=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enviar una notificación de prueba a un usuario';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('userId');
        $message = $this->option('message') ?? 'Esta es una notificación de prueba desde Laravel 🚀';
        $type = $this->option('type') ?? 'info';

        $user = User::find($userId);

        if (!$user) {
            $this->error("Usuario con ID {$userId} no encontrado.");
            return 1;
        }

        $this->info("Enviando notificación al usuario: {$user->name} (ID: {$user->id})");

        try {
            // Enviar notificación con datos de ejemplo
            $user->notify(new PushNotification(
                'Notificación de Prueba',
                $message,
                $type,
                [
                    'deepLink' => [
                        'type' => 'general',
                        'action' => 'view',
                        'data' => [
                            'timestamp' => now()->toIsoString(),
                            'source' => 'test_command'
                        ]
                    ]
                ]
            ));

            $this->info('✅ Notificación enviada exitosamente!');
            $this->info("Tipo: {$type}");
            $this->info("Mensaje: {$message}");
            $this->info('La notificación debería aparecer en la aplicación Angular/Ionic.');
            
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Error al enviar la notificación: ' . $e->getMessage());
            return 1;
        }
    }
}
