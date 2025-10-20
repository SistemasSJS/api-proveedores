<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @OA\Schema(
 *     schema="Grupo",
 *     required={"nombre"},
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nombre", type="string", example="Cementos"),
 *     @OA\Property(property="descripcion", type="string", example="Productos relacionados con cementos y derivados"),
 *     @OA\Property(property="estatus", type="string", example="activo"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Grupo extends BaseModel
{
    use HasFactory;

    protected $fillable = ['nombre', 'descripcion', 'estatus'];

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }
}
