<?php

namespace App\Notifications\Usuario;

use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Services\FcmService;

class UsuarioReasignadoNotification extends Notification implements ShouldBroadcastNow
{
    public $usuarioNombre;
    public $usuarioEmail;
    public $rolNombre;
    public $tipoRelacion;
    public $proveedorNombre;
    public $fechaAsignacion;

    public function __construct(
        string $usuarioNombre,
        string $usuarioEmail,
        string $rolNombre,
        string $tipoRelacion,
        string $proveedorNombre
    ) {
        $this->usuarioNombre = $usuarioNombre;
        $this->usuarioEmail = $usuarioEmail;
        $this->rolNombre = $rolNombre;
        $this->tipoRelacion = $tipoRelacion;
        $this->proveedorNombre = $proveedorNombre;
        $this->fechaAsignacion = now()->toIso8601String();
    }

    /**
     * Canales de notificación
     */
    public function via(object $notifiable): array
    {
        $via = ['broadcast', 'database'];

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
        return new BroadcastMessage([
            'tipo' => 'usuario',
            'subtipo' => 'reasignado',
            'titulo' => 'Nuevo Usuario Asignado',
            'mensaje' => "Se ha asignado a {$this->usuarioNombre} como usuario {$this->tipoRelacion} de {$this->proveedorNombre}",
            'action_url' => '/panel-admin/usuarios',
            'data' => [
                'usuario_nombre' => $this->usuarioNombre,
                'usuario_email' => $this->usuarioEmail,
                'rol_nombre' => $this->rolNombre,
                'tipo_relacion' => $this->tipoRelacion,
                'proveedor_nombre' => $this->proveedorNombre,
            ],
            'timestamp' => $this->fechaAsignacion,
        ]);
    }

    public function broadcastType(): string
    {
        return 'usuario-reasignado';
    }

    public function broadcastOn(): array
    {
        return [];
    }

    /**
     * Canal Database
     */
    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'usuario',
            'subtipo' => 'reasignado',
            'titulo' => 'Nuevo Usuario Asignado',
            'mensaje' => "Se ha asignado a {$this->usuarioNombre} como usuario {$this->tipoRelacion} de {$this->proveedorNombre}",
            'action_url' => '/panel-admin/usuarios',
            'usuario_nombre' => $this->usuarioNombre,
            'usuario_email' => $this->usuarioEmail,
            'rol_nombre' => $this->rolNombre,
            'tipo_relacion' => $this->tipoRelacion,
            'proveedor_nombre' => $this->proveedorNombre,
            'timestamp' => $this->fechaAsignacion,
        ];
    }

    /**
     * Canal Mail
     */
    public function toMail(object $notifiable): MailMessage
    {
        $frontendUrl = config('app.frontend_url', config('app.url'));
        $urlUsuarios = $frontendUrl . '/panel-admin/usuarios';

        $tipoTexto = $this->tipoRelacion === 'PRINCIPAL' ? 'principal' : 'secundario';

        return (new MailMessage)
            ->subject('Nuevo Usuario Asignado a ' . $this->proveedorNombre)
            ->greeting('¡Hola ' . $notifiable->name . '!')
            ->line("Se ha asignado un nuevo usuario {$tipoTexto} a su empresa {$this->proveedorNombre}.")
            ->line("**Usuario:** {$this->usuarioNombre}")
            ->line("**Email:** {$this->usuarioEmail}")
            ->line("**Rol:** {$this->rolNombre}")
            ->line("**Tipo:** " . ucfirst(strtolower($this->tipoRelacion)))
            ->action('Ver Usuarios', $urlUsuarios)
            ->line('Gracias por usar nuestra aplicación.');
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

        $tipoTexto = $this->tipoRelacion === 'PRINCIPAL' ? 'principal' : 'secundario';

        $notification = [
            'title' => '👤 Nuevo Usuario Asignado',
            'body' => "Se ha asignado a {$this->usuarioNombre} como usuario {$tipoTexto} de {$this->proveedorNombre}",
        ];

        $data = [
            'tipo' => 'usuario',
            'subtipo' => 'reasignado',
            'action_url' => '/panel-admin/usuarios',
            'usuario_nombre' => $this->usuarioNombre,
            'usuario_email' => $this->usuarioEmail,
            'rol_nombre' => $this->rolNombre,
            'tipo_relacion' => $this->tipoRelacion,
            'proveedor_nombre' => $this->proveedorNombre,
            'timestamp' => $this->fechaAsignacion,
        ];

        app(FcmService::class)->sendToTokens($tokens, $notification, $data);
    }
}
