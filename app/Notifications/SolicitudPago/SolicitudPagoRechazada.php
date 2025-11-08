<?php

namespace App\Notifications\SolicitudPago;

use App\Services\FcmService;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Notifications\Notification;

class SolicitudPagoRechazada extends Notification implements ShouldBroadcastNow
{
    public $solicitudPagoId;
    public $solicitudPagoFolio;
    public $proveedorId;
    public $motivo;
    public $userId;

    public function __construct(string $solicitudPagoFolio, int $solicitudPagoId, int $proveedorId, ?string $motivo = null, ?int $userId = null)
    {
        $this->solicitudPagoFolio = $solicitudPagoFolio;
        $this->solicitudPagoId = $solicitudPagoId;
        $this->proveedorId = $proveedorId;
        $this->motivo = $motivo;
        $this->userId = $userId;
    }

    /**
     * Canales de notificación
     */
    public function via(object $notifiable): array
    {
        $via = ['database', 'mail', 'broadcast'];

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
            'tipo' => 'solicitud_pago_rechazada',
            'titulo' => 'Solicitud de Pago Rechazada #' . $this->solicitudPagoFolio,
            'mensaje' => "Tu solicitud de pago #{$this->solicitudPagoFolio} ha sido rechazada.",
            'action_url' => '/pages/proveedor/sp/detalle/' . $this->solicitudPagoId,
            'data' => [
                'solicitud_pago_folio' => $this->solicitudPagoFolio,
                'proveedor_id' => $this->proveedorId,
                // 'empresa_id' => $this->empresaId,
                'motivo' => $this->motivo,
                'estatus' => 'rechazada',
            ],
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function broadcastType(): string
    {
        return 'solicitud-pago-rechazada';
    }

    /**
     * Canal privado por usuario
     */
    public function broadcastOn(): array
    {
        // Laravel automáticamente envía al canal privado del notifiable (usuario)
        // Esto enviará a: private-App.Models.User.{userId}
        return [];
    }

    /**
     * Canal Database
     */
    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'solicitud_pago_rechazada',
            'titulo' => 'Solicitud de Pago Rechazada #' . $this->solicitudPagoFolio,
            'mensaje' => "Tu solicitud de pago #{$this->solicitudPagoFolio} ha sido rechazada.",
            'action_url' => '/pages/proveedor/sp/detalle/' . $this->solicitudPagoId,
            'solicitud_pago_folio' => $this->solicitudPagoFolio,
            'proveedor_id' => $this->proveedorId,
            // 'empresa_id' => $this->empresaId,
            'motivo' => $this->motivo,
            'estatus' => 'rechazada',
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Canal Mail
     */
    public function toMail(object $notifiable): MailMessage
    {
        $frontendUrl = config('app.frontend_url', config('app.url'));

        $mailMessage = (new MailMessage)
            ->subject('Solicitud de Pago Rechazada #' . $this->solicitudPagoFolio)
            ->greeting('Hola ' . $notifiable->name . ',')
            ->line('Tu solicitud de pago ha sido rechazada.')
            ->line('Número de solicitud: ' . $this->solicitudPagoFolio);

        if ($this->motivo) {
            $mailMessage->line('Motivo: ' . $this->motivo);
        }

        return $mailMessage
            ->line('Estatus: Rechazada')
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
            'title' => '❌ Solicitud de Pago Rechazada #' . $this->solicitudPagoFolio,
            'body' => $this->motivo
                ? "Tu solicitud de pago ha sido rechazada. Motivo: {$this->motivo}"
                : "Tu solicitud de pago ha sido rechazada.",
        ];
        $data = [
            'tipo' => 'solicitud_pago_rechazada',
            'action_url' => '/pages/proveedor/sp/detalle/' . $this->solicitudPagoId,
            'solicitud_pago_folio' => $this->solicitudPagoFolio,
            'proveedor_id' => (string) $this->proveedorId,
            'motivo' => $this->motivo,
            'estatus' => 'rechazada',
            'timestamp' => now()->toIso8601String(),
        ];
        // ];
        app(FcmService::class)->sendToTokens($tokens, $notification, $data);
    }
}
