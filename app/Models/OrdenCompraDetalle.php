<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdenCompraDetalle extends BaseModel
{
    use HasFactory;

    protected $table = 'ordenes_compra_detalles';

    protected $fillable = [
        'orden_compra_id',
        'producto',
        'descripcion',
        'cantidad',
        'unidad_medida',
        'precio_unitario',
        'importe',
    ];

    protected $casts = [
        'cantidad' => 'decimal:3',
        'precio_unitario' => 'decimal:2',
        'importe' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /** ----------------
     * Relaciones
     * ----------------- */
    public function ordenCompra(): BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class);
    }

    /** ----------------
     * Métodos de negocio
     * ----------------- */
    public function calcularImporte(): float
    {
        return (float) ($this->cantidad * $this->precio_unitario);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($detalle) {
            $detalle->importe = $detalle->calcularImporte();
        });
    }
}
