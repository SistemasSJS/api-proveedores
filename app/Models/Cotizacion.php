<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cotizacion extends BaseModel
{
    /** @use HasFactory<\Database\Factories\CotizacionFactory> */
    use Filterable, HasFactory;

    protected $table = 'cotizaciones';

    protected $fillable = [
        'proveedor_id',
        'fecha_cotizacion',
        'fecha_vencimiento',
        'total',
        'observaciones',
        'estatus',
    ];

    protected $casts = [
        'fecha_cotizacion' => 'datetime',
        'fecha_vencimiento' => 'date',
        'total' => 'decimal:2',
    ];

    /** ----------------
     * Filtros disponibles
     * ----------------- */
    protected static $filters = [
        'proveedor_id' => 'ProveedorId',
        'fecha_cotizacion' => 'FechaCotizacion',
        'fecha_vencimiento' => 'FechaVencimiento',
        'total' => 'Total',
        'estatus' => 'Estatus',
    ];

    /**
     * Relaciones para cargar con 'with'
     */
    public static function eagerLodable(): array
    {
        return [
            'proveedor',
            'detalles',
        ];
    }

    public function filterByProveedorId($query, $value)
    {
        return $query->whereIn('proveedor_id', explode(',', $value));
    }

    public function filterByFechaCotizacion($query, $value)
    {
        return $query->whereDate('fecha_cotizacion', $value);
    }

    public function filterByFechaVencimiento($query, $value)
    {
        return $query->whereDate('fecha_vencimiento', $value);
    }

    public function filterByTotal($query, $value)
    {
        return $query->where('total', $value);
    }

    public function filterByEstatus($query, $value)
    {
        return $query->where('estatus', $value);
    }

    /** ----------------
     * Relaciones
     * ----------------- */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(CotizacionDetalle::class);
    }

    /** ----------------
     * Scopes
     * ----------------- */
    public function scopeVigentes($query)
    {
        return $query->where('fecha_vencimiento', '>=', now()->toDateString());
    }

    public function scopeVencidas($query)
    {
        return $query->where('fecha_vencimiento', '<', now()->toDateString());
    }
}
