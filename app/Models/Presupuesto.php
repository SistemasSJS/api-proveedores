<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Presupuesto extends BaseModel
{
    use HasFactory;

    protected $table = 'presupuestos';

    protected $fillable = [
        'numero_presupuesto',
        'fecha_emision',
        'concepto_general',
        'subtotal',
        'con_iva',
        'iva_porcentaje',
        'iva_total',
        'total',
        'empresa_emisora_datos',
        'empresa_receptora_datos',
        'condiciones',
        'observaciones',
        'proveedor_id',
        'empresa_receptora_id',
        'user_id',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'con_iva' => 'boolean',
        'subtotal' => 'decimal:2',
        'iva_porcentaje' => 'decimal:2',
        'iva_total' => 'decimal:2',
        'total' => 'decimal:2',
        'empresa_emisora_datos' => 'array',
        'empresa_receptora_datos' => 'array',
        'condiciones' => 'array',
    ];

    /**
     * Relación con proveedor emisor.
     */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    /**
     * Relación con empresa receptora (también proveedor).
     */
    public function empresaReceptora(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'empresa_receptora_id');
    }

    /**
     * Usuario que registró el presupuesto.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Conceptos del presupuesto.
     */
    public function conceptos(): HasMany
    {
        return $this->hasMany(PresupuestoConcepto::class);
    }

    /**
     * Calcula subtotal, IVA y total con base en conceptos y configuración del IVA.
     */
    public function calcularTotales(): void
    {
        $this->recalcularDesdeConceptos();
    }

    /**
     * Recalcula el subtotal a partir de conceptos y luego aplica IVA.
     */
    public function recalcularDesdeConceptos(): void
    {
        $subtotal = $this->relationLoaded('conceptos')
            ? $this->conceptos->sum(fn (PresupuestoConcepto $concepto) => (float) $concepto->precio_total)
            : (float) $this->conceptos()->sum('precio_total');

        $this->subtotal = round($subtotal, 2);
        $this->aplicarIva();
    }

    /**
     * Aplica IVA según configuración actual (`con_iva` e `iva_porcentaje`).
     */
    public function aplicarIva(): void
    {
        $subtotal = (float) $this->subtotal;
        $porcentajeIva = (float) $this->iva_porcentaje;

        if ($this->con_iva) {
            $ivaTotal = round(($subtotal * $porcentajeIva) / 100, 2);
            $this->iva_total = $ivaTotal;
            $this->total = round($subtotal + $ivaTotal, 2);

            return;
        }

        $this->iva_total = 0;
        $this->total = round($subtotal, 2);
    }

    /**
     * Genera un número de presupuesto consecutivo por proveedor.
     */
    public static function generarNumeroPresupuesto(int $proveedorId): string
    {
        $prefix = 'PRES-' . now()->format('Ymd') . '-';

        $ultimo = static::query()
            ->where('proveedor_id', $proveedorId)
            ->orderByDesc('id')
            ->value('numero_presupuesto');

        $consecutivo = 1;
        if (is_string($ultimo) && preg_match('/(\d+)$/', $ultimo, $matches)) {
            $consecutivo = ((int) $matches[1]) + 1;
        }

        do {
            $numero = $prefix . str_pad((string) $consecutivo, 4, '0', STR_PAD_LEFT);
            $existe = static::query()
                ->where('proveedor_id', $proveedorId)
                ->where('numero_presupuesto', $numero)
                ->exists();
            $consecutivo++;
        } while ($existe);

        return $numero;
    }

    /**
     * Filtra por proveedor.
     */
    public function scopeByProveedor($query, int $proveedorId)
    {
        return $query->where('proveedor_id', $proveedorId);
    }

    /**
     * Filtra por usuario.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Filtra por rango de fechas de emisión.
     */
    public function scopeByFechaRango($query, ?string $desde, ?string $hasta)
    {
        return $query
            ->when($desde, fn ($q) => $q->whereDate('fecha_emision', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('fecha_emision', '<=', $hasta));
    }

    /**
     * Presupuestos con IVA.
     */
    public function scopeConIva($query)
    {
        return $query->where('con_iva', true);
    }

    /**
     * Presupuestos sin IVA.
     */
    public function scopeSinIva($query)
    {
        return $query->where('con_iva', false);
    }
}
