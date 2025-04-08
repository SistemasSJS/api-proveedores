<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Producto",
 *     title="Producto",
 *     description="Modelo de producto que pertenece a un proveedor y puede estar en muchas sucursales",
 *     required={"nombre", "proveedor_id"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nombre", type="string", example="Producto A"),
 *     @OA\Property(property="email", type="string", example="contacto@producto.com"),
 *     @OA\Property(property="telefono", type="string", example="555-123-4567"),
 *     @OA\Property(property="direccion", type="string", example="Calle Falsa 123, Ciudad"),
 *     @OA\Property(property="proveedor_id", type="integer", example=2),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00Z")
 * )
 */
class Producto extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'email',
        'telefono',
        'direccion',
        'proveedor_id',
    ];

    /**
     * Relación muchos a muchos con sucursales
     */
    public function sucursales()
    {
        return $this->belongsToMany(Sucursal::class, 'producto_sucursal');
    }

    /**
     * Relación uno a muchos inversa con proveedor
     */
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }
}
