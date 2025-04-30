<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


/**
 * @OA\Schema(
 *     schema="Producto",
 *     required={"nombre", "proveedor_id"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nombre", type="string", example="Cemento gris 50kg"),
 *     @OA\Property(property="descripcion", type="string", example="Saco de cemento gris para construcción"),
 *     @OA\Property(property="codigo_interno", type="string", example="CMG-50"),
 *     @OA\Property(property="precio_unitario", type="number", format="float", example=180.5),
 *     @OA\Property(property="disponible", type="boolean", example=true),
 *     @OA\Property(property="proveedor_id", type="integer", example=2),
 *     @OA\Property(property="unidad_medida_id", type="integer", example=1),
 *     @OA\Property(property="grupo_id", type="integer", example=1),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Producto extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'proveedor_id',
        'nombre',
        'descripcion',
        'codigo_interno',
        'precio_unitario',
        'disponible',
        'unidad_medida_id',
        'grupo_id'
    ];

    protected $casts = [
        'precio_unitario' => 'float',
        'disponible' => 'boolean',
    ];


    protected $hidden = [
        'precio_unitario',
        'disponible',
    ];

    // Filtros disponibles para este modelo
    protected static $filters = [
        'nombre' => 'nombre',
        'precio_unitario' => 'precio_unitario',
        'disponible' => 'disponible',
    ];


    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function unidad_medida()
    {
        return $this->belongsTo(UnidadMedida::class);
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class);
    }

    public function imagenes()
    {
        return $this->hasMany(Imagen::class);
    }


    // Filtro específico para 'nombre'
    public function filterByNombre($query, $value)
    {
        return $query->where('nombre', 'like', "%$value%");
    }

    // Filtro específico para 'precio_unitario'
    public function filterByPrecioUnitario($query, $value)
    {
        return $query->where('precio_unitario', '<=', $value);
    }

    // Filtro específico para 'disponible'
    public function filterByDisponible($query, $value)
    {
        return $query->where('disponible', $value);
    }
}
