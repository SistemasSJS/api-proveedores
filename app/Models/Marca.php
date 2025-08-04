<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Marca extends BaseModel
{
    use HasFactory;

    protected static $filters = [
        'nombre' => 'nombre',
        'estatus' => 'estatus',
    ];

    protected $fillable = ['nombre', 'descripcion', 'logo', 'proveedor_id', 'activo'];

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    // public function lineas()
    // {
    //     return $this->hasMany(Linea::class);
    // }

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }

    public function scopeDelProveedor($query, $proveedorId)
    {
        return $query->where('proveedor_id', $proveedorId);
    }

    public function scopeFilterByNombre($query, $value)
    {
        return $query->where('nombre', 'like', "%$value%");
    }

    public function scopeFilterByEstatus($query, $value)
    {
        return $query->where('estatus', "%$value%");
    }
}
