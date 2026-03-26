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
 * Notificación al proveedor cuando el cliente acepta el presupuesto.
 */
class PresupuestoAceptadoNotification extends Notification implements ShouldBroadcastNow
{
    use NotificationStyleTrait;

    public function __construct(
        public Presupuesto $presupuesto
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

        return (new MailMessage)
            ->subject('Presupuesto aceptado #' . $this->presupuesto->numero_presupuesto)
            ->view('emails.presupuesto.notificacion-aceptado', [
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

        $cliente = $this->presupuesto->empresa_receptora_empresa ?? $this->presupuesto->empresa_receptora_nombre ?? 'el cliente';

        app(FcmService::class)->sendToTokens(
            $tokens,
            [
                'title' => 'Presupuesto aceptado #' . $this->presupuesto->numero_presupuesto,
                'body' => $cliente . ' aceptó tu presupuesto.',
            ],
            $this->addStylesToData([
                'action_url' => '/pages/proveedor/presupuestos/detalle/' . $this->presupuesto->id,
            ])
        );
    }

    private function baseData(): array
    {
        $cliente = $this->presupuesto->empresa_receptora_empresa ?? $this->presupuesto->empresa_receptora_nombre ?? 'el cliente';

        return [
            'tipo' => 'presupuesto',
            'subtipo' => 'aceptado',
            'titulo' => 'Presupuesto aceptado #' . $this->presupuesto->numero_presupuesto,
            'mensaje' => $cliente . ' aceptó tu presupuesto.',
            'action_url' => '/pages/proveedor/presupuestos/detalle/' . $this->presupuesto->id,
            'presupuesto_id' => $this->presupuesto->id,
            'presupuesto_numero' => $this->presupuesto->numero_presupuesto,
            'proveedor_id' => $this->presupuesto->proveedor_id,
            'estatus' => 'aceptado',
            'timestamp' => now()->toIso8601String(),
        ];
    }

    protected function getNotificationTipo(): string
    {
        return 'presupuesto';
    }

    protected function getNotificationSubtipo(): string
    {
        return 'aceptado';
    }
}
