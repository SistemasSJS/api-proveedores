<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @OA\Schema(
 *     schema="Linea",
 *     required={"nombre"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nombre", type="string", example="Industrial"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Linea extends BaseModel
{
    use HasFactory;
    protected $fillable = ['nombre'];

    protected static $filters = [
        'nombre' => 'nombre',
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

    public function marca()
    {
        return $this->belongsTo(Linea::class);
    }
}
