<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'email', 'telefono', 'direccion'];

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }
}
