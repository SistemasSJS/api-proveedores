<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OcConstrucc extends BaseModel
{
    use Filterable, HasFactory;

    protected $table = 'oc_construcc';

    protected $fillable = [
        'empresa_id',
        'proveedor_id',
        'orden_compra_id',
        'estatus',
    ];

    protected static $filters = [
        'orden_compra_id' => 'OrdenCompraId',
        'estatus' => 'Estatus',
        'proveedor_id' => 'ProveedorId',
        'empresa_id' => 'EmpresaId',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /** ----------------
     * Eager loading disponible
     * ----------------- */
    public static function eagerLodable(): array
    {
        return [
            // 'proveedor',
        ];
    }

    /** ----------------
     * Relaciones
     * ----------------- */
    // public function proveedor(): BelongsTo
    // {
    //     return $this->belongsTo(Proveedor::class, 'proveedor_id');
    // }

    /** ----------------
     * Scopes
     * ----------------- */
    public function scopePorEstatus(Builder $query, string $estatus): Builder
    {
        return $query->where('estatus', $estatus);
    }


    /** ----------------
     * Filtros
     * ----------------- */
    public function filterByOrdenCompraId($query, $value)
    {
        return $query->where('orden_compra_id', 'like', "%{$value}%");
    }

    public function filterByEstatus($query, $value)
    {
        return $query->where('estatus', $value);
    }

    public function filterByProveedorId($query, $value)
    {
        return $query->where('proveedor_id', $value);
    }

    public function filterByEmpresaId($query, $value)
    {
        return $query->where('empresa_id', $value);
    }
}
