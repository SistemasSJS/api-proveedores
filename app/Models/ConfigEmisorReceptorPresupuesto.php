<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConfigEmisorReceptorPresupuesto extends BaseModel
{
    use Filterable;

    protected $connection = 'mysql5';

    public $timestamps = true;

    protected $fillable = [
        'proveedor_id',
        'tipo', // manejar tipo 1: emisor, 2: receptor
        'nombre',
        'apellido',
        'puesto',
        'file_firma',
        'estado', // 1: activo, 2: inactivo, 3: default
    ];

    // para los tipos de emisor/receptor crear enumerado y en cast convertir string
    protected $casts = [
        'proveedor_id' => 'integer',
        'tipo' => 'integer',
        'estado' => 'integer',
    ];

    const ESTADO_ACTIVO = 1;
    const ESTADO_INACTIVO = 2;
    const ESTADO_DEFAULT = 3;
    
    const TIPO_EMISOR = 1;
    const TIPO_RECEPTOR = 2;

    protected $hidden = ['created_at', 'updated_at'];

    protected static $filters = [
        'proveedor_id' => 'ProveedorId',
        'tipo' => 'Tipo',
        'estado' => 'Estado',
        'search' => 'Search',
    ];

    /**
     * Relaciones para carga eager estándar.
     *
     * @return array<int, string>
     */
    public static function eagerLodable(): array
    {
        return [
            'proveedor'
        ];
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function filterByProveedorId($query, $value)
    {
        return $query->whereIn('proveedor_id', explode(',', (string) $value));
    }

    public function filterByTipo($query, string $value)
    {
        return $query->where('tipo', $value);
    }

    public function filterByEstado($query, string $value)
    {
        return $query->where('estado', $value);
    }

    public function filterBySearch($query, string $value)
    {
        return $query->where(function ($q) use ($value) {
            $q->where('nombre', 'like', "%{$value}%")
                ->orWhere('apellido', 'like', "%{$value}%")
                ->orWhere('puesto', 'like', "%{$value}%");
        });
    }
}
