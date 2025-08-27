<?php

namespace App\Http\Resources\Notification;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Recurso para transformar items individuales de notificaciones
 * 
 * Esta clase transforma una notificación individual en la estructura
 * que espera el frontend, incluyendo todos los datos necesarios para mostrarla.
 */
class NotificationItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = json_decode($this->data, true) ?: [];
        
        return [
            'id' => $this->id,
            'titulo' => $data['titulo'] ?? '',
            'mensaje' => $data['mensaje'] ?? '',
            'icono' => $data['icono'] ?? 'notifications-outline',
            'color' => $data['color'] ?? 'primary',
            'url' => $data['url'] ?? null,
            'entity_id' => $data['entity_id'] ?? null,
            'leida' => !is_null($this->read_at),
            'created_at' => $this->created_at->toISOString(),
            'read_at' => $this->read_at?->toISOString(),
            'data' => [
                'tipo_notificacion_id' => $data['tipo_notificacion_id'] ?? null,
                'datos_especificos' => $data['data'] ?? [],
            ],
            // Información temporal útil para la UI
            'tiempo_transcurrido' => $this->created_at->diffForHumans(),
            'es_reciente' => $this->created_at->gt(now()->subHours(24)),
            'es_muy_reciente' => $this->created_at->gt(now()->subHours(1)),
        ];
    }
}
