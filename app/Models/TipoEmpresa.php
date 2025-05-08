<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="TipoEmpresa",
 *     required={"nombre"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nombre", type="string", example="Pequeña"),
 *     @OA\Property(property="estatus", type="string", example="activo"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class TipoEmpresa extends BaseModel
{
    use HasFactory;
    protected $table = "tipos_empresa";


    protected $fillable = ['nombre', 'estatus'];

    // Filtros disponibles para este modelo
    protected static $filters = [
        'nombre' => 'nombre',
        'estatus' => 'estatus',
    ];

    // Filtro específico para 'nombre'
    public function scopeFilterByNombre($query, $value)
    {
        return $query->where('nombre', 'like', "%$value%");
    }

    // Filtro específico para 'estatus'
    public function scopeFilterByEstatus($query, $value)
    {
        return $query->where('estatus', $value);
    }
}
