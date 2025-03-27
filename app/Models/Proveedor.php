<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'precio', 'proveedor_id'];

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }
}
