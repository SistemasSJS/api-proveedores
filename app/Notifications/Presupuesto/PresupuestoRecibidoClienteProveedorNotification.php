<?php

namespace App\Notifications\Presupuesto;

use App\Models\Presupuesto;
use App\Traits\NotificationStyleTrait;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Cliente del presupuesto con cuenta en la app (otro proveedor): recibió presupuesto o reenvío.
 */
class PresupuestoRecibidoClienteProveedorNotification extends Notification implements ShouldBroadcastNow
{
    use NotificationStyleTrait;

    public function __construct(
        public Presupuesto $presupuesto,
        public bool $esReenvio = false
    ) {}

    public function via(object $notifiable): array
    {
        $via = ['broadcast', 'database'];
        if (
            method_exists($notifiable, 'deviceTokens')
            && $notifiable->deviceTokens()->where('is_active', true)->exists()
        ) {
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

    public function toFcm(object $notifiable): array
    {
        $data = $this->addStylesToData($this->baseData());

        return [
            'notification' => [
                'title' => $data['titulo'],
                'body' => $data['mensaje'],
                'icon' => '/assets/icon/favicon.png',
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ],
            'data' => [
                'type' => 'presupuesto',
                'subtipo' => (string) $data['subtipo'],
                'titulo' => (string) $data['titulo'],
                'mensaje' => (string) $data['mensaje'],
                'action_url' => (string) $data['action_url'],
                'presupuesto_id' => (string) $data['presupuesto_id'],
                'es_reenvio' => $data['es_reenvio'] ? '1' : '0',
                'timestamp' => (string) $data['timestamp'],
            ],
        ];
    }

    private function baseData(): array
    {
        $emisor = $this->presupuesto->proveedor?->nombre_comercial
            ?? $this->presupuesto->proveedor?->razon_social
            ?? 'Un proveedor';
        $folio = $this->presupuesto->numero_presupuesto;
        $total = number_format((float) $this->presupuesto->total, 2) . ' ' . ($this->presupuesto->term_cond_moneda ?? 'MXN');

        if ($this->esReenvio) {
            $titulo = 'Presupuesto actualizado #' . $folio;
            $mensaje = $emisor . ' reenvió el presupuesto con cambios. Total: ' . $total . '.';
        } else {
            $titulo = 'Nuevo presupuesto recibido #' . $folio;
            $mensaje = $emisor . ' te envió un presupuesto. Total: ' . $total . '.';
        }

        $frontendUrl = rtrim(config('app.frontend_url', config('app.url')), '/');
        $token = $this->presupuesto->token_publico;
        $actionUrl = $token
            ? '/public/presupuesto/' . $token
            : '/pages/proveedor/presupuestos/detalle/' . $this->presupuesto->id;

        return [
            'tipo' => 'presupuesto',
            'subtipo' => 'recibido_cliente_proveedor',
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'action_url' => $actionUrl,
            'url_publica' => $token ? $frontendUrl . '/public/presupuesto/' . $token : null,
            'presupuesto_id' => $this->presupuesto->id,
            'presupuesto_numero' => $this->presupuesto->numero_presupuesto,
            'proveedor_emisor_id' => $this->presupuesto->proveedor_id,
            'proveedor_receptor_id' => $this->presupuesto->proveedor_receptor_id,
            'es_reenvio' => $this->esReenvio,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    protected function getNotificationTipo(): string
    {
        return 'presupuesto';
    }

    protected function getNotificationSubtipo(): string
    {
        return 'recibido_cliente_proveedor';
    }
}
