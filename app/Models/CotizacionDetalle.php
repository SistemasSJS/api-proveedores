<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CotizacionDetalle extends BaseModel
{
    /** @use HasFactory<\Database\Factories\CotizacionDetalleFactory> */
    use HasFactory;

    protected $table = 'cotizacion_detalles';

    protected $fillable = [
        'cotizacion_id',
        'producto_id',
        'cantidad_cotizada',
        'precio_unitario',
        'subtotal',
        'tiempo_entrega_dias',
        'observaciones',
    ];

    protected $casts = [
        'cantidad_cotizada' => 'integer',
        'precio_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tiempo_entrega_dias' => 'integer',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }


    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class);
    }

    public function requisicionDetalle(): BelongsTo
    {
        return $this->belongsTo(RequisicionDetalle::class);
    }
}
