<?php

namespace App\Notifications\Presupuesto;

use App\Models\Presupuesto;
use App\Services\FcmService;
use App\Support\PresupuestoPdf;
use App\Traits\NotificationStyleTrait;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificación al proveedor cuando el cliente rechaza el presupuesto.
 */
class PresupuestoRechazadoNotification extends Notification implements ShouldBroadcastNow
{
    use NotificationStyleTrait;

    public function __construct(
        public Presupuesto $presupuesto,
        public ?string $motivoRechazo = null
    ) {}

    public function via(object $notifiable): array
    {
        $via = ['broadcast', 'database'];

        if ($notifiable->email && filter_var($notifiable->email, FILTER_VALIDATE_EMAIL)) {
            $via[] = 'mail';
        }

        if (method_exists($notifiable, 'deviceTokens') && $notifiable->deviceTokens()->where('is_active', true)->exists()) {
            $via[] = 'fcm';
        }

        return $via;
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->addStylesToData($this->baseData()));
    }

    public function broadcastType(): string
    {
        return 'presupuesto';
    }

    public function toArray(object $notifiable): array
    {
        return $this->addStylesToData($this->baseData());
    }

    public function toMail(object $notifiable): MailMessage
    {
        $frontendUrl = config('app.frontend_url', config('app.url'));
        $urlDetalle = $frontendUrl . '/pages/proveedor/presupuestos/detalle/' . $this->presupuesto->id;

        $mail = (new MailMessage)
            ->subject('Presupuesto rechazado #' . $this->presupuesto->numero_presupuesto)
            ->view('emails.presupuesto.notificacion-rechazado', [
                'notifiable' => $notifiable,
                'presupuesto' => $this->presupuesto,
                'motivoRechazo' => $this->motivoRechazo,
                'urlDetalle' => $urlDetalle,
            ]);

        try {
            $this->presupuesto->loadMissing(Presupuesto::eagerLodable());
            $pdf = PresupuestoPdf::renderPdfBinary($this->presupuesto);
            $mail->attachData(
                $pdf,
                'Presupuesto_' . $this->presupuesto->numero_presupuesto . '.pdf',
                ['mime' => 'application/pdf']
            );
        } catch (\Throwable $e) {
        }

        return $mail;
    }

    public function toFcm(object $notifiable): void
    {
        $tokens = $notifiable->deviceTokens()
            ->where('is_active', true)
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return;
        }

        $cliente = $this->presupuesto->empresa_receptora_empresa ?? $this->presupuesto->empresa_receptora_nombre ?? 'el cliente';
        $body = $cliente . ' rechazó tu presupuesto.';
        if ($this->motivoRechazo) {
            $body .= ' Motivo: ' . \Illuminate\Support\Str::limit($this->motivoRechazo, 80);
        }

        app(FcmService::class)->sendToTokens(
            $tokens,
            [
                'title' => 'Presupuesto rechazado #' . $this->presupuesto->numero_presupuesto,
                'body' => $body,
            ],
            $this->addStylesToData([
                'action_url' => '/pages/proveedor/presupuestos/detalle/' . $this->presupuesto->id,
            ])
        );
    }

    private function baseData(): array
    {
        $cliente = $this->presupuesto->empresa_receptora_empresa ?? $this->presupuesto->empresa_receptora_nombre ?? 'el cliente';
        $mensaje = $cliente . ' rechazó tu presupuesto.';
        if ($this->motivoRechazo) {
            $mensaje .= ' Motivo: ' . \Illuminate\Support\Str::limit($this->motivoRechazo, 100);
        }

        return [
            'tipo' => 'presupuesto',
            'subtipo' => 'rechazado',
            'titulo' => 'Presupuesto rechazado #' . $this->presupuesto->numero_presupuesto,
            'mensaje' => $mensaje,
            'motivo_rechazo' => $this->motivoRechazo,
            'action_url' => '/pages/proveedor/presupuestos/detalle/' . $this->presupuesto->id,
            'presupuesto_id' => $this->presupuesto->id,
            'presupuesto_numero' => $this->presupuesto->numero_presupuesto,
            'proveedor_id' => $this->presupuesto->proveedor_id,
            'estatus' => 'rechazado',
            'timestamp' => now()->toIso8601String(),
        ];
    }

    protected function getNotificationTipo(): string
    {
        return 'presupuesto';
    }

    protected function getNotificationSubtipo(): string
    {
        return 'rechazado';
    }
}
