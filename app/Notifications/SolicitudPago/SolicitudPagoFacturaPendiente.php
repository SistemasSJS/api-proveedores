<php

namespace App\Notifications\SolicitudPago;

use App\Models\SolicitudPago;
use App\Traits\NotificationStyleTrait;
use App\Services\FcmService;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SolicitudPagoFacturaPendiente extends Notification implements ShouldBroadcastNow
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
    $this->solicitudPagoFolio = $this->resolveSolicitudPagoFolio($solicitudPagoFolio, $solicitudPagoId);
  }

  /**
   * Canales de notificación
   */
  public function via(object $notifiable): array
  {
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
   * Canal Broadcast (WebSocket)
   */
  public function toBroadcast(object $notifiable): BroadcastMessage
  {
    $data = [
      'tipo' => 'solicitud_pago',
      'subtipo' => 'factura_pendiente',
      'titulo' => 'Factura pendiente de la solicitud de pago #' . $this->solicitudPagoFolio,
      'mensaje' => "La solicitud de pago #{$this->solicitudPagoFolio} fue pagada, pero falta la factura correspondiente. Emite y sube el CFDI conforme a los datos fiscales indicados.",
      'action_url' => '/pages/proveedor/sp/detalle/' . $this->solicitudPagoId,
      'data' => [
        'solicitud_pago_folio' => $this->solicitudPagoFolio,
        'proveedor_id' => $this->proveedorId,
        'monto' => $this->monto,
        'estatus' => 'factura_pendiente',
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
   * Canal Database
   */
  public function toArray(object $notifiable): array
  {
    $data = [
      'tipo' => 'solicitud_pago',
      'subtipo' => 'factura_pendiente',
      'titulo' => 'Factura pendiente de la solicitud de pago #' . $this->solicitudPagoFolio,
      'mensaje' => "La solicitud de pago #{$this->solicitudPagoFolio} fue pagada, pero falta la factura correspondiente. Emite y sube el CFDI conforme a los datos fiscales indicados.",
      'action_url' => '/pages/proveedor/sp/detalle/' . $this->solicitudPagoId,
      'solicitud_pago_id' => $this->solicitudPagoId,
      'solicitud_pago_folio' => $this->solicitudPagoFolio,
      'proveedor_id' => $this->proveedorId,
      'monto' => $this->monto,
      'estatus' => 'factura_pendiente',
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
      ->subject('Factura pendiente de la solicitud de pago #' . $this->solicitudPagoFolio)
      ->view('emails.solicitud-pago.factura-pendiente', [
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
      'title' => 'Factura pendiente - SPP #' . $this->solicitudPagoFolio,
      'body' => 'La solicitud ya fue pagada, pero falta la factura (CFDI).',
    ];

    $data = [
      'tipo' => 'solicitud_pago',
      'subtipo' => 'factura_pendiente',
      'action_url' => '/pages/proveedor/sp/detalle/' . $this->solicitudPagoId,
      'solicitud_pago_folio' => $this->solicitudPagoFolio,
      'proveedor_id' => (string) $this->proveedorId,
      'monto' => $this->monto  (string) $this->monto : null,
      'estatus' => 'factura_pendiente',
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
    return 'factura_pendiente';
  }

  private function resolveSolicitudPagoFolio(string $folio, int $solicitudPagoId): string
  {
    if ($folio && !preg_match('/^SP-\\d+$/', $folio)) {
      return $folio;
    }

    $sp = SolicitudPago::find($solicitudPagoId);
    if ($sp && $sp->numero_folio_solicitud) {
      return $sp->numero_folio_solicitud;
    }

    return $folio : ('SP-' . $solicitudPagoId);
  }
}
