<?php

namespace App\Notifications;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Notification;

/**
 * Notificación enviada cuando se crea una nueva orden de compra
 * ENVÍO INSTANTÁNEO - Sin cola de jobs
 */
class NuevaOrdenCompra extends Notification implements ShouldBroadcastNow
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
     * Canales de envío basados en contexto del usuario
     * - broadcast: Reverb WebSocket (para web)
     * - fcm: Push nativas (Android/iOS)
     * - database: Historial de notificaciones
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        // 1. SIEMPRE: Guardar en base de datos para historial
        $channels[] = 'database';

        // 2. SIEMPRE: Broadcast via Reverb (para usuarios web conectados)
        $channels[] = 'broadcast';

        // 3. CONDICIONAL: FCM para usuarios con tokens activos (nativos)
        if (
            method_exists($notifiable, 'activeDeviceTokens') &&
            $notifiable->activeDeviceTokens()->exists()
        ) {
            $channels[] = 'fcm';
        }

        return $channels;
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
     * IMPORTANTE: Esta notificación NO usa colas
     * Se envía de forma INSTANTÁNEA y SÍNCRONA
     */
    public function shouldQueue(): bool
    {
        return false;
    }
}
