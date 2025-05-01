<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @OA\Schema(
 *     schema="Linea",
 *     required={"nombre", "marca_id"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nombre", type="string", example="Industrial"),
 *     @OA\Property(property="marca_id", type="integer", example=1),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Linea extends BaseModel
{
    use HasFactory;
    protected $fillable = ['nombre', 'marca_id'];

    protected static $filters = [
        'nombre' => 'nombre',
        'marca_id' => 'marca_id',
    ];

    public function filterByNombre($query, $value)
    {
        return $query->where('nombre', 'like', "%$value%");
    }

    public function filterByMarcaId($query, $value)
    {
        return $query->where('marca_id', $value);
    }

    public function marca()
    {
        return $this->belongsTo(Marca::class);
    }
}
