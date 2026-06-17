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

    public function toFcm(object $notifiable): void
    {
        $tokens = $notifiable->deviceTokens()
            ->where('is_active', true)
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return;
        }

        $base = $this->addStylesToData($this->baseData());

        $notification = [
            'title' => $base['titulo'],
            'body' => $base['mensaje'],
        ];

        $data = [
            'tipo' => 'presupuesto',
            'subtipo' => (string) $base['subtipo'],
            'action_url' => (string) $base['action_url'],
            'url_publica' => (string) ($base['url_publica'] ?? ''),
            'presupuesto_id' => (string) $base['presupuesto_id'],
            'presupuesto_numero' => (string) $base['presupuesto_numero'],
            'proveedor_emisor_id' => (string) $base['proveedor_emisor_id'],
            'proveedor_receptor_id' => (string) ($base['proveedor_receptor_id'] ?? ''),
            'usuario_envio_nombre' => (string) $base['usuario_envio_nombre'],
            'empresa_emisora_nombre' => (string) $base['empresa_emisora_nombre'],
            'es_reenvio' => $base['es_reenvio'] ? '1' : '0',
            'timestamp' => (string) $base['timestamp'],
        ];

        $data = $this->addStylesToData($data);

        app(FcmService::class)->sendToTokens($tokens, $notification, $data);
    }

    private function baseData(): array
    {
        $this->presupuesto->loadMissing(['user', 'proveedor']);

        $nombreUsuario = $this->presupuesto->user?->name ?? 'Usuario';
        $nombreEmpresa = $this->presupuesto->proveedor?->nombre_comercial
            ?? $this->presupuesto->proveedor?->razon_social
            ?? 'Empresa';

        $folio = $this->presupuesto->numero_presupuesto;
        $total = number_format((float) $this->presupuesto->total, 2) . ' ' . ($this->presupuesto->term_cond_moneda ?? 'MXN');
        $quienEnvia = $nombreUsuario . ' de "' . $nombreEmpresa . '"';

        if ($this->esReenvio) {
            $titulo = 'Presupuesto actualizado #' . $folio;
            $mensaje = $quienEnvia . ' reenvió el presupuesto con cambios. Total: ' . $total . '.';
        } else {
            $titulo = 'Nuevo presupuesto #' . $folio . ' — ' . $nombreUsuario;
            $mensaje = $quienEnvia . ' te envió un presupuesto. Total: ' . $total . '.';
        }

        $frontendUrl = rtrim(config('app.frontend_url', config('app.url')), '/');
        $urlPublica = $this->presupuesto->token_publico
            ? $frontendUrl . '/public/presupuesto/' . $this->presupuesto->token_publico
            : $frontendUrl . '/public/presupuesto/' . $this->presupuesto->id;
        $actionUrl = '/pages/proveedor/presupuestos/preview/' . $this->presupuesto->id;

        return [
            'tipo' => 'presupuesto',
            'subtipo' => 'recibido_cliente_proveedor',
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'action_url' => $actionUrl,
            'url_publica' => $urlPublica,
            'presupuesto_id' => $this->presupuesto->id,
            'presupuesto_numero' => $this->presupuesto->numero_presupuesto,
            'proveedor_emisor_id' => $this->presupuesto->proveedor_id,
            'proveedor_receptor_id' => $this->presupuesto->proveedor_receptor_id,
            'usuario_envio_id' => $this->presupuesto->user_id,
            'usuario_envio_nombre' => $nombreUsuario,
            'empresa_emisora_nombre' => $nombreEmpresa,
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
