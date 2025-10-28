<?php

namespace App\Console\Commands;

use App\Channels\FcmChannel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class VerifyNotificationsSetup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:verify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verificar configuración del sistema de notificaciones multicanal';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Verificando configuración de notificaciones...');
        $this->newLine();

        $allPassed = true;

        // 1. Verificar canales registrados
        $allPassed &= $this->verifyChannels();
        $this->newLine();

        // 2. Verificar configuración FCM
        $allPassed &= $this->verifyFcm();
        $this->newLine();

        // 3. Verificar configuración Reverb
        $allPassed &= $this->verifyReverb();
        $this->newLine();

        // 5. Verificar clases de canales
        $allPassed &= $this->verifyChannelClasses();
        $this->newLine();

        // Resumen final
        if ($allPassed) {
            $this->info('✅ Todas las verificaciones pasaron correctamente!');
            return Command::SUCCESS;
        } else {
            $this->error('❌ Algunas verificaciones fallaron. Revisa los errores arriba.');
            return Command::FAILURE;
        }
    }

    private function verifyChannels(): bool
    {
        $this->info('📡 Verificando canales registrados...');
        
        $channels = ['fcm'];
        $allPassed = true;

        foreach ($channels as $channel) {
            try {
                $driver = Notification::driver($channel);
                $this->line("  ✅ Canal '{$channel}' registrado correctamente");
            } catch (\Exception $e) {
                $this->error("  ❌ Canal '{$channel}' NO está registrado");
                $this->error("     Error: {$e->getMessage()}");
                $allPassed = false;
            }
        }

        return $allPassed;
    }

    private function verifyFcm(): bool
    {
        $this->info('🔥 Verificando configuración FCM...');
        
        $allPassed = true;

        // Verificar Project ID
        $projectId = config('services.fcm.project_id');
        if ($projectId) {
            $this->line("  ✅ FCM Project ID configurado: {$projectId}");
        } else {
            $this->error("  ❌ FCM Project ID no configurado");
            $this->warn("     Agrega FCM_PROJECT_ID al .env");
            $allPassed = false;
        }

        // Verificar archivo de credenciales
        $credentialsPath = config('services.fcm.credentials');
        $fullPath = storage_path($credentialsPath);
        
        if (file_exists($fullPath)) {
            $this->line("  ✅ Archivo de credenciales existe: {$credentialsPath}");
            
            // Verificar que sea JSON válido
            $content = file_get_contents($fullPath);
            $json = json_decode($content, true);
            
            if ($json && isset($json['project_id'], $json['private_key'], $json['client_email'])) {
                $this->line("  ✅ Credenciales Firebase válidas");
            } else {
                $this->error("  ❌ Archivo de credenciales inválido");
                $allPassed = false;
            }
        } else {
            $this->error("  ❌ Archivo de credenciales no existe: {$fullPath}");
            $this->warn("     Coloca tu service-account.json en storage/app/firebase/");
            $allPassed = false;
        }

        return $allPassed;
    }

    private function verifyReverb(): bool
    {
        $this->info('🌐 Verificando configuración Reverb...');
        
        $allPassed = true;

        $appKey = config('reverb.apps.apps.0.key') ?? config('broadcasting.connections.reverb.key');
        $host = config('reverb.apps.apps.0.host') ?? config('broadcasting.connections.reverb.host');
        $port = config('reverb.apps.apps.0.port') ?? config('broadcasting.connections.reverb.port');

        if ($appKey) {
            $this->line("  ✅ Reverb App Key configurado");
        } else {
            $this->error("  ❌ Reverb App Key no configurado");
            $allPassed = false;
        }

        if ($host) {
            $this->line("  ✅ Reverb Host configurado: {$host}");
        } else {
            $this->error("  ❌ Reverb Host no configurado");
            $allPassed = false;
        }

        if ($port) {
            $this->line("  ✅ Reverb Port configurado: {$port}");
        } else {
            $this->error("  ❌ Reverb Port no configurado");
            $allPassed = false;
        }

        return $allPassed;
    }

    private function verifyChannelClasses(): bool
    {
        $this->info('🔧 Verificando clases de canales...');
        
        $allPassed = true;

        // Verificar FcmChannel
        if (class_exists(FcmChannel::class)) {
            $this->line("  ✅ Clase FcmChannel existe");
            
            if (method_exists(FcmChannel::class, 'send')) {
                $this->line("  ✅ Método send() implementado en FcmChannel");
            } else {
                $this->error("  ❌ Método send() no existe en FcmChannel");
                $allPassed = false;
            }
        } else {
            $this->error("  ❌ Clase FcmChannel no existe");
            $allPassed = false;
        }

        return $allPassed;
    }
}
