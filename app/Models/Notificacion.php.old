<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    protected $table = 'notificaciones';

    protected $fillable = [
        'tipo',
        'proveedor_id',
        'titulo',
        'mensaje',
        'data',
        'leida',
    ];

    protected $casts = [
        'data' => 'array',
        'leida' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con proveedor
     */
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    /**
     * Scope para notificaciones no leídas
     */
    public function scopeNoLeidas($query)
    {
        return $query->where('leida', false);
    }

    /**
     * Scope para notificaciones de un proveedor específico
     */
    public function scopeDeProveedor($query, $proveedorId)
    {
        return $query->where('proveedor_id', $proveedorId);
    }
}
