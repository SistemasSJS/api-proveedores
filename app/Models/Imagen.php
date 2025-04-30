<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Imagen",
 *     required={"url", "producto_id"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="producto_id", type="integer", example=1),
 *     @OA\Property(property="url", type="string", format="url", example="https://miapp.com/storage/productos/1.jpg"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Imagen extends BaseModel
{
    use HasFactory;
    protected $table = "imagenes";


    protected $fillable = ['url', 'producto_id'];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
