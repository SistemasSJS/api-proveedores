<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Role extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'estatus',
        'created_at',
        'update_at',
    ];

    /**
     * Filtros disponibles para construir consultas dinámicas sobre este modelo.
     * Utilizado por controladores o repositorios para permitir filtrado de datos
     * sin escribir condiciones manuales.
     */
    protected static $filters = [
        'nombre' => 'nombre',
        'estatus' => 'estatus',
        'created_at' => 'created_at',
        'update_at' => 'update_at',
    ];

    /**
     * Filter data mediante el nombre del role.
     */
    public function scopeFilterByNombre($query, $value)
    {
        return $query->where('nombre', 'like', "%$value%");
    }

    /**
     * Filtro específico para 'estatus'
     */
    public function scopeFilterByEstatus($query, $value)
    {
        return $query->where('estatus', $value);
    }

    /**
     * Relacion de usuarios con este rol.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
