<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Categoria extends BaseModel
{
    use HasFactory, Filterable;

    protected $fillable = [
        'nombre',
        'descripcion',
        'parent_id',
        'nivel',
        'proveedor_id',
        'activo',
        'estatus',
    ];

    protected static $filters = [
        'nombre'      => 'Nombre',
        'descripcion' => 'Descripcion',
        'estatus'     => 'Estatus',
        'proveedor_id' => 'ProveedorId',
    ];

    /** ----------------
     * Relaciones
     * ----------------- */

    // Categoría padre
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'parent_id');
    }

    // Categorías hijas
    public function children(): HasMany
    {
        return $this->hasMany(Categoria::class, 'parent_id');
    }

    // Productos principales (categoría padre)
    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'categoria_id');
    }

    // Productos de subcategoría
    public function productosSubcategoria(): HasMany
    {
        return $this->hasMany(Producto::class, 'subcategoria_id');
    }

    // Proveedor de la categoría
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    /** ----------------
     * Scopes de filtro
     * ----------------- */
    public function scopeFilterByNombre($query, $value)
    {
        return $query->when($value, fn($q) => $q->where('nombre', 'like', "%$value%"));
    }

    public function scopeFilterByDescripcion($query, $value)
    {
        return $query->when($value, fn($q) => $q->where('descripcion', 'like', "%$value%"));
    }

    public function scopeFilterByEstatus($query, $value)
    {
        return $query->when($value, fn($q) => $q->where('estatus', $value));
    }

    public function scopeDelProveedor($query, $proveedorId)
    {
        return $query->when($proveedorId, fn($q) => $q->where('proveedor_id', $proveedorId));
    }

    /** ----------------
     * Helpers
     * ----------------- */
    public function isPrincipal(): bool
    {
        return $this->nivel === 0;
    }

    public function isActive(): bool
    {
        return $this->activo;
    }
}
