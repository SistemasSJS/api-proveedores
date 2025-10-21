<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 Verificando notificaciones en la base de datos...\n\n";

try {
    $user = App\Models\User::first();
    
    if (!$user) {
        echo "❌ No hay usuarios\n";
        exit(1);
    }
    
    echo "👤 Usuario: {$user->name} (ID: {$user->id})\n\n";
    
    // Obtener las últimas 5 notificaciones
    $notifications = $user->notifications()->latest()->limit(5)->get();
    
    echo "📋 Últimas 5 notificaciones:\n";
    echo str_repeat("-", 50) . "\n";
    
    if ($notifications->isEmpty()) {
        echo "❌ No hay notificaciones en la base de datos\n";
    } else {
        foreach ($notifications as $notif) {
            $data = is_string($notif->data) ? json_decode($notif->data, true) : $notif->data;
            echo sprintf(
                "[%s] %s\n   %s\n   ID: %s | Leída: %s\n\n",
                $notif->created_at->format('Y-m-d H:i:s'),
                $data['title'] ?? 'Sin título',
                $data['mensaje'] ?? 'Sin mensaje',
                $notif->id,
                $notif->read_at ? 'Sí' : 'No'
            );
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}