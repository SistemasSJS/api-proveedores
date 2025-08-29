<?php

namespace App\Notifications;

use App\Models\Cotizacion;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * Notificación enviada al proveedor cuando se crea una nueva cotización
 * desde el módulo de construcción
 */
class CotizacionCreada extends Notification implements ShouldBroadcast
{
    use Queueable;

    protected Cotizacion $cotizacion;
    protected User $solicitante;
    protected string $moduloOrigen;

    /**
     * Create a new notification instance.
     */
    public function __construct(Cotizacion $cotizacion, User $solicitante, string $moduloOrigen = 'construccion')
    {
        $this->cotizacion = $cotizacion;
        $this->solicitante = $solicitante;
        $this->moduloOrigen = $moduloOrigen;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['mail', 'broadcast', 'database'];

        // Agregar broadcasting si está configurado
        // if (config('broadcasting.default') !== 'null') {
        //     $channels[] = 'broadcast';
        // }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $urlCotizacion = URL::to('/admin/cotizaciones/' . $this->cotizacion->id);

        return (new MailMessage)
            ->subject('Nueva Cotización Solicitada - #' . $this->cotizacion->id)
            ->view('emails.cotizacion-creada', [
                'notifiable' => $notifiable,
                'cotizacion' => $this->cotizacion,
                'solicitante' => $this->solicitante,
                'moduloOrigen' => $this->moduloOrigen,
                'urlCotizacion' => $urlCotizacion,
            ]);
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->getPayloadCotizacion());
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->getPayloadCotizacion();
    }

    /**
     * Determine which queues should be used for each notification channel.
     */
    public function viaQueues(): array
    {
        return [
            'mail' => 'notifications',
            'database' => 'default',
            'broadcast' => 'broadcast',
        ];
    }


    private function getPayloadCotizacion()
    {
        return [
            'tipo' => 'Cotizaciones',
            'titulo' => 'Nueva Cotización',
            'mensaje' => 'Se ha creado una nueva cotización #' . $this->cotizacion->id,
            'icono' => '',
            'data' => [
                'id' => $this->cotizacion->id,
                'productos_count' => $this->cotizacion->detalles->count(),
                'total' => $this->cotizacion->total,
                'fecha_creacion' => $this->cotizacion->fecha_cotizacion->format('Y-m-d'),
                'fecha_vencimiento' => $this->cotizacion->fecha_vencimiento->format('Y-m-d'),
                'solicitante' => [
                    'name' => $this->solicitante->name,
                    'email' => $this->solicitante->email,
                ],
            ],
            // 'url' => URL::to(config('services.frontend.url') . '/pages/proveedor/cotizacion/' . $this->cotizacion->id . '/view'),
            'url' => URL::to('/pages/proveedor/cotizacion/' . $this->cotizacion->id . '/view'),
            'modulo_origen' => $this->moduloOrigen,
            'timestamp' => now()->toISOString(),
        ];
    }
}
