<?php

namespace App\Notifications\SolicitudPago;

use App\Services\FcmService;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Notifications\Notification;

class SolicitudPagoPagada extends Notification implements ShouldBroadcastNow
{
    public $solicitudPagoId;
    public $solicitudPagoFolio;
    public $proveedorId;
    public $monto;
    public $userId;

    public function __construct(string $solicitudPagoFolio, int $solicitudPagoId, int $proveedorId, ?float $monto = null, ?int $userId = null)
    {
        $this->solicitudPagoFolio = $solicitudPagoFolio;
        $this->solicitudPagoId = $solicitudPagoId;
        $this->proveedorId = $proveedorId;
        $this->monto = $monto;
        $this->userId = $userId;
    }

    /**
     * Canales de notificación
     */
    public function via(object $notifiable): array
    {
        $via = ['database', 'mail'];

        if (method_exists($notifiable, 'deviceTokens') && $notifiable->deviceTokens()->where('is_active', true)->exists()) {
            $via[] = 'fcm';
        }

        return $via;
    }

    /**
     * Canal Broadcast (WebSocket) — PRIVADO POR USUARIO
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'tipo' => 'solicitud_pago_pagada',
            'titulo' => 'Solicitud de Pago Pagada #' . $this->solicitudPagoFolio,
            'mensaje' => "Tu solicitud de pago #{$this->solicitudPagoFolio} ha sido pagada.",
            'action_url' => '/pages/proveedor/sp/detalle/' . $this->solicitudPagoId,
            'data' => [
                'solicitud_pago_folio' => $this->solicitudPagoFolio,
                'proveedor_id' => $this->proveedorId,
                // 'empresa_id' => $this->empresaId,
                'monto' => $this->monto,
                'estatus' => 'pagada',
            ],
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function broadcastType(): string
    {
        return 'solicitud-pago-pagada';
    }

    /**
     * Canal privado por usuario
     */
    public function broadcastOn(): array
    {
        // Usa el userId si está disponible, sino usa proveedorId como fallback
        $channelId = $this->userId ?? $this->proveedorId;
        return [new PrivateChannel('App.Models.User.' . $channelId)];
    }

    /**
     * Canal Database
     */
    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'solicitud_pago_pagada',
            'titulo' => 'Solicitud de Pago Pagada #' . $this->solicitudPagoFolio,
            'mensaje' => "Tu solicitud de pago #{$this->solicitudPagoFolio} ha sido pagada.",
            'action_url' => '/pages/proveedor/sp/detalle/' . $this->solicitudPagoId,
            'solicitud_pago_folio' => $this->solicitudPagoFolio,
            'proveedor_id' => $this->proveedorId,
            // 'empresa_id' => $this->empresaId,
            'monto' => $this->monto,
            'estatus' => 'pagada',
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Canal Mail
     */
    public function toMail(object $notifiable): MailMessage
    {
        $frontendUrl = config('app.frontend_url', config('app.url'));
        $montoFormateado = $this->monto ? '$' . number_format($this->monto, 2) : '';

        return (new MailMessage)
            ->subject('Solicitud de Pago Pagada #' . $this->solicitudPagoFolio)
            ->greeting('Hola ' . $notifiable->name . ',')
            ->line('Tu solicitud de pago ha sido pagada exitosamente.')
            ->line('Número de solicitud: ' . $this->solicitudPagoFolio)
            ->line($montoFormateado ? 'Monto: ' . $montoFormateado : '')
            ->line('Estatus: Pagada')
            ->action('Ver Solicitud', $frontendUrl . '/pages/proveedor/sp/detalle/' . $this->solicitudPagoId)
            ->line('Gracias por utilizar CONSTRUCC.');
    }

    /**
     * Canal FCM personalizado
     */
    public function toFcm(object $notifiable): void
    {
        $tokens = $notifiable->deviceTokens()
            ->where('is_active', true)
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return;
        }

        // return [
        $notification = [
            'title' => '✅ Solicitud de Pago Pagada #' . $this->solicitudPagoFolio,
            'body' => "Tu solicitud de pago ha sido pagada exitosamente.",
        ];
        $data = [
            'tipo' => 'solicitud_pago_pagada',
            'action_url' => '/pages/proveedor/sp/detalle/' . $this->solicitudPagoId,
            'solicitud_pago_folio' => $this->solicitudPagoFolio,
            'proveedor_id' => (string) $this->proveedorId,
            'monto' => $this->monto ? (string) $this->monto : null,
            'estatus' => 'pagada',
            'timestamp' => now()->toIso8601String(),
        ];
        // ];
        app(FcmService::class)->sendToTokens($tokens, $notification, $data);
    }
}
