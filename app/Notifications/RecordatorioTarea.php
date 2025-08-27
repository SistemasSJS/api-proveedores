<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

/**
 * Notificación para recordar tareas pendientes o vencidas
 */
class RecordatorioTarea extends BaseNotification implements ShouldBroadcast
{
    protected string $nombreTarea;
    protected string $fechaVencimiento;
    protected string $prioridad;
    protected ?string $descripcion;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        string $nombreTarea,
        string $fechaVencimiento,
        string $prioridad = 'media',
        ?string $descripcion = null,
        ?int $tareaId = null
    ) {
        $this->nombreTarea = $nombreTarea;
        $this->fechaVencimiento = $fechaVencimiento;
        $this->prioridad = $prioridad;
        $this->descripcion = $descripcion;

        // Preparar datos para la notificación base
        $notificationData = [
            'tipo' => 'recordatorio_tarea',
            'nombre_tarea' => $nombreTarea,
            'fecha_vencimiento' => $fechaVencimiento,
            'prioridad' => $prioridad,
            'descripcion' => $descripcion,
        ];

        // Llamar al constructor padre con los datos
        parent::__construct($notificationData, $tareaId);
    }

    /**
     * Obtener el código del tipo de notificación
     */
    protected function getCodigoTipo(): string
    {
        return 'RECORDATORIO_TAREA';
    }

    /**
     * Obtener el título de la notificación
     */
    protected function getTitulo(): string
    {
        return 'Recordatorio: ' . $this->nombreTarea;
    }

    /**
     * Obtener el mensaje de la notificación
     */
    protected function getMensaje(): string
    {
        $mensaje = "Tienes una tarea pendiente: {$this->nombreTarea}";
        
        if ($this->fechaVencimiento) {
            $fechaVencimiento = \Carbon\Carbon::parse($this->fechaVencimiento);
            
            if ($fechaVencimiento->isPast()) {
                $mensaje .= " (Vencida desde {$fechaVencimiento->diffForHumans()})";
            } else {
                $mensaje .= " (Vence {$fechaVencimiento->diffForHumans()})";
            }
        }

        return $mensaje;
    }

    /**
     * Personalizar el mensaje de email
     */
    protected function customizeMailMessage(MailMessage $mailMessage, $notifiable): MailMessage
    {
        $mailMessage
            ->subject('Recordatorio de Tarea: ' . $this->nombreTarea)
            ->greeting('¡Hola!')
            ->line('Te recordamos que tienes una tarea pendiente:')
            ->line('**Tarea:** ' . $this->nombreTarea);

        if ($this->descripcion) {
            $mailMessage->line('**Descripción:** ' . $this->descripcion);
        }

        if ($this->fechaVencimiento) {
            $fechaVencimiento = \Carbon\Carbon::parse($this->fechaVencimiento);
            $mailMessage->line('**Fecha de vencimiento:** ' . $fechaVencimiento->format('d/m/Y H:i'));
            
            if ($fechaVencimiento->isPast()) {
                $mailMessage->line('⚠️ **Esta tarea está vencida**');
            }
        }

        $mailMessage->line('**Prioridad:** ' . ucfirst($this->prioridad));

        $url = $this->generarUrl();
        if ($url) {
            $mailMessage->action('Ver Tarea', $url);
        }

        $mailMessage->line('¡No olvides completar tus tareas pendientes!');

        return $mailMessage;
    }

    /**
     * Obtener datos adicionales para la notificación
     */
    protected function getAdditionalData(): array
    {
        return [
            'tarea' => [
                'nombre' => $this->nombreTarea,
                'fecha_vencimiento' => $this->fechaVencimiento,
                'prioridad' => $this->prioridad,
                'descripcion' => $this->descripcion,
                'esta_vencida' => $this->fechaVencimiento ? 
                    \Carbon\Carbon::parse($this->fechaVencimiento)->isPast() : false,
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
