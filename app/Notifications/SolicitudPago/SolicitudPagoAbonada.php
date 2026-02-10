<?php

namespace App\Notifications\SolicitudPago;

use App\Traits\NotificationStyleTrait;
use App\Services\FcmService;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SolicitudPagoAbonada extends Notification implements ShouldBroadcastNow
{
  use NotificationStyleTrait;

  public $solicitudPagoId;
  public $solicitudPagoFolio;
  public $proveedorId;
  public $montoAbonado;
  public $montoRestante;
  public $userId;

  public function __construct(
    string $solicitudPagoFolio,
    int $solicitudPagoId,
    int $proveedorId,
    ?float $montoAbonado = null,
    ?float $montoRestante = null,
    ?int $userId = null
  ) {
    $this->solicitudPagoFolio = $solicitudPagoFolio;
    $this->solicitudPagoId = $solicitudPagoId;
    $this->proveedorId = $proveedorId;
    $this->montoAbonado = $montoAbonado;
    $this->montoRestante = $montoRestante;
    $this->userId = $userId;
  }

  /**
   * Canales de notificación
   */
  public function via(object $notifiable): array
  {
    $via = ['database'];

    if ($notifiable->email && filter_var($notifiable->email, FILTER_VALIDATE_EMAIL)) {
      $via[] = 'mail';
    }

    if (method_exists($notifiable, 'deviceTokens') && $notifiable->deviceTokens()->where('is_active', true)->exists()) {
      $via[] = 'fcm';
    }

    return $via;
  }

  /**
   * Canal Broadcast
   */
  public function toBroadcast(object $notifiable): BroadcastMessage
  {
    $data = [
      'tipo' => 'solicitud_pago',
      'subtipo' => 'abonada',
      'titulo' => 'Abono registrado a la Solicitud de Pago #' . $this->solicitudPagoFolio,
      'mensaje' => "Se registró un abono a tu solicitud de pago #{$this->solicitudPagoFolio}.",
      'action_url' => '/pages/proveedor/sp/detalle/' . $this->solicitudPagoId,
      'data' => [
        'solicitud_pago_folio' => $this->solicitudPagoFolio,
        'proveedor_id' => $this->proveedorId,
        'monto_abonado' => $this->montoAbonado,
        'monto_restante' => $this->montoRestante,
        'estatus' => 'abonada',
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
      'subtipo' => 'abonada',
      'titulo' => 'Abono registrado a la Solicitud de Pago #' . $this->solicitudPagoFolio,
      'mensaje' => "Se registró un abono a tu solicitud de pago #{$this->solicitudPagoFolio}.",
      'action_url' => '/pages/proveedor/sp/detalle/' . $this->solicitudPagoId,
      'solicitud_pago_id' => $this->solicitudPagoId,
      'solicitud_pago_folio' => $this->solicitudPagoFolio,
      'proveedor_id' => $this->proveedorId,
      'monto_abonado' => $this->montoAbonado,
      'monto_restante' => $this->montoRestante,
      'estatus' => 'abonada',
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
      ->subject('Abono registrado a la Solicitud de Pago #' . $this->solicitudPagoFolio)
      ->view('emails.solicitud-pago.abonada', [
        'notifiable' => $notifiable,
        'solicitudPagoFolio' => $this->solicitudPagoFolio,
        'solicitudPagoId' => $this->solicitudPagoId,
        'proveedorId' => $this->proveedorId,
        'montoAbonado' => $this->montoAbonado,
        'montoRestante' => $this->montoRestante,
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
      'title' => '💳 Abono registrado - SPP #' . $this->solicitudPagoFolio,
      'body' => 'Se registró un abono a tu solicitud de pago.',
    ];

    $data = [
      'tipo' => 'solicitud_pago',
      'subtipo' => 'abonada',
      'action_url' => '/pages/proveedor/sp/detalle/' . $this->solicitudPagoId,
      'solicitud_pago_folio' => $this->solicitudPagoFolio,
      'proveedor_id' => (string) $this->proveedorId,
      'monto_abonado' => $this->montoAbonado ? (string) $this->montoAbonado : null,
      'monto_restante' => $this->montoRestante ? (string) $this->montoRestante : null,
      'estatus' => 'abonada',
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
    return 'abonada';
  }
}
