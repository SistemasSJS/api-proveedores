<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @OA\Schema(
 *     schema="Proveedor",
 *     title="Proveedor",
 *     description="Esquema del modelo Proveedor",
 *     required={"nombre_comercial", "razon_social", "rfc", "email", "telefono", "estado", "municipio", "codigo_postal", "contacto_nombre", "contacto_telefono", "contacto_email"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nombre_comercial", type="string", example="Materiales Rivera"),
 *     @OA\Property(property="razon_social", type="string", example="Materiales de Construcción Rivera S.A. de C.V."),
 *     @OA\Property(property="rfc", type="string", example="RIEM920313AB1"),
 *     @OA\Property(property="email", type="string", example="contacto@materialesrivera.com"),
 *     @OA\Property(property="telefono", type="string", example="6671234567"),
 *     @OA\Property(property="estado", type="string", example="Sinaloa"),
 *     @OA\Property(property="municipio", type="string", example="Culiacán"),
 *     @OA\Property(property="codigo_postal", type="string", example="80000"),
 *     @OA\Property(property="contacto_nombre", type="string", example="Juan Pérez"),
 *     @OA\Property(property="contacto_telefono", type="string", example="6677654321"),
 *     @OA\Property(property="contacto_email", type="string", example="juan.perez@materialesrivera.com"),
 *     @OA\Property(property="estatus", type="string", example="activo"),
 *     @OA\Property(property="notas", type="string", example="Proveedor confiable."),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-02T00:00:00Z")
 * )
 */
class Proveedor extends BaseModel
{
    use HasFactory;

    protected $table = "proveedores";

    /**
     * Campos asignables masivamente. Incluyen información de contacto,
     * datos fiscales, datos del contacto responsable, estado del proveedor
     * y referencias internas del sistema.
     */
    protected $fillable = [
        'logo',
        'nombre_comercial',
        'razon_social',
        'rfc',
        'tipo_persona',
        'email',
        'telefono',
        'sitio_web',
        'direccion_fiscal',
        'estado',
        'municipio',
        'codigo_postal',
        'contacto_nombre',
        'contacto_telefono',
        'contacto_email',
        'estatus',
        'fecha_registro',
        'validado_por',
        'user_id',
        'notas',
    ];

    /**
     * Conversión automática de campos al acceder.
     * En este caso, se formatea fecha_registro como objeto Carbon.
     */
    protected $casts = [
        'fecha_registro' => 'datetime',
    ];

    /**
     * Filtros disponibles para construir consultas dinámicas sobre este modelo.
     * Utilizado por controladores o repositorios para permitir filtrado de datos
     * sin escribir condiciones manuales.
     */
    protected static $filters = [
        'nombre_comercial' => 'nombre_comercial',
        'razon_social' => 'razon_social',
        'rfc' => 'rfc',
        'direccion_fiscal' => 'direccion_fiscal',
        'estado' => 'estado',
        'municipio' => 'municipio',
        'fecha_registro' => 'fecha_registro',
        'estatus' => 'estatus',
        'notas' => 'notas',
        'nombre_comercial' => 'nombre_comercial',
        'email' => 'email',
    ];

    /**
     * Define las relaciones permitidas para cargar con with() (eager loading).
     * Esto evita el problema N+1 y mejora el rendimiento de las consultas.
     *
     * @return string[]
     */
    public static function eagerLodable(): array
    {
        return [
            'user'
        ];
    }

    // ====== Scopes de filtro para campos individuales ======

    public function scopeFilterByNombreComercial($query, $value)
    {
        return $query->where('nombre_comercial', 'like', "%$value%");
    }

    public function scopeFilterByRazonSocial($query, $value)
    {
        return $query->where('razon_social', 'like', "%$value%");
    }

    public function scopeFilterByRfc($query, $value)
    {
        return $query->where('rfc', 'like', "%$value%");
    }

    public function scopeFilterByDireccionFiscal($query, $value)
    {
        return $query->where('direccion_fiscal', 'like', "%$value%");
    }

    public function scopeFilterByEstado($query, $value)
    {
        return $query->where('estado', 'like', "%$value%");
    }

    public function scopeFilterByMunicipio($query, $value)
    {
        return $query->where('municipio', 'like', "%$value%");
    }

    public function scopeFilterByFechaRegistro($query, $value)
    {
        return $query->whereDate('fecha_registro', $value);
    }

    public function scopeFilterByEstatus($query, $value)
    {
        return $query->where('estatus', $value);
    }

    public function scopeFilterByNotas($query, $value)
    {
        return $query->where('notas', 'like', "%$value%");
    }

    public function scopeFilterByEmail($query, $value)
    {
        return $query->where('email', 'like', "%$value%");
    }

    // ====== Relaciones con otros modelos ======

    /**
     * Relación uno a muchos: un proveedor puede tener varias sucursales.
     */
    public function sucursales()
    {
        return $this->hasMany(Sucursal::class);
    }

    /**
     * Relación uno a muchos: un proveedor puede tener múltiples productos registrados.
     */
    public function productos()
    {
        return $this->hasMany(Producto::class);
    }

    /**
     * Usuario que registró al proveedor.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Usuario que validó al proveedor (usando la columna validado_por).
     */
    public function validador()
    {
        return $this->belongsTo(User::class, 'validado_por');
    }
}
