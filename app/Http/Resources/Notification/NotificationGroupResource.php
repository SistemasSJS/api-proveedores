<?php

namespace App\Http\Resources\Notification;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Recurso para agrupar notificaciones por tipo
 * 
 * Esta clase transforma las notificaciones agrupadas por tipo en una estructura
 * adecuada para el frontend, incluyendo información del tipo y las notificaciones asociadas.
 */
class NotificationGroupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $tipoNotificacion = $this->tipoNotificacion;
        
        return [
            'tipo' => [
                'id' => $tipoNotificacion->id,
                'codigo' => $tipoNotificacion->codigo,
                'nombre' => $tipoNotificacion->nombre,
                'descripcion' => $tipoNotificacion->descripcion,
                'icono' => $tipoNotificacion->icono ?: 'notifications-outline',
                'color' => $tipoNotificacion->color,
                'orden_importancia' => $tipoNotificacion->orden_importancia,
            ],
            'total_notificaciones' => $this->total_notificaciones,
            'no_leidas' => $this->no_leidas,
            'recientes' => $this->recientes,
            'notificaciones' => NotificationItemResource::collection($this->notificaciones),
        ];
    }
}
