<?php

namespace App\Notifications\Presupuesto;

use App\Models\Presupuesto;
use App\Services\FcmService;
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

    public function toFcm(object $notifiable): void
    {
        $tokens = $notifiable->deviceTokens()
            ->where('is_active', true)
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return;
        }

        $data = $this->baseData();
        app(FcmService::class)->sendToTokens(
            $tokens,
            [
                'title' => $data['titulo'],
                'body' => $data['mensaje'],
            ],
            $this->addStylesToData([
                'action_url' => $data['action_url'],
            ])
        );
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
