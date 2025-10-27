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
        $mensaje = 'Prueba desde comando: ' . now();

        $user = User::find(14);

        $user->notify(
            new PushNotification(

                'Title: Notificaion Push',
                'Mensaje: Notificaion Push',
                'info',
                [
                    'mensaje' => 'saludio'
                ]
            )
        );
        // $this->info('Enviando evento TestEvent...');
        // broadcast(new TestEvent($mensaje));
        // $this->info('Evento enviado: ' . $mensaje);

        return 0;
    }
}
