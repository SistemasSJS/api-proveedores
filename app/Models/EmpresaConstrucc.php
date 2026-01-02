<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class EmpresaConstrucc extends Model
{
    use Filterable, HasFactory;

    /**
     * Tabla asociada
     */
    protected $table = 'empresa_construcc';

    /**
     * Atributos asignables
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

        // Consecutivo interno para Solicitudes de Pago
        'consecutivo_sp',
    ];

    /**
     * Filtros dinámicos
     */
    protected static $filters = [
        'nombre' => 'Nombre',
        'rfc' => 'Rfc',
        'razon_social' => 'RazonSocial',
        'ciudad' => 'Ciudad',
        'estado' => 'Estado',
        'proveedor_id' => 'ProveedorId',
        'proveedores' => 'Proveedores',
        'activo' => 'Activo',
        'search' => 'Search',
    ];

    /**
     * Casts
     */
    protected $casts = [
        'activo' => 'boolean',
        'consecutivo_sp' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /* -----------------------------------------------------------------
     | Scopes
     |------------------------------------------------------------------*/

    /**
     * Solo empresas activas
     */
    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Búsqueda general
     */
    public function scopeSearch($query, ?string $termino)
    {
        if (! $termino) {
            return $query;
        }

        return $query->where(function ($q) use ($termino) {
            $q->where('nombre', 'like', "%{$termino}%")
                ->orWhere('razon_social', 'like', "%{$termino}%")
                ->orWhere('rfc', 'like', "%{$termino}%");
        });
    }

    /* -----------------------------------------------------------------
     | Relaciones
     |------------------------------------------------------------------*/

    /**
     * Solicitudes de Pago de la empresa
     */
    public function solicitudesPago(): HasMany
    {
        return $this->hasMany(SolicitudPago::class, 'empresa_construcc_id');
    }

    /**
     * Proveedores asociados (pivot)
     */
    public function proveedores()
    {
        return $this->belongsToMany(
            Proveedor::class,
            'empresa_construcc_proveedor'
        );
    }

    /* -----------------------------------------------------------------
     | Accessors
     |------------------------------------------------------------------*/

    /**
     * Nombre completo (razón social o nombre)
     */
    public function getNombreCompletoAttribute(): string
    {
        return $this->razon_social ?: $this->nombre;
    }

    /* -----------------------------------------------------------------
     | Solicitudes de Pago – Consecutivo y Folio
     |------------------------------------------------------------------*/

    /**
     * Incrementa y devuelve el siguiente consecutivo de SP
     * 🔒 Seguro ante concurrencia
     */
    public function obtenerConsecutivoSiguienteSP(): int
    {
        return DB::transaction(function () {
            $this->refresh();
            $folioSiguiente = $this->consecutivo_sp;
            $this->consecutivo_sp = ($this->consecutivo_sp ?? 0) + 1;
            $this->save();

            return $folioSiguiente;
        });
    }

    /**
     * Obtiene el folio formateado de la siguiente SP
     *
     * Formato: 0001, 0002, 0003...
     */
    public function obtenerFolioSiguienteSP(): string
    {
        $consecutivo = $this->obtenerConsecutivoSiguienteSP();

        return str_pad($consecutivo, 4, '0', STR_PAD_LEFT);
    }

    /* -----------------------------------------------------------------
     | Eager loading permitido
     |------------------------------------------------------------------*/

    public static function eagerLodable(): array
    {
        return [
            'proveedores',
            'solicitudesPago',
        ];
    }
}
