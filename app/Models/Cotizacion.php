<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cotizacion extends BaseModel
{
    /** @use HasFactory<\Database\Factories\CotizacionFactory> */
    use HasFactory;

    protected $table = 'cotizaciones';

    protected $fillable = [
        'requisicion_id',
        'fecha_cotizacion',
        'fecha_vencimiento',
        'total',
        'observaciones',
    ];

    protected $casts = [
        'fecha_cotizacion' => 'datetime',
        'fecha_vencimiento' => 'date',
        'total' => 'decimal:2',
    ];

    public function requisicion(): BelongsTo
    {
        return $this->belongsTo(Requisicion::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(CotizacionDetalle::class);
    }

    public function scopeVigentes($query)
    {
        return $query->where('fecha_vencimiento', '>=', now()->toDateString());
    }

    public function scopeVencidas($query)
    {
        return $query->where('fecha_vencimiento', '<', now()->toDateString());
    }
}
