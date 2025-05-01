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
 *     @OA\Property(property="nivel", type="integer", example=2),
 *     @OA\Property(property="padre_id", type="integer", nullable=true, example=1),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Categoria extends BaseModel
{
    use HasFactory;
    protected $fillable = ['nombre', 'nivel', 'padre_id'];

    protected static $filters = [
        'nombre' => 'nombre',
        'nivel' => 'nivel',
    ];

    public function filterByNombre($query, $value)
    {
        return $query->where('nombre', 'like', "%$value%");
    }

    public function filterByNivel($query, $value)
    {
        return $query->where('nivel', $value);
    }

    public function padre()
    {
        return $this->belongsTo(Categoria::class, 'padre_id');
    }

    public function subcategorias()
    {
        return $this->hasMany(Categoria::class, 'padre_id');
    }
}
