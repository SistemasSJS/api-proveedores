<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductoImagen extends BaseModel
{
    // Si usas plural, Laravel detecta automáticamente 'producto_imagenes', pero si quieres asegurarte:
    protected $table = 'producto_imagenes';

    protected $fillable = [
        'producto_id',
        'url_imagen',
        'alt_text',
        'orden',
        'es_principal',
    ];

    protected $casts = [
        'es_principal' => 'boolean',
        'orden' => 'integer',
    ];

    /**
     * Relación inversa hacia Producto.
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
