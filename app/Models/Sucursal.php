<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Sucursal",
 *     title="Sucursal",
 *     description="Modelo de sucursal de un proveedor",
 *     required={"proveedor_id", "nombre", "direccion"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="proveedor_id", type="integer", example=2),
 *     @OA\Property(property="nombre", type="string", example="Sucursal Norte"),
 *     @OA\Property(property="direccion", type="string", example="Av. Reforma 123, CDMX"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00Z")
 * )
 */
class Sucursal extends Model
{
    use HasFactory;

    protected $fillable = [
        'proveedor_id',
        'nombre',
        'direccion',
    ];

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'producto_sucursal');
    }
}
 