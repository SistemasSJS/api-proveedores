<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


/**
 * @OA\Schema(
 *     schema="Sucursal",
 *     required={"nombre", "direccion", "proveedor_id"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nombre", type="string", example="Sucursal Culiacán"),
 *     @OA\Property(property="direccion", type="string", example="Av. Álvaro Obregón 1234"),
 *     @OA\Property(property="telefono", type="string", example="6671234567"),
 *     @OA\Property(property="correo", type="string", example="contacto@sucursal.com"),
 *     @OA\Property(property="proveedor_id", type="integer", example=1),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Sucursal extends BaseModel
{
    use HasFactory;
    protected $table = "sucursales";
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
