<?php

namespace App\Console\Commands;

use App\Events\TestEvent;
use Illuminate\Console\Command;

class TestReverb extends Command
{
    protected $signature = 'test:reverb';
    protected $description = 'Enviar evento de prueba a Reverb';

    public function handle()
    {
        $mensaje = 'Prueba desde comando: ' . now();
        
        $this->info('Enviando evento TestEvent...');
        broadcast(new TestEvent($mensaje));
        $this->info('Evento enviado: ' . $mensaje);
        
        return 0;
    }
}
