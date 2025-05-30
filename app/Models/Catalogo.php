<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Catalogo",
 *     required={"nombre", "proveedor_id"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nombre", type="string", example="Catálogo 2024"),
 *     @OA\Property(property="descripcion", type="string", nullable=true, example="Catálogo anual de Productos"),
 *     @OA\Property(property="proveedor_id", type="integer", example=1),
 *     @OA\Property(property="photo_url", type="string", nullable=true, example="https://dominio.com/storage/catalogos/2024.jpg"),
 *     @OA\Property(property="Proveedor", ref="#/components/schemas/Proveedor")
 * )
 */
class Catalogo extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'descripcion',
        'proveedor_id',
        'photo_path',

    ];

    protected static $filters = [
        'nombre' => 'nombre',
        'descripcion' => 'descripcion',
        'estatus' => 'estatus',
    ];


    /**
     * Define las relaciones permitidas para cargar con with() (eager loading).
     * Esto evita el problema N+1 y mejora el rendimiento de las consultas.
     *
     * @return string[]
     */
    public static function eagerLodable(): array
    {
        return [
            'proveedor',
            'productos'
        ];
    }

    public function scopeFilterByNombre($query, $value)
    {
        return $query->where('nombre', 'like', "%$value%");
    }

    public function scopeFilterByEstatus($query, $value)
    {
        return $query->where('estatus', "%$value%");
    }

    public function scopeFilterByDescripcion($query, $value)
    {
        return $query->where('descripcion', "%$value%");
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }
}
