<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class GenerateApiToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'construcc:generate-token 
                            {--user-id= : ID del usuario (opcional)}
                            {--email= : Email del usuario (opcional)}
                            {--token-name=construcc-api : Nombre del token}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera un token de API permanente para el módulo de construcción';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔑 Generador de Tokens API - Módulo Construcción');
        $this->line('');

        // Obtener usuario
        $user = $this->getUser();
        if (! $user) {
            return 1;
        }

        // Mostrar información del usuario
        $this->info("👤 Usuario: {$user->name} ({$user->email})");
        $roleName = $user->role ? $user->role->name : 'Sin rol';
        $this->info("🏢 Rol: {$roleName}");

        // Verificar si tiene proveedores
        $proveedoresCount = $user->proveedoresActivos()->count();
        if ($proveedoresCount > 0) {
            $this->info("🏭 Proveedores activos: {$proveedoresCount}");
        }

        $this->line('');

        // Generar token
        $tokenName = $this->option('token-name');

        // Crear token SIN EXPIRACIÓN
        $token = $user->createToken($tokenName, [
            'construcc:read',
            'construcc:write',
            'construcc:admin',
        ]);

        // Mostrar resultado
        $this->line('✅ Token generado exitosamente:');
        $this->line('');
        $this->info('🔐 TOKEN (cópialo y guárdalo):');
        $this->line($token->plainTextToken);
        $this->line('');

        // Información adicional
        $this->comment('📋 Información del Token:');
        $this->line("• ID: {$token->accessToken->id}");
        $this->line("• Nombre: {$tokenName}");
        $this->line("• Usuario: {$user->name}");
        $this->line('• Expira: NUNCA (permanente)');
        $this->line('• Habilidades: construcc:read, construcc:write, construcc:admin');
        $this->line('');

        // Instrucciones de uso
        $this->comment('🚀 Uso en Angular:');
        $this->line("localStorage.setItem('api_token', '{$token->plainTextToken}');");
        $this->line('');

        $this->comment('🌐 Header HTTP:');
        $this->line("Authorization: Bearer {$token->plainTextToken}");
        $this->line('');

        $this->success('¡Token listo para usar en el módulo de construcción!');

        return 0;
    }

    private function getUser()
    {
        // Si se proporciona ID de usuario
        if ($userId = $this->option('user-id')) {
            $user = User::find($userId);
            if (! $user) {
                $this->error("❌ Usuario con ID {$userId} no encontrado.");

                return null;
            }

            return $user;
        }

        // Si se proporciona email
        if ($email = $this->option('email')) {
            $user = User::where('email', $email)->first();
            if (! $user) {
                $this->error("❌ Usuario con email {$email} no encontrado.");

                return null;
            }

            return $user;
        }

        // Mostrar lista de usuarios disponibles
        $users = User::with('role')->get(['id', 'name', 'email', 'role_id']);

        if ($users->isEmpty()) {
            $this->error('❌ No hay usuarios en la base de datos.');

            return null;
        }

        $this->comment('👥 Usuarios disponibles:');
        foreach ($users as $user) {
            $roleName = $user->role ? $user->role->name : 'Sin rol';
            $this->line("  [{$user->id}] {$user->name} ({$user->email}) - {$roleName}");
        }

        $userId = $this->ask('🔍 Ingresa el ID del usuario para generar el token');

        $user = User::find($userId);
        if (! $user) {
            $this->error("❌ Usuario con ID {$userId} no encontrado.");

            return null;
        }

        return $user;
    }
}
