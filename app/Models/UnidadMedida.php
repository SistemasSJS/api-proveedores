<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


/**
 * @OA\Schema(
 *     schema="UnidadMedida",
 *     required={"nombre"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nombre", type="string", example="Kilogramo"),
 *     @OA\Property(property="descripcion", type="string", example="Unidad de peso equivalente a mil gramos"),
 *     @OA\Property(property="estatus", type="string", example="activo"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class UnidadMedida extends BaseModel
{
    use HasFactory;

    protected $fillable = ['nombre', 'descripcion', 'estatus'];

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }
}
