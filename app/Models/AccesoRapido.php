<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccesoRapido extends BaseModel
{
    use HasFactory;

    protected $table = 'accesos_rapidos';

    protected $fillable = [
        'titulo',
        'descripcion',
        'icono',
        'url',
        'color',
        'orden',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer'
    ];
}
