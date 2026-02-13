<?php

namespace App\Notifications\SolicitudPago;

use App\Models\SolicitudPago;
use App\Traits\NotificationStyleTrait;
use App\Services\FcmService;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Notifications\Notification;

class SolicitudPagoPagada extends Notification implements ShouldBroadcastNow
{
    use NotificationStyleTrait;

    public $solicitudPagoId;
    public $solicitudPagoFolio;
    public $proveedorId;
    public $monto;
    public $userId;

    public function __construct(string $solicitudPagoFolio, int $solicitudPagoId, int $proveedorId, float $monto = null, int $userId = null)
    {
    $this->solicitudPagoFolio = $solicitudPagoFolio;
    $this->solicitudPagoId = $solicitudPagoId;
    $this->proveedorId = $proveedorId;
    $this->monto = $monto;
    $this->userId = $userId;
    $this->solicitudPagoFolio = $solicitudPagoFolio;
  }

    /**
     * Canales de notificación
     */
    public function via(object $notifiable): array
    {
        // $via = ['broadcast', 'database'];
        $via = ['database'];

        // Solo agregar email si el correo es válido
        if ($notifiable->email && filter_var($notifiable->email, FILTER_VALIDATE_EMAIL)) {
            $via[] = 'mail';
        }

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
        $data = [
            'tipo' => 'solicitud_pago',  // Categoría base
            'subtipo' => 'pagada',        // Tipo específico
            'titulo' => 'Solicitud de pago pagada #' . $this->solicitudPagoFolio,
            'mensaje' => "Se registró el pago de tu solicitud #{$this->solicitudPagoFolio}.",
            'action_url' => '/pages/proveedor/sp/detalle/' . $this->solicitudPagoId,
            'data' => [
                'solicitud_pago_folio' => $this->solicitudPagoFolio,
                'proveedor_id' => $this->proveedorId,
                // 'empresa_id' => $this->empresaId,
                'monto' => $this->monto,
                'estatus' => 'pagada',
            ],
            'timestamp' => now()->toIso8601String(),
        ];

        return new BroadcastMessage($this->addStylesToData($data));
    }

    public function broadcastType(): string
    {
        return 'solicitud-pago';
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
        $data = [
            'tipo' => 'solicitud_pago',  // Categoría base
            'subtipo' => 'pagada',        // Tipo específico
            'titulo' => 'Solicitud de pago pagada #' . $this->solicitudPagoFolio,
            'mensaje' => "Se registró el pago de tu solicitud #{$this->solicitudPagoFolio}.",
            'action_url' => '/pages/proveedor/sp/detalle/' . $this->solicitudPagoId,
            'solicitud_pago_id' => $this->solicitudPagoId,
            'solicitud_pago_folio' => $this->solicitudPagoFolio,
            'proveedor_id' => $this->proveedorId,
            // 'empresa_id' => $this->empresaId,
            'monto' => $this->monto,
            'estatus' => 'pagada',
            'timestamp' => now()->toIso8601String(),
        ];

        return $this->addStylesToData($data);
    }

    /**
     * Canal Mail
     */
    public function toMail(object $notifiable): MailMessage
    {
        $frontendUrl = config('app.frontend_url', config('app.url'));
        $urlSolicitud = $frontendUrl . '/pages/proveedor/sp/detalle/' . $this->solicitudPagoId;

        return (new MailMessage)
            ->subject('Solicitud de pago pagada #' . $this->solicitudPagoFolio)
            ->view('emails.solicitud-pago.pagada', [
                'notifiable' => $notifiable,
                'solicitudPagoFolio' => $this->solicitudPagoFolio,
                'solicitudPagoId' => $this->solicitudPagoId,
                'proveedorId' => $this->proveedorId,
                'monto' => $this->monto,
                'urlSolicitud' => $urlSolicitud,
            ]);
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
            'title' => 'Solicitud de pago pagada #' . $this->solicitudPagoFolio,
            'body' => 'Se registró el pago de tu solicitud.',
        ];
        $data = [
            'tipo' => 'solicitud_pago',  // Categoría base
            'subtipo' => 'pagada',        // Tipo específico
            'action_url' => '/pages/proveedor/sp/detalle/' . $this->solicitudPagoId,
            'solicitud_pago_folio' => $this->solicitudPagoFolio,
            'proveedor_id' => (string) $this->proveedorId,
            'monto' => $this->monto ? (string) $this->monto : null,
            'estatus' => 'pagada',
            'timestamp' => now()->toIso8601String(),
        ];

        $data = $this->addStylesToData($data);
        app(FcmService::class)->sendToTokens($tokens, $notification, $data);
    }

    /**
     * Implementación de métodos abstractos del trait
     */
    protected function getNotificationTipo(): string
    {
        return 'solicitud_pago';
    }

    protected function getNotificationSubtipo(): string
    {
    return 'pagada';
  }
}
