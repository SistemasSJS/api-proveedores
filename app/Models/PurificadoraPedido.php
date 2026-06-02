<?php

namespace App\Models;

class PurificadoraPedido extends BaseModel
{
    protected $table = 'purificadora_pedidos';

    protected $fillable = [
        'nombre',
        'celular',
        'correo',
        'calle',
        'numero',
        'colonia',
        'codigo_postal',
        'municipio',
    ];
}
