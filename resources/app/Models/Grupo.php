<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Grupo",
 *     required={"nombre"},
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

    // Filtros disponibles para este modelo
    protected static $filters = [
        'nombre' => 'nombre',
        'estatus' => 'estatus',
    ];

    // Filtro específico para 'nombre'
    public function filterByNombre($query, $value)
    {
        return $query->where('nombre', 'like', "%$value%");
    }

    // Filtro específico para 'estatus'
    public function filterByEstatus($query, $value)
    {
        return $query->where('estatus', $value);
    }
}
