<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notification;

/**
 * Modelo para gestionar los tipos de notificaciones
 * 
 * @property int $id
 * @property string $codigo
 * @property string $nombre
 * @property int $orden_importancia
 * @property string|null $descripcion
 * @property string|null $icono
 * @property string $color
 * @property array|null $canales
 * @property string|null $url_base
 * @property bool $estatus
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class TipoNotificacion extends Model
{
    use HasFactory;

    protected $table = 'tipos_notificacion';

    protected $fillable = [
        'codigo',
        'nombre',
        'orden_importancia',
        'descripcion',
        'icono',
        'color',
        'canales',
        'url_base',
        'estatus',
    ];

    protected $casts = [
        'canales' => 'array',
        'estatus' => 'boolean',
        'orden_importancia' => 'integer',
    ];

    /**
     * Scope para obtener solo los tipos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('estatus', true);
    }

    /**
     * Scope para ordenar por importancia
     */
    public function scopeOrdenadosPorImportancia($query)
    {
        return $query->orderBy('orden_importancia', 'asc');
    }

    /**
     * Relación con las notificaciones
     */
    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Obtener los canales como array, garantizando que database esté incluido
     */
    public function getCanalesToUseAttribute(): array
    {
        $canales = $this->canales ?? [];

        // Database es obligatorio
        if (!in_array('database', $canales)) {
            $canales[] = 'database';
        }

        return $canales;
    }

    /**
     * Verificar si un canal está habilitado
     */
    public function tieneCanalHabilitado(string $canal): bool
    {
        return in_array($canal, $this->canales_to_use);
    }

    /**
     * Generar URL completa basada en url_base y un ID
     */
    public function generarUrl(?int $entityId = null): ?string
    {
        if (!$this->url_base) {
            return null;
        }

        $url = $this->url_base;

        if ($entityId) {
            $url = rtrim($url, '/') . '/' . $entityId;
        }

        return $url;
    }

    /**
     * Obtener configuración para el frontend
     */
    public function toFrontendArray(): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'icono' => $this->icono ?: 'notifications-outline',
            'color' => $this->color,
            'orden_importancia' => $this->orden_importancia,
        ];
    }
}
