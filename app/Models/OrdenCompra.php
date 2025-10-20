<?php

namespace App\Models;

use App\Enums\EstadoOrdenCompra;
use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrdenCompra extends BaseModel
{
    use Filterable, HasFactory;

    protected $table = 'ordenes_compra';

    protected $fillable = [
        'numero_orden',
        'fecha_orden',
        'proveedor_id',
        'empresa_construcc_id',
        'importe_total',
        'estado',
        'fecha_aprobacion',
        'observaciones',
        'metadata_json',
        'monto_sp_asociado',
        'sp_count',
    ];

    protected static $filters = [
        'numero_orden' => 'NumeroOrden',
        'estado' => 'Estado',
        'proveedor_id' => 'ProveedorId',
        'empresa_construcc_id' => 'EmpresaConstruccId',
        'fecha_orden' => 'FechaOrden',
        'fecha_orden_desde' => 'FechaOrdenDesde',
        'fecha_orden_hasta' => 'FechaOrdenHasta',
        'importe_total' => 'ImporteTotal',
        'importe_desde' => 'ImporteDesde',
        'importe_hasta' => 'ImporteHasta',
    ];

    protected $casts = [
        'fecha_orden' => 'date',
        'fecha_aprobacion' => 'datetime',
        'importe_total' => 'decimal:2',
        'monto_sp_asociado' => 'decimal:2',
        'sp_count' => 'integer',
        'estado' => EstadoOrdenCompra::class,
        'metadata_json' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /** ----------------
     * Eager loading disponible
     * ----------------- */
    public static function eagerLodable(): array
    {
        return [
            'proveedor',
            'empresaConstrucc',
            'detalles',
            'solicitudesPago',
        ];
    }

    /** ----------------
     * Relaciones
     * ----------------- */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function empresaConstrucc(): BelongsTo
    {
        return $this->belongsTo(EmpresaConstrucc::class, 'empresa_construcc_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(OrdenCompraDetalle::class);
    }

    public function solicitudesPago(): HasMany
    {
        return $this->hasMany(SolicitudPago::class, 'referencia_oc', 'numero_orden');
    }

    /** ----------------
     * Scopes
     * ----------------- */
    public function scopeAprobadas(Builder $query): Builder
    {
        return $query->where('estado', EstadoOrdenCompra::APROBADA);
    }

    public function scopeConSolicitudesPago(Builder $query): Builder
    {
        return $query->where('sp_count', '>', 0);
    }

    public function scopeSinSolicitudesPago(Builder $query): Builder
    {
        return $query->where('sp_count', 0);
    }

    public function scopeDisponiblesParaConversion(Builder $query): Builder
    {
        return $query->where('estado', EstadoOrdenCompra::APROBADA)
            ->whereRaw('monto_sp_asociado < importe_total');
    }

    /** ----------------
     * Métodos de negocio
     * ----------------- */
    public function getMontoDisponible(): float
    {
        return (float) ($this->importe_total - $this->monto_sp_asociado);
    }

    public function tieneMontoDisponible(): bool
    {
        return $this->getMontoDisponible() > 0;
    }

    public function puedeGenerarSolicitudPago(): bool
    {
        return $this->estado === EstadoOrdenCompra::APROBADA && $this->tieneMontoDisponible();
    }

    public function getDiasSinSolicitudPago(): int
    {
        if ($this->sp_count > 0) {
            return 0;
        }

        $fechaBase = $this->fecha_aprobacion ?? $this->created_at;

        return $fechaBase->diffInDays(now());
    }

    public function getNivelAlerta(): ?string
    {
        $dias = $this->getDiasSinSolicitudPago();

        if ($dias >= 15) {
            return 'danger';
        }
        if ($dias >= 7) {
            return 'warning';
        }

        return null;
    }

    public function actualizarContadores(): void
    {
        $this->sp_count = $this->solicitudesPago()->count();
        $this->monto_sp_asociado = $this->solicitudesPago()->sum('monto_total');
        $this->save();
    }

    /** ----------------
     * Filtros
     * ----------------- */
    public function filterByNumeroOrden($query, $value)
    {
        return $query->where('numero_orden', 'like', "%{$value}%");
    }

    public function filterByEstado($query, $value)
    {
        return $query->where('estado', $value);
    }

    public function filterByFechaOrdenDesde($query, $value)
    {
        return $query->where('fecha_orden', '>=', $value);
    }

    public function filterByFechaOrdenHasta($query, $value)
    {
        return $query->where('fecha_orden', '<=', $value);
    }

    public function filterByImporteDesde($query, $value)
    {
        return $query->where('importe_total', '>=', $value);
    }

    public function filterByImporteHasta($query, $value)
    {
        return $query->where('importe_total', '<=', $value);
    }
}
