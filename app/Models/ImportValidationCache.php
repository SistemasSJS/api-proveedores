<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportValidationCache extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'proveedor_id',
        'file_name',
        'total_rows',
        'validation_data',
        'expires_at',
    ];

    protected $casts = [
        'validation_data' => 'array',
        'expires_at' => 'datetime',
        'total_rows' => 'integer',
        'proveedor_id' => 'integer',
    ];

    /**
     * Relación con el proveedor
     */
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    /**
     * Scope para obtener solo los registros no expirados
     */
    public function scopeNotExpired($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope para obtener solo los registros expirados
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Verifica si el cache ha expirado
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Obtiene los datos de validación si no han expirado
     */
    public function getValidationDataAttribute($value)
    {
        if ($this->isExpired()) {
            return null;
        }

        return $this->castAttribute('validation_data', $value);
    }
}
