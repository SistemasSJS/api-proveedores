<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class ProductoEspecificacion extends BaseModel
{
  use HasFactory;

  protected $table = 'producto_especificaciones';

  protected $fillable = [
    'producto_id',
    'atributo',
    'valor',
    'unidad',
    'orden',
  ];

  /**
   * Relación inversa hacia Producto.
   */
  public function producto()
  {
    return $this->belongsTo(Producto::class);
  }
}
