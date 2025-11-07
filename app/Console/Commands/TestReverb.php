<?php

namespace App\Console\Commands;

use App\Events\TestEvent;
use App\Models\User;
use App\Notifications\PushNotification;
use Illuminate\Console\Command;

class TestReverb extends Command
{
    protected $signature = 'test:reverb';
    protected $description = 'Enviar evento de prueba a Reverb';

    public function handle()
    {
        $this->info('🚀 Probando Reverb con PushNotification...');
        $this->newLine();

        $user = User::find(14);

        if (!$user) {
            $this->error('❌ Usuario con ID 14 no encontrado.');
            return Command::FAILURE;
        }

        $this->info("👤 Usuario: {$user->name} (ID: {$user->id})");
        $this->newLine();

        $user->notify(
            new PushNotification(
                title: '🔔 Notificación de Prueba Reverb',
                message: 'Prueba desde comando: ' . now()->format('Y-m-d H:i:s'),
                type: 'info',
                data: [
                    'mensaje' => 'Hola desde TestReverb',
                    'timestamp' => now()->toIso8601String(),
                ],
                actionUrl: null,
                userId: $user->id
            )
        );

        $this->info('✅ Notificación enviada correctamente');
        $this->line('  - Canal privado: App.Models.User.' . $user->id);
        $this->line('  - Revisa la consola del navegador o app móvil');

        return Command::SUCCESS;
    }
}
