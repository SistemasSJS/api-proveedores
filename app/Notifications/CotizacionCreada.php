<?php

namespace App\Notifications;

use App\Models\Cotizacion;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

/**
 * Notificación enviada al proveedor cuando se crea una nueva cotización
 * desde el módulo de construcción
 */
class CotizacionCreada extends BaseNotification implements ShouldBroadcast
{
    protected $cotizacion;
    protected $solicitante;
    protected string $moduloOrigen;

    /**
     * Create a new notification instance.
     */
    public function __construct($cotizacion, $solicitante, string $moduloOrigen = 'construccion')
    {
        $this->cotizacion = $cotizacion;
        $this->solicitante = $solicitante;
        $this->moduloOrigen = $moduloOrigen;

        // Preparar datos para la notificación base
        $notificationData = [
            'tipo' => 'cotizacion_creada',
            'cotizacion_id' => $cotizacion->id,
            'proveedor_id' => $cotizacion->proveedor_id,
            'fecha_cotizacion' => $cotizacion->fecha_cotizacion->format('Y-m-d'),
            'fecha_vencimiento' => $cotizacion->fecha_vencimiento->format('Y-m-d'),
            'total' => $cotizacion->total,
            'productos_count' => $cotizacion->detalles->count(),
            'solicitante' => [
                'id' => $solicitante->id,
                'name' => $solicitante->name,
                'email' => $solicitante->email,
            ],
            'modulo_origen' => $moduloOrigen,
        ];

        // Llamar al constructor padre con los datos
        parent::__construct($notificationData, $cotizacion->id);
    }

    /**
     * Obtener el código del tipo de notificación
     */
    protected function getCodigoTipo(): string
    {
        return 'COTIZACION_CREADA';
    }

    /**
     * Obtener el título de la notificación
     */
    protected function getTitulo(): string
    {
        return 'Nueva Cotización #' . $this->cotizacion->id;
    }

    /**
     * Obtener el mensaje de la notificación
     */
    protected function getMensaje(): string
    {
        return 'Se ha creado una nueva cotización desde ' . ucfirst($this->moduloOrigen) . ' solicitada por ' . $this->solicitante->name;
    }

    /**
     * Personalizar el mensaje de email
     */
    protected function customizeMailMessage(MailMessage $mailMessage, $notifiable): MailMessage
    {
        return $mailMessage
            ->subject('Nueva Cotización Solicitada - #' . $this->cotizacion->id)
            ->view('emails.cotizacion-creada', [
                'notifiable' => $notifiable,
                'cotizacion' => $this->cotizacion,
                'solicitante' => $this->solicitante,
                'moduloOrigen' => $this->moduloOrigen,
                'urlCotizacion' => $this->generarUrl(),
            ]);
    }

    /**
     * Obtener datos adicionales para la notificación
     */
    protected function getAdditionalData(): array
    {
        return [
            'cotizacion' => [
                'id' => $this->cotizacion->id,
                'fecha_cotizacion' => $this->cotizacion->fecha_cotizacion->format('Y-m-d'),
                'fecha_vencimiento' => $this->cotizacion->fecha_vencimiento->format('Y-m-d'),
                'total' => $this->cotizacion->total,
                'productos_count' => $this->cotizacion->detalles->count(),
            ],
        ];
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
}
