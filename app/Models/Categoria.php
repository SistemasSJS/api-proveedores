<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Categoria",
 *     required={"nombre"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nombre", type="string", example="Herramientas Eléctricas"),
 *     @OA\Property(property="descripcion", type="text", example="Herramientas Eléctricas"),
 *     @OA\Property(property="photo_path", type="string", example="Herramientas Eléctricas"),
 *     @OA\Property(property="categoria_padre_id", type="integer", example="Herramientas Eléctricas"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Categoria extends BaseModel
{
    use HasFactory;

    protected static $filters = [
        'nombre' => 'nombre',
        'descripcion' => 'descripcion',
        'estatus' => 'estatus',
    ];

    protected $fillable = ['nombre', 'descripcion', 'parent_id', 'nivel', 'proveedor_id', 'activo'];

    public function parent()
    {
        return $this->belongsTo(Categoria::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Categoria::class, 'parent_id');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }

    // Scope para multi-tenant
    public function scopeDelProveedor($query, $proveedorId)
    {
        return $query->where('proveedor_id', $proveedorId);
    }

    public function scopeFilterByNombre($query, $value)
    {
        return $query->where('nombre', 'like', "%$value%");
    }

    public function scopeFilterByEstatus($query, $value)
    {
        return $query->where('estatus', "%$value%");
    }

    public function scopeFilterByDescripcion($query, $value)
    {
        return $query->where('descripcion', "%$value%");
    }
}
