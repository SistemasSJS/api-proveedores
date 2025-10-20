<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class PollingService
{
    /**
     * Obtener notificaciones nuevas desde timestamp
     */
    public function getNewNotifications($lastTimestamp = null)
    {
        $user = Auth::user();
        $cacheKey = "user_notifications_hash_{$user->id}";
        
        // Query optimizada
        $query = $user->notifications()->latest();
        
        if ($lastTimestamp) {
            $query->where('created_at', '>', $lastTimestamp);
        }
        
        $notifications = $query->limit(20)->get();
        
        // Hash para detectar cambios rápidamente
        $currentHash = md5($notifications->pluck(['id', 'read_at'])->toJson());
        $previousHash = Cache::get($cacheKey);
        
        Cache::put($cacheKey, $currentHash, 300); // 5 minutos
        
        return [
            'notifications' => $notifications,
            'has_changes' => $currentHash !== $previousHash,
            'timestamp' => now()->toIsoString(),
            'unread_count' => $user->unreadNotifications->count()
        ];
    }
    
    /**
     * Polling adaptativo - ajusta frecuencia según actividad
     */
    public function getPollingInterval($userActivity = 'normal')
    {
        return match ($userActivity) {
            'high' => 10,      // 10 segundos si hay alta actividad
            'normal' => 30,    // 30 segundos normal
            'low' => 60,       // 1 minuto si poca actividad
            'inactive' => 300  // 5 minutos si inactivo
        };
    }
    
    /**
     * Estadísticas para optimizar polling
     */
    public function getUserActivity($userId)
    {
        $recentActivity = Cache::get("user_activity_{$userId}", []);
        
        if (empty($recentActivity)) {
            return 'normal';
        }
        
        $actionsLastHour = collect($recentActivity)
            ->where('timestamp', '>', now()->subHour())
            ->count();
        
        return match (true) {
            $actionsLastHour > 20 => 'high',
            $actionsLastHour > 5 => 'normal',
            $actionsLastHour > 0 => 'low',
            default => 'inactive'
        };
    }
}