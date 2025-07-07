<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class Sucursal extends BaseModel
{
    use HasFactory;

    protected $table = 'sucursales';

    protected $fillable = [
        'proveedor_id',
        'nombre',
        'direccion',
        'telefono',
        'email',
        'encargado',
        'activa',
        'coordenadas_lat',
        'coordenadas_lng',
        'estatus'
    ];
    protected $casts = [
        'activa' => 'boolean',
        'coordenadas_lat' => 'float',
        'coordenadas_lng' => 'float',
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected static $filters = [
        'sku' => 'sku',
        'nombre' => 'nombre',
        'descripcion' => 'descripcion',
        'modelo' => 'modelo',
    ];



    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function productos(): BelongsToMany
    {
        return $this->belongsToMany(Producto::class)
            ->withPivot('stock_local', 'precio_local', 'activo')
            ->withTimestamps();
    }

    public function scopeActivas($query)
    {
        return $query->where('activa', true);
    }

    public function scopeDelProveedor($query, $proveedorId)
    {
        return $query->where('proveedor_id', $proveedorId);
    }
}
