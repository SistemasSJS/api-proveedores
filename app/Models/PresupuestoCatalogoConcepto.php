<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresupuestoCatalogoConcepto extends BaseModel
{
    public const CATEGORIA_PRODUCTO = 'producto';

    public const CATEGORIA_SERVICIO = 'servicio';

    public const DESCRIPCION_MAX = 500;

    protected $table = 'presupuesto_catalogo_conceptos';

    protected static $filters = [
        'proveedor_id' => 'ProveedorId',
        'categoria' => 'Categoria',
        'search' => 'Search',
    ];

    protected $fillable = [
        'proveedor_id',
        'descripcion',
        'categoria',
        'unidad',
        'precio_unitario',
        'imagen_path',
    ];

    protected $casts = [
        'precio_unitario' => 'decimal:2',
    ];

    /**
     * Proveedor dueño del catálogo.
     */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    /**
     * Scope por proveedor.
     */
    public function scopeByProveedor($query, int $proveedorId)
    {
        return $query->where('proveedor_id', $proveedorId);
    }

    /**
     * Filtro por proveedor.
     */
    public function filterByProveedorId($query, $value)
    {
        return $query->whereIn('proveedor_id', explode(',', (string) $value));
    }

    /**
     * Filtro por categoría (producto|servicio).
     */
    public function filterByCategoria($query, string $value)
    {
        return $query->where('categoria', $value);
    }

    /**
     * Búsqueda general en descripción / unidad / id.
     */
    public function filterBySearch($query, string $value)
    {
        $numericId = ctype_digit($value) ? (int) $value : null;

        return $query->where(function ($q) use ($value, $numericId) {
            $q->where('descripcion', 'like', "%{$value}%")
                ->orWhere('unidad', 'like', "%{$value}%");
            if ($numericId !== null) {
                $q->orWhere('id', $numericId);
            }
        });
    }

    /**
     * @return list<string>
     */
    public static function categoriasValidas(): array
    {
        return [
            self::CATEGORIA_PRODUCTO,
            self::CATEGORIA_SERVICIO,
        ];
    }
}
