<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmpresaConstrucc extends Model
{
    use HasFactory;
    
    /**
     * Nombre de la tabla
     */
    protected $table = 'empresa_construcc';
    
    /**
     * Los atributos que se pueden asignar masivamente.
     */
    protected $fillable = [
        'nombre',
        'rfc',
        'razon_social',
        'direccion',
        'ciudad',
        'estado',
        'codigo_postal',
        'telefono',
        'email',
        'representante_legal',
        'activo',
    ];
    
    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     */
    protected $casts = [
        'activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    /**
     * Scopes
     */
    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }
    
    public function scopePorNombre($query, $nombre)
    {
        return $query->where('nombre', 'LIKE', "%{$nombre}%")
                    ->orWhere('razon_social', 'LIKE', "%{$nombre}%")
                    ->orWhere('rfc', 'LIKE', "%{$nombre}%");
    }
    
    /**
     * Relaciones
     */
    public function solicitudesPago(): HasMany
    {
        return $this->hasMany(SolicitudPago::class, 'empresa_construcc_id');
    }
    
    /**
     * Accessors
     */
    public function getNombreCompletoAttribute(): string
    {
        return $this->razon_social ?: $this->nombre;
    }
    
    /**
     * Métodos de búsqueda
     */
    public static function buscar(string $termino)
    {
        return static::where(function ($query) use ($termino) {
            $query->where('nombre', 'LIKE', "%{$termino}%")
                  ->orWhere('razon_social', 'LIKE', "%{$termino}%")
                  ->orWhere('rfc', 'LIKE', "%{$termino}%");
        })->activo();
    }
}
