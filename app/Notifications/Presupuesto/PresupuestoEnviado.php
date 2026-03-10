<?php

namespace App\Notifications\Presupuesto;

use App\Models\Presupuesto;
use App\Services\FcmService;
use App\Traits\NotificationStyleTrait;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificación al proveedor cuando envía un presupuesto al cliente.
 */
class PresupuestoEnviado extends Notification implements ShouldBroadcastNow
{
    use NotificationStyleTrait;

    public function __construct(
        public Presupuesto $presupuesto
    ) {}

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

        return (new MailMessage)
            ->subject('Presupuesto enviado #' . $this->presupuesto->numero_presupuesto)
            ->view('emails.presupuesto.notificacion-enviado', [
                'notifiable' => $notifiable,
                'presupuesto' => $this->presupuesto,
                'urlDetalle' => $urlDetalle,
            ]);
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

        app(FcmService::class)->sendToTokens(
            $tokens,
            [
                'title' => 'Presupuesto enviado #' . $this->presupuesto->numero_presupuesto,
                'body' => 'Se envió el presupuesto a ' . ($this->presupuesto->empresa_receptora_empresa ?? $this->presupuesto->empresa_receptora_nombre ?? 'el cliente') . '.',
            ],
            $this->addStylesToData([
                'action_url' => '/pages/proveedor/presupuestos/detalle/' . $this->presupuesto->id,
            ])
        );
    }

    private function baseData(): array
    {
        return [
            'tipo' => 'presupuesto',
            'subtipo' => 'enviado',
            'titulo' => 'Presupuesto enviado #' . $this->presupuesto->numero_presupuesto,
            'mensaje' => 'Se envió el presupuesto a ' . ($this->presupuesto->empresa_receptora_empresa ?? $this->presupuesto->empresa_receptora_nombre ?? 'el cliente') . '.',
            'action_url' => '/pages/proveedor/presupuestos/detalle/' . $this->presupuesto->id,
            'presupuesto_id' => $this->presupuesto->id,
            'presupuesto_numero' => $this->presupuesto->numero_presupuesto,
            'proveedor_id' => $this->presupuesto->proveedor_id,
            'estatus' => 'enviado',
            'timestamp' => now()->toIso8601String(),
        ];
    }

    protected function getNotificationTipo(): string
    {
        return 'presupuesto';
    }

    protected function getNotificationSubtipo(): string
    {
        return 'enviado';
    }
}
