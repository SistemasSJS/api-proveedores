<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    use HasFactory;
    protected $table = "proveedores";

    protected $fillable = [
        'user_id',
        'razon_social',
        'nombre_comercial',
        'email',
    ];

    // Relación con el modelo User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
