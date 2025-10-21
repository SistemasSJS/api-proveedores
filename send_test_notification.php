<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔔 Enviando notificación de prueba...\n";

try {
    $user = App\Models\User::find(14);
    
    if (!$user) {
        echo "❌ No hay usuarios en la base de datos\n";
        exit(1);
    }
    
    echo "👤 Usuario: {$user->name} ({$user->email})\n";
    
    // Enviar notificación
    $user->notify(new App\Notifications\PushNotification(
        '🧪 Notificación de Prueba ' . date('H:i:s'),
        'Esta es una notificación de prueba enviada a las ' . date('H:i:s') . '. Deberías verla aparecer en tu frontend en los próximos 10 segundos.',
        'success',
        [
            'test' => true,
            'timestamp' => now()->toIsoString(),
            'user_id' => $user->id,
            'sent_at' => date('Y-m-d H:i:s')
        ]
    ));
    
    echo "✅ Notificación enviada exitosamente\n";
    echo "🎯 Revisa el frontend - debería aparecer en máximo 10 segundos\n";
    echo "⏱️  Tiempo actual: " . date('H:i:s') . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}