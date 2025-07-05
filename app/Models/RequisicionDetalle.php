<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RequisicionDetalle extends BaseModel
{
    /** @use HasFactory<\Database\Factories\RequisicionDetalleFactory> */
    use HasFactory;

    protected $table = 'requisicion_productos';

    protected $fillable = [
        'requisicion_id',
        'producto_id',
        'cantidad',
        'precio_unitario',
        'subtotal',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'precio_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function requisicion(): BelongsTo
    {
        return $this->belongsTo(Requisicion::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function cotizacionDetalle(): HasOne
    {
        return $this->hasOne(CotizacionDetalle::class);
    }
}
