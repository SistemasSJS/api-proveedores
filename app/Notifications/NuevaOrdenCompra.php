<?php

namespace App\Notifications;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Notification;

/**
 * Notificación enviada cuando se crea una nueva orden de compra
 * ENVÍO INSTANTÁNEO - Sin cola de jobs
 */
class NuevaOrdenCompra extends Notification implements ShouldBroadcast
{

    public $ordenCompraId;
    public $proveedorId;
    public $empresaId;
    public $estatus;

    public function __construct(
        string $ordenCompraId,
        int $proveedorId,
        int $empresaId,
        ?string $estatus = null
    ) {
        $this->ordenCompraId = $ordenCompraId;
        $this->proveedorId = $proveedorId;
        $this->empresaId = $empresaId;
        $this->estatus = $estatus ?? 'pendiente';
    }

    /**
     * Canales de notificación - SYNC (sin cola)
     */
    public function via(object $notifiable): array
    {
        return ['broadcast', 'database', 'fcm'];
    }

    /**
     * Representación para broadcast (WebSocket)
     */
    public function toBroadcast(object $notifiable): array
    {
        return [
            'id' => $this->id,
            'tipo' => 'nueva_orden_compra',
            'titulo' => 'Nueva Orden de Compra #' . $this->ordenCompraId,
            'mensaje' => "Tienes una nueva orden de compra: {$this->ordenCompraId}",
            'data' => [
                'orden_compra_id' => $this->ordenCompraId,
                'proveedor_id' => $this->proveedorId,
                'empresa_id' => $this->empresaId,
                'estatus' => $this->estatus,
            ],
            'timestamp' => now()->toIsoString(),
            'read_at' => null,
        ];
    }

    /**
     * Tipo de evento broadcast
     */
    public function broadcastType(): string
    {
        return 'nueva-orden-compra';
    }

    /**
     * Representación para base de datos
     */
    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'nueva_orden_compra',
            'titulo' => 'Nueva Orden de Compra #' . $this->ordenCompraId,
            'mensaje' => "Tienes una nueva orden de compra: {$this->ordenCompraId}",
            'orden_compra_id' => $this->ordenCompraId,
            'proveedor_id' => $this->proveedorId,
            'empresa_id' => $this->empresaId,
            'estatus' => $this->estatus,
            'timestamp' => now()->toIsoString(),
        ];
    }

    /**
     * Configuración para FCM (Push Notification)
     */
    public function toFcm(object $notifiable): array
    {
        return [
            'title' => 'Nueva Orden de Compra #' . $this->ordenCompraId,
            'body' => "Tienes una nueva orden de compra: {$this->ordenCompraId}",
            'data' => [
                'tipo' => 'nueva_orden_compra',
                'orden_compra_id' => $this->ordenCompraId,
                'proveedor_id' => $this->proveedorId,
                'empresa_id' => $this->empresaId,
                'estatus' => $this->estatus,
                'timestamp' => now()->toIsoString(),
            ],
            // Configuración Android - Notificación Audible y Heads-Up
            'android' => [
                'priority' => 'high',
                'notification' => [
                    'sound' => 'default',
                    'priority' => 'high',
                    'visibility' => 'public',  // Visible en pantalla bloqueada
                    'channel_id' => 'app_proveedores_notifications',
                    'vibrate' => [300, 100, 400],  // Patrón de vibración
                ],
            ],
            // Configuración iOS
            'apns' => [
                'payload' => [
                    'aps' => [
                        'sound' => 'default',
                        'badge' => 1,
                        'alert' => [
                            'title' => 'Nueva Orden de Compra #' . $this->ordenCompraId,
                            'body' => "Tienes una nueva orden de compra: {$this->ordenCompraId}",
                        ],
                        'content-available' => 1,
                        'mutable-content' => 1,
                    ],
                ],
            ],
        ];
    }
    
    /**
     * IMPORTANTE: Esta notificación NO usa colas
     * Se envía de forma INSTANTÁNEA y SÍNCRONA
     */
    public function shouldQueue(): bool
    {
        return false;
    }
}
