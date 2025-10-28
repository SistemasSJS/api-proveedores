<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\PushNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DiagnoseNotifications extends Command
{
    protected $signature = 'notifications:diagnose {user_id?}';
    protected $description = 'Diagnosticar sistema completo de notificaciones';

    public function handle()
    {
        $this->info('🔍 DIAGNÓSTICO COMPLETO DE NOTIFICACIONES');
        $this->newLine();

        // 1. Verificar configuración
        $this->checkConfiguration();
        $this->newLine();

        // 2. Verificar modelo User
        $this->checkUserModel();
        $this->newLine();

        // 3. Verificar Reverb
        $this->checkReverbServer();
        $this->newLine();

        // 4. Enviar notificación de prueba
        if ($this->confirm('¿Enviar notificación de prueba?', true)) {
            $this->sendTestNotification();
        }

        return Command::SUCCESS;
    }

    private function checkConfiguration()
    {
        $this->info('📋 CONFIGURACIÓN');

        $broadcast = config('broadcasting.default');
        $this->line("  BROADCAST_CONNECTION: {$broadcast}");

        if ($broadcast !== 'reverb') {
            $this->error('  ❌ BROADCAST_CONNECTION debe ser "reverb"');
        } else {
            $this->line('  ✅ BROADCAST_CONNECTION correcto');
        }

        $reverbKey = config('broadcasting.connections.reverb.key');
        $reverbHost = config('broadcasting.connections.reverb.options.host');
        $reverbPort = config('broadcasting.connections.reverb.options.port');

        $this->line("  Reverb Key: {$reverbKey}");
        $this->line("  Reverb Host: {$reverbHost}");
        $this->line("  Reverb Port: {$reverbPort}");
    }

    private function checkUserModel()
    {
        $this->info('👤 MODELO USER');

        $user = User::first();
        if (!$user) {
            $this->error('  ❌ No hay usuarios en la base de datos');
            return;
        }

        $this->line("  Usuario: {$user->name} (ID: {$user->id})");

        // Verificar trait Notifiable
        $uses = class_uses_recursive(User::class);
        if (in_array('Illuminate\Notifications\Notifiable', $uses)) {
            $this->line('  ✅ Trait Notifiable presente');
        } else {
            $this->error('  ❌ Trait Notifiable NO presente');
        }

        // Verificar método receivesBroadcastNotificationsOn
        if (method_exists($user, 'receivesBroadcastNotificationsOn')) {
            $channel = $user->receivesBroadcastNotificationsOn();
            $this->line("  ✅ receivesBroadcastNotificationsOn(): {$channel}");
        } else {
            $this->line('  ℹ️  receivesBroadcastNotificationsOn() no definido (usando canal por defecto)');
            $this->line("  Canal por defecto: App.Models.User.{$user->id}");
        }

        // Verificar tokens FCM
        if (method_exists($user, 'activeDeviceTokens')) {
            $tokensCount = $user->activeDeviceTokens()->count();
            $this->line("  📱 Tokens FCM activos: {$tokensCount}");
        }
    }

    private function checkReverbServer()
    {
        $this->info('🌐 SERVIDOR REVERB');

        $host = config('broadcasting.connections.reverb.options.host');
        $port = config('broadcasting.connections.reverb.options.port');
        $url = "http://{$host}:{$port}";

        $this->line("  Intentando conectar a: {$url}");

        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode > 0) {
                $this->line("  ✅ Servidor respondió con código: {$httpCode}");
            } else {
                $this->error('  ❌ Servidor NO responde');
                $this->warn('    Ejecuta: php artisan reverb:start');
            }
        } catch (\Exception $e) {
            $this->error("  ❌ Error al conectar: {$e->getMessage()}");
        }
    }

    private function sendTestNotification()
    {
        $this->newLine();
        $this->info('📤 ENVIANDO NOTIFICACIÓN DE PRUEBA');

        $userId = $this->argument('user_id');
        $user = $userId ? User::find($userId) : User::first();

        if (!$user) {
            $this->error('❌ Usuario no encontrado');
            return;
        }

        $this->line("  👤 Usuario: {$user->name} (ID: {$user->id})");
        $this->line("  📡 Canal: private-App.Models.User.{$user->id}");

        try {
            // Crear notificación
            $notification = new PushNotification(
                title: '🔔 Test de Notificaciones',
                message: 'Si ves esto en el frontend, el sistema funciona correctamente!',
                type: 'test',
                data: [
                    'test_id' => uniqid('diag_'),
                    'timestamp' => now()->toIso8601String(),
                ]
            );

            // Obtener canales que se usarán
            $channels = $notification->via($user);
            $this->line('  📡 Canales: ' . implode(', ', $channels));

            // Enviar
            $user->notify($notification);

            $this->newLine();
            $this->info('✅ Notificación enviada!');
            $this->newLine();

            $this->line('🔎 Verifica en el frontend:');
            $this->line('  1. Abre la consola del navegador (F12)');
            $this->line('  2. Busca mensajes de WebSocket/Pusher');
            $this->line('  3. Deberías ver: "📩 Notificación recibida"');
            $this->newLine();

            $this->line('📊 Logs a revisar:');
            $this->line('  tail -f storage/logs/laravel.log | grep -E "(Broadcast|Notification)"');

        } catch (\Exception $e) {
            $this->error('❌ Error al enviar notificación:');
            $this->error($e->getMessage());
            $this->line('');
            $this->line('Stack trace:');
            $this->line($e->getTraceAsString());
        }
    }
}
