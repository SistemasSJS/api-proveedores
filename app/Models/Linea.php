<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;


class Linea extends BaseModel
{
    use HasFactory;
    protected $fillable = ['nombre', 'descripcion', 'marca_id', 'proveedor_id', 'activo'];

    protected static $filters = [
        'nombre' => 'nombre',
        'estatus' => 'estatus',
    ];

    public function scopeFilterByNombre($query, $value)
    {
        return $query->where('nombre', 'like', "%$value%");
    }

    public function scopeFilterByEstatus($query, $value)
    {
        return $query->where('estatus', "%$value%");
    }

    public function marca()
    {
        return $this->belongsTo(Marca::class);
    }
}
