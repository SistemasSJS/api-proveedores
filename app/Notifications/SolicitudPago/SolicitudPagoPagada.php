<?php

namespace App\Notifications\SolicitudPago;

use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Notifications\Notification;
use App\Services\FcmService;

class SolicitudPagoPagada extends Notification implements ShouldBroadcastNow
{
    public $solicitudPagoFolio;
    public $proveedorId;
    // public $empresaId;
    public $monto;

    public function __construct(string $solicitudPagoFolio, int $proveedorId, ?float $monto = null)
    {
        $this->solicitudPagoFolio = $solicitudPagoFolio;
        $this->proveedorId = $proveedorId;
        // // $this->empresaId = $empresaId;
        $this->monto = $monto;
    }

    /**
     * Canales de notificación
     */
    public function via(object $notifiable): array
    {
        $via = ['broadcast', 'database', 'mail'];

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
            'action_url' => '/solicitudes-pago/' . $this->solicitudPagoFolio,
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
        return [new PrivateChannel('App.Models.User.' . $this->id)];
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
            'action_url' => '/solicitudes-pago/' . $this->solicitudPagoFolio,
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
            ->action('Ver Solicitud', $frontendUrl . '/solicitudes-pago/' . $this->solicitudPagoFolio)
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

        $notification = [
            'title' => '✅ Solicitud de Pago Pagada #' . $this->solicitudPagoFolio,
            'body' => "Tu solicitud de pago ha sido pagada exitosamente.",
        ];

        $data = [
            'tipo' => 'solicitud_pago_pagada',
            'action_url' => '/solicitudes-pago/' . $this->solicitudPagoFolio,
            'solicitud_pago_folio' => $this->solicitudPagoFolio,
            'proveedor_id' => (string) $this->proveedorId,
            // 'empresa_id' => (string) $this->empresaId,
            'monto' => $this->monto ? (string) $this->monto : null,
            'estatus' => 'pagada',
            'timestamp' => now()->toIso8601String(),
        ];

        app(FcmService::class)->sendToTokens($tokens, $notification, $data);
    }
}
