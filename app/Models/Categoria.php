<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends BaseModel
{
    use HasFactory;

    protected static $filters = [
        'nombre' => 'nombre',
        'descripcion' => 'descripcion',
        'estatus' => 'estatus',
    ];

    protected $fillable = ['nombre', 'descripcion', 'parent_id', 'nivel', 'proveedor_id', 'activo'];

    // Relación con la categoría padre (si existe)
    public function parent()
    {
        return $this->belongsTo(Categoria::class, 'parent_id');
    }

    // Relación con las categorías hijas
    public function children()
    {
        return $this->hasMany(Categoria::class, 'parent_id');
    }

    // Relación con el proveedor
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    // Relación con los productos
    public function productos()
    {
        return $this->hasMany(Producto::class);
    }

    // Scope para filtrar por proveedor
    public function scopeDelProveedor($query, $proveedorId)
    {
        return $query->where('proveedor_id', $proveedorId);
    }

    // Scope para filtrar por nombre
    public function scopeFilterByNombre($query, $value)
    {
        return $query->where('nombre', 'like', "%$value%");
    }

    // Scope para filtrar por estatus
    public function scopeFilterByEstatus($query, $value)
    {
        return $query->where('estatus', 'like', "%$value%");
    }

    // Scope para filtrar por descripción
    public function scopeFilterByDescripcion($query, $value)
    {
        return $query->where('descripcion', 'like', "%$value%");
    }
}
