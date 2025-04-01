<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sucursal extends Model
{
    use HasFactory;

    protected $fillable = [
        'proveedor_id',
        'nombre',
        'direccion',
    ];

    // Relación con el modelo Proveedor
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }
}
