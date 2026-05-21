<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresupuestoConcepto extends BaseModel
{
    use HasFactory;

    public const TIPO_CONCEPTO = 'concepto';

    public const TIPO_PARRAFO = 'parrafo';

    /** Longitud máxima del texto de un párrafo (~3 renglones en PDF). */
    public const DESCRIPCION_PARRAFO_MAX = 500;

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

    /**
     * Texto de párrafo en una sola línea: sin saltos de línea, tabulaciones ni caracteres de control.
     */
    public static function sanitizarDescripcionParrafo(string $descripcion): string
    {
        $text = preg_replace('/[\R\v\f\x{85}\x{2028}\x{2029}]+/u', ' ', $descripcion) ?? '';
        $text = preg_replace('/[\x{00}-\x{1F}\x{7F}-\x{9F}]/u', '', $text) ?? '';
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';

        $text = trim($text);
        if (mb_strlen($text) > self::DESCRIPCION_PARRAFO_MAX) {
            $text = mb_substr($text, 0, self::DESCRIPCION_PARRAFO_MAX);
        }

        return $text;
    }
}

