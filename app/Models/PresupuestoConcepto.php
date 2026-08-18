<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresupuestoConcepto extends BaseModel
{
    use HasFactory;

    public const TIPO_CONCEPTO = 'concepto';

    public const TIPO_PARRAFO = 'parrafo';

    /** Longitud máxima del texto de un párrafo (~5 renglones en el PDF). */
    public const DESCRIPCION_PARRAFO_MAX = 500;

    /** Altura fija de la fila párrafo en plantillas PDF (mm). */
    public const ALTURA_FILA_PARRAFO_PDF_MM = 22.0;

    protected $table = 'presupuesto_conceptos';

    protected $fillable = [
        'presupuesto_id',
        'numero',
        'tipo',
        'descripcion',
        'cantidad',
        'unidad',
        'precio_unitario',
        'precio_total',
        'imagen_path',
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
        if ($this->esParrafo()) {
            $this->precio_total = 0;

            return;
        }

        $cantidad = (float) $this->cantidad;
        $precioUnitario = (float) $this->precio_unitario;
        $this->precio_total = round($cantidad * $precioUnitario, 2);
    }

    public function esParrafo(): bool
    {
        return ($this->tipo ?? self::TIPO_CONCEPTO) === self::TIPO_PARRAFO;
    }
}

