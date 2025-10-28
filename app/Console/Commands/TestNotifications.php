<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\PushNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:test 
                            {user_id? : ID del usuario a notificar (opcional)} 
                            {--channels= : Canales específicos separados por comas (broadcast,fcm,database)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar sistema de notificaciones multicanal (Reverb, FCM)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Iniciando prueba de notificaciones multicanal...');
        $this->newLine();

        // Obtener usuario
        $userId = $this->argument('user_id');
        $user = $userId ? User::find($userId) : User::first();

        if (!$user) {
            $this->error('❌ No se encontró ningún usuario.');
            return Command::FAILURE;
        }

        $this->info("👤 Usuario: {$user->name} (ID: {$user->id})");
        $this->info("📧 Email: {$user->email}");
        
        if ($user->telefono) {
            $this->info("📞 Teléfono: {$user->telefono}");
        }

        // Verificar tokens FCM
        if (method_exists($user, 'activeDeviceTokens')) {
            $tokensCount = $user->activeDeviceTokens()->count();
            $this->info("📱 Tokens FCM activos: {$tokensCount}");
        }

        $this->newLine();

        // Crear notificación de prueba
        $notification = new PushNotification(
            title: '🔔 Notificación de Prueba',
            message: 'Este es un mensaje de prueba del sistema de notificaciones multicanal. ' . 
                     'Si recibes esto, significa que el sistema está funcionando correctamente.',
            type: 'test',
            data: [
                'test_id' => uniqid('test_'),
                'timestamp' => now()->toIso8601String(),
                'environment' => app()->environment(),
            ]
        );

        // Determinar qué canales se usarán
        $channels = $notification->via($user);
        
        $this->info('📡 Canales que se utilizarán:');
        foreach ($channels as $channel) {
            $channelName = is_string($channel) ? $channel : class_basename($channel);
            $this->line("  • {$channelName}");
        }

        $this->newLine();

        // Confirmar envío
        if (!$this->confirm('¿Deseas enviar la notificación de prueba?', true)) {
            $this->warn('⚠️ Operación cancelada.');
            return Command::SUCCESS;
        }

        $this->newLine();
        $this->info('📤 Enviando notificación...');

        try {
            // Enviar notificación
            $user->notify($notification);

            $this->newLine();
            $this->info('✅ Notificación enviada exitosamente!');
            $this->newLine();

            // Mostrar instrucciones
            $this->info('📋 Instrucciones de verificación:');
            $this->line('  1. Broadcast (Reverb): Verifica la consola del navegador web');
            $this->line('  2. FCM: Revisa tu dispositivo móvil nativo (Android/iOS)');
            $this->line('  3. Database: Revisa la tabla notifications');
            $this->newLine();

            // Mostrar logs
            $this->info('📊 Revisa los logs para más detalles:');
            $this->line('  tail -f storage/logs/laravel.log');
            
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error al enviar notificación:');
            $this->error($e->getMessage());
            
            Log::error('Test Notifications Command Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}
