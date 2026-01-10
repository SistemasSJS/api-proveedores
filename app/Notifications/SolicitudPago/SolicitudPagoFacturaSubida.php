<?php

namespace App\Notifications\SolicitudPago;

use App\Services\FcmService;
use App\Traits\NotificationStyleTrait;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SolicitudPagoFacturaSubida extends Notification implements ShouldBroadcastNow
{
  use NotificationStyleTrait;

  public function __construct(
    public string $solicitudPagoFolio,
    public int $solicitudPagoId,
    public int $proveedorId,
    public ?int $userId = null
  ) {}

  /**
   * Canales
   */
  public function via(object $notifiable): array
  {
    $via = ['database'];

    if ($notifiable->email && filter_var($notifiable->email, FILTER_VALIDATE_EMAIL)) {
      $via[] = 'mail';
    }

    if (
      method_exists($notifiable, 'deviceTokens') &&
      $notifiable->deviceTokens()->where('is_active', true)->exists()
    ) {
      $via[] = 'fcm';
    }

    return $via;
  }

  /**
   * Broadcast
   */
  public function toBroadcast(object $notifiable): BroadcastMessage
  {
    return new BroadcastMessage(
      $this->addStylesToData($this->baseData())
    );
  }

  public function broadcastType(): string
  {
    return 'solicitud-pago';
  }

  /**
   * Database
   */
  public function toArray(object $notifiable): array
  {
    return $this->addStylesToData($this->baseData());
  }

  /**
   * Mail
   */
  public function toMail(object $notifiable): MailMessage
  {
    $frontendUrl = config('app.frontend_url', config('app.url'));

    return (new MailMessage)
      ->subject('Factura subida - Solicitud de Pago #' . $this->solicitudPagoFolio)
      ->view('emails.solicitud-pago.factura-subida', [
        'notifiable' => $notifiable,
        'solicitudPagoFolio' => $this->solicitudPagoFolio,
        'urlSolicitud' => $frontendUrl . '/pages/proveedor/sp/detalle/' . $this->solicitudPagoId,
      ]);
  }

  /**
   * FCM
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

    app(FcmService::class)->sendToTokens(
      $tokens,
      [
        'title' => '🧾 Factura subida',
        'body' => "La solicitud #{$this->solicitudPagoFolio} ya cuenta con factura.",
      ],
      $this->addStylesToData([
        'action_url' => '/pages/proveedor/sp/detalle/' . $this->solicitudPagoId,
      ])
    );
  }

  /**
   * Datos base compartidos
   */
  private function baseData(): array
  {
    return [
      'tipo' => 'solicitud_pago',
      'subtipo' => 'factura_subida',
      'titulo' => 'Factura subida #' . $this->solicitudPagoFolio,
      'mensaje' => "La solicitud de pago #{$this->solicitudPagoFolio} ya cuenta con factura.",
      'action_url' => '/pages/proveedor/sp/detalle/' . $this->solicitudPagoId,
      'solicitud_pago_id' => $this->solicitudPagoId,
      'solicitud_pago_folio' => $this->solicitudPagoFolio,
      'proveedor_id' => $this->proveedorId,
      'estatus' => 'factura_subida',
      'timestamp' => now()->toIso8601String(),
    ];
  }

  protected function getNotificationTipo(): string
  {
    return 'solicitud_pago';
  }

  protected function getNotificationSubtipo(): string
  {
    return 'factura_subida';
  }
}
