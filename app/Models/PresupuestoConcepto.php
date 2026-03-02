<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresupuestoConcepto extends BaseModel
{
    use HasFactory;

    protected $table = 'presupuesto_conceptos';

    protected $fillable = [
        'presupuesto_id',
        'numero',
        'descripcion',
        'cantidad',
        'unidad',
        'precio_unitario',
        'precio_total',
    ];

    protected $casts = [
        'cantidad' => 'decimal:4',
        'precio_unitario' => 'decimal:2',
        'precio_total' => 'decimal:2',
    ];

    /**
     * Relación con presupuesto padre.
     */
    public function presupuesto(): BelongsTo
    {
        return $this->belongsTo(Presupuesto::class);
    }

    /**
     * Calcula y asigna el importe total del concepto.
     */
    public function calcularImporte(): void
    {
        $cantidad = (float) $this->cantidad;
        $precioUnitario = (float) $this->precio_unitario;
        $this->precio_total = round($cantidad * $precioUnitario, 2);
    }
}

