<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Categoria",
 *     required={"nombre", "nivel"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nombre", type="string", example="Herramientas Eléctricas"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Categoria extends BaseModel
{
    use HasFactory;
    protected $fillable = ['nombre', 'descripcion', 'photo_path'];

    protected static $filters = [
        'nombre' => 'nombre',
        'descripcion' => 'descripcion',
        'estatus' => 'estatus',
    ];

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

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'categoria_productro');
    }
}
