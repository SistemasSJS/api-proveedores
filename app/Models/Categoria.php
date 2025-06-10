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
    protected $fillable = [
        'nombre',
        'descripcion',
        'photo_path',
        'categoria_padre_id'
    ];

    protected static $filters = [
        'nombre' => 'nombre',
        'descripcion' => 'descripcion',
        'estatus' => 'estatus',
    ];


    /**
     * Productos asignados a la categoria
     */
    public function productos()
    {
        return $this->belongsToMany(Producto::class);
    }

    /**
     * Categoiria padre
     */
    public function padre()
    {
        return $this->belongsTo(Categoria::class, 'categoria_padre_id');
    }

    /**
     * SubCategoiria de la categoria
     */
    public function hijos()
    {
        return $this->hasMany(Categoria::class, 'categoria_padre_id');
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
