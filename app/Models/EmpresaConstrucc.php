<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmpresaConstrucc extends Model
{
    use HasFactory, Filterable;
    
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
        'proveedor_id',
        'activo',
    ];
    
    /**
     * Filtros disponibles
     */
    protected static $filters = [
        'nombre' => 'Nombre',
        'rfc' => 'Rfc',
        'razon_social' => 'RazonSocial',
        'ciudad' => 'Ciudad',
        'estado' => 'Estado',
        'proveedor_id' => 'ProveedorId',
        'activo' => 'Activo',
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
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }
    
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
    
    /**
     * Filtros
     */
    public function filterByNombre($query, $value)
    {
        return $query->where('nombre', 'like', "%$value%");
    }
    
    public function filterByRfc($query, $value)
    {
        return $query->where('rfc', 'like', "%$value%");
    }
    
    public function filterByRazonSocial($query, $value)
    {
        return $query->where('razon_social', 'like', "%$value%");
    }
    
    public function filterByCiudad($query, $value)
    {
        return $query->where('ciudad', 'like', "%$value%");
    }
    
    public function filterByEstado($query, $value)
    {
        return $query->where('estado', 'like', "%$value%");
    }
    
    public function filterByProveedorId($query, $value)
    {
        return $query->whereIn('proveedor_id', explode(',', $value));
    }
    
    public function filterByActivo($query, $value)
    {
        return $query->where('activo', $value);
    }
}
