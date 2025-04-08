<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Proveedor",
 *     title="Proveedor",
 *     description="Modelo del proveedor",
 *     required={"razon_social", "nombre_comercial", "email"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="razon_social", type="string", example="Proveedor S.A."),
 *     @OA\Property(property="nombre_comercial", type="string", example="ProveedorTech"),
 *     @OA\Property(property="email", type="string", example="proveedor@email.com"),
 *     @OA\Property(property="user_id", type="integer", example=3),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00Z"),
 *     @OA\Property(property="productos", type="array", @OA\Items(ref="#/components/schemas/Producto"))
 * )
 */
class Proveedor extends Model
{
    use HasFactory;
    protected $table = "proveedores";

    protected $fillable = [
        'user_id',
        'razon_social',
        'nombre_comercial',
        'email',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }
}

