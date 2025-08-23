<?php

/**
 * TEST COMPLETO DEL SISTEMA DE NOTIFICACIONES
 * 
 * Este script permite probar todo el flujo de notificaciones:
 * 1. Registro de device tokens
 * 2. Envío de emails
 * 3. Notificaciones push FCM
 * 4. Broadcasting (si está habilitado)
 * 
 * INSTRUCCIONES DE USO:
 * 
 * Desde terminal:
 * php artisan tinker
 * 
 * Luego ejecutar:
 * include 'tests/NotificationSystemTest.php';
 * $test = new NotificationSystemTest();
 * $test->runCompleteTest();
 */

use App\Models\User;
use App\Models\UserDeviceToken;
use App\Models\Cotizacion;
use App\Notifications\CotizacionCreada;
use App\Services\FcmService;
use Illuminate\Support\Facades\Log;

class NotificationSystemTest
{
    /**
     * Ejecutar test completo del sistema
     */
    public function runCompleteTest()
    {
        echo "\n🚀 INICIANDO TEST COMPLETO DEL SISTEMA DE NOTIFICACIONES\n";
        echo "=" . str_repeat("=", 60) . "\n\n";
        
        try {
            // 1. Verificar configuración
            $this->testConfiguration();
            
            // 2. Crear usuario de prueba
            $user = $this->createTestUser();
            
            // 3. Registrar device token
            $this->testDeviceTokenRegistration($user);
            
            // 4. Test del servicio FCM
            $this->testFcmService();
            
            // 5. Crear cotización dummy y enviar notificación completa
            $this->testFullNotificationFlow($user);
            
            echo "\n✅ TODOS LOS TESTS COMPLETADOS EXITOSAMENTE\n";
            echo "Revisa los logs para ver detalles del envío\n\n";
            
        } catch (Exception $e) {
            echo "\n❌ ERROR EN EL TEST: " . $e->getMessage() . "\n";
            echo "Trace: " . $e->getTraceAsString() . "\n\n";
        }
    }
    
    /**
     * Test 1: Verificar configuración
     */
    private function testConfiguration()
    {
        echo "📋 TEST 1: Verificando configuración...\n";
        
        // Verificar variables de entorno
        $projectId = config('services.fcm.project_id');
        $credentials = config('services.fcm.credentials');
        $credentialsPath = storage_path($credentials);
        
        if (empty($projectId)) {
            throw new Exception('FCM_PROJECT_ID no configurado en .env');
        }
        
        if (!file_exists($credentialsPath)) {
            throw new Exception('Archivo de credenciales Firebase no encontrado: ' . $credentialsPath);
        }
        
        echo "   ✅ FCM Project ID: {$projectId}\n";
        echo "   ✅ Credenciales Firebase: OK\n";
        echo "   ✅ Configuración de mail: " . config('mail.default') . "\n";
        echo "   ✅ Configuración de broadcasting: " . config('broadcasting.default') . "\n\n";
    }
    
    /**
     * Test 2: Crear usuario de prueba
     */
    private function createTestUser(): User
    {
        echo "👤 TEST 2: Creando usuario de prueba...\n";
        
        $user = User::firstOrCreate(
            ['email' => 'test-notifications@construcc.com'],
            [
                'name' => 'Usuario Test Notificaciones',
                'password' => bcrypt('password123'),
                'role_id' => 1 // Asume que existe un rol con ID 1
            ]
        );
        
        echo "   ✅ Usuario creado/encontrado: {$user->name} (ID: {$user->id})\n\n";
        
        return $user;
    }
    
    /**
     * Test 3: Registrar device token
     */
    private function testDeviceTokenRegistration(User $user)
    {
        echo "📱 TEST 3: Registrando device token de prueba...\n";
        
        $testToken = 'test_fcm_token_' . time() . '_' . rand(1000, 9999);
        
        $deviceToken = UserDeviceToken::create([
            'user_id' => $user->id,
            'token' => $testToken,
            'platform' => 'android',
            'device_id' => 'test_device_' . rand(1000, 9999),
            'device_name' => 'Test Android Device',
            'metadata' => [
                'app_version' => '1.0.0',
                'os_version' => 'Android 12',
                'test_mode' => true
            ],
            'last_used_at' => now(),
            'is_active' => true
        ]);
        
        echo "   ✅ Device token registrado: " . substr($testToken, 0, 20) . "...\n";
        echo "   ✅ Platform: {$deviceToken->platform}\n";
        echo "   ✅ Device ID: {$deviceToken->device_id}\n\n";
        
        return $deviceToken;
    }
    
    /**
     * Test 4: Probar servicio FCM
     */
    private function testFcmService()
    {
        echo "🔥 TEST 4: Probando servicio FCM...\n";
        
        try {
            $fcmService = new FcmService();
            
            $notification = [
                'title' => '🧪 Test de Notificación',
                'body' => 'Esta es una notificación de prueba del sistema'
            ];
            
            $data = [
                'type' => 'test',
                'timestamp' => now()->toISOString(),
                'test_id' => 'test_' . time()
            ];
            
            echo "   ✅ Servicio FCM inicializado correctamente\n";
            echo "   ✅ Payload de prueba creado\n";
            echo "   ⚠️ Nota: No se enviará notificación real (token de prueba)\n\n";
            
        } catch (Exception $e) {
            throw new Exception('Error inicializando servicio FCM: ' . $e->getMessage());
        }
    }
    
    /**
     * Test 5: Flujo completo de notificación
     */
    private function testFullNotificationFlow(User $user)
    {
        echo "📬 TEST 5: Probando flujo completo de notificación...\n";
        
        // Crear cotización dummy
        $cotizacion = $this->createDummyCotization();
        $solicitante = $user; // Usar el mismo usuario como solicitante
        
        echo "   ✅ Cotización dummy creada (ID: {$cotizacion->id})\n";
        
        // Enviar notificación completa
        try {
            $user->notify(new CotizacionCreada($cotizacion, $solicitante, 'construccion'));
            echo "   ✅ Notificación enviada a la cola\n";
            
            // Verificar que se creó la notificación en base de datos
            $dbNotification = $user->notifications()->latest()->first();
            if ($dbNotification) {
                echo "   ✅ Notificación guardada en BD (ID: {$dbNotification->id})\n";
            }
            
            echo "   📨 Canales habilitados:\n";
            echo "      - ✅ Email (SMTP)\n";
            echo "      - ✅ Base de datos\n";
            
            if (config('broadcasting.default') !== 'null') {
                echo "      - ✅ Broadcasting\n";
            } else {
                echo "      - ⚠️ Broadcasting (deshabilitado)\n";
            }
            
            if ($user->activeDeviceTokens()->exists()) {
                echo "      - ✅ FCM Push Notifications\n";
            } else {
                echo "      - ⚠️ FCM (sin tokens activos)\n";
            }
            
        } catch (Exception $e) {
            throw new Exception('Error enviando notificación: ' . $e->getMessage());
        }
        
        echo "\n";
    }
    
    /**
     * Crear cotización dummy para pruebas
     */
    private function createDummyCotization(): Cotizacion
    {
        $cotizacion = new Cotizacion();
        $cotizacion->id = 999999; // ID dummy
        $cotizacion->fecha_cotizacion = now();
        $cotizacion->fecha_vencimiento = now()->addDays(15);
        $cotizacion->total = 125000.50;
        $cotizacion->proveedor_id = 1; // Asume que existe un proveedor
        $cotizacion->observaciones = 'Esta es una cotización de prueba para testing del sistema de notificaciones';
        
        // Simular detalles (relación)
        $cotizacion->setRelation('detalles', collect([
            (object)['producto' => 'Producto Test 1', 'cantidad' => 10],
            (object)['producto' => 'Producto Test 2', 'cantidad' => 5]
        ]));
        
        return $cotizacion;
    }
    
    /**
     * Test adicional: Verificar workers de cola
     */
    public function testQueueWorkers()
    {
        echo "⚙️ TEST ADICIONAL: Verificando workers de cola...\n";
        
        $queueConnection = config('queue.default');
        echo "   ✅ Conexión de cola: {$queueConnection}\n";
        
        if ($queueConnection === 'database') {
            $jobsCount = \DB::table('jobs')->count();
            echo "   ✅ Jobs pendientes en cola: {$jobsCount}\n";
        }
        
        echo "   💡 Para procesar la cola ejecuta: php artisan queue:work\n\n";
    }
}

// Si se ejecuta directamente (no desde tinker)
if (php_sapi_name() === 'cli' && isset($argv[0]) && basename($argv[0]) === 'NotificationSystemTest.php') {
    $test = new NotificationSystemTest();
    $test->runCompleteTest();
    $test->testQueueWorkers();
}
