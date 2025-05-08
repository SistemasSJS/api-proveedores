<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @OA\Schema(
 *     schema="Proveedor",
 *     title="Proveedor",
 *     description="Esquema del modelo Proveedor",
 *     required={
 *         "nombre_comercial", "razon_social", "rfc", "email", "telefono",
 *         "estado", "municipio", "codigo_postal", "contacto_nombre",
 *         "contacto_telefono", "contacto_correo", "tipos_empresa_id",
 *         "descripcion_giro_empresa", "direccion_empresa"
 *     },
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="logo", type="string", format="uri", example="https://misitio.com/logo.png"),
 *     @OA\Property(property="rfc", type="string", example="RIEM920313AB1"),
 *     @OA\Property(property="tipo_persona", type="string", example="Moral"),
 *     @OA\Property(property="direccion_fiscal", type="string", example="Calle Ficticia 123"),
 *     @OA\Property(property="estado", type="string", example="Sinaloa"),
 *     @OA\Property(property="municipio", type="string", example="Culiacán"),
 *     @OA\Property(property="codigo_postal", type="string", example="80000"),
 *     @OA\Property(property="estatus", type="string", example="pendiente"),
 *     @OA\Property(property="notas", type="string", example="Proveedor con buena reputación."),
 *     @OA\Property(property="validado_por", type="integer", nullable=true, example=5),
 *     @OA\Property(property="user_id", type="integer", nullable=true, example=2),
 *     @OA\Property(property="nombre_propietario", type="string", example="Carlos López"),
 *     @OA\Property(property="nombre_de_quien_registra", type="string", example="María Pérez"),
 *     @OA\Property(property="nombre_comercial", type="string", example="Materiales Rivera"),
 *     @OA\Property(property="razon_social", type="string", example="Materiales de Construcción Rivera S.A. de C.V."),
 *     @OA\Property(property="tipos_empresa_id", type="integer", example=1),
 *     @OA\Property(property="tipos_empresa_otro", type="string", nullable=true, example="Familiar"),
 *     @OA\Property(property="descripcion_giro_empresa", type="string", example="Venta de materiales de construcción."),
 *     @OA\Property(property="direccion_empresa", type="string", example="Calle 123, Col. Centro"),
 *     @OA\Property(property="email", type="string", format="email", example="contacto@materialesrivera.com"),
 *     @OA\Property(property="telefono", type="string", example="6671234567"),
 *     @OA\Property(property="pagina_web", type="string", format="uri", nullable=true, example="https://materialesrivera.com"),
 *     @OA\Property(property="contacto_nombre", type="string", example="Juan Pérez"),
 *     @OA\Property(property="contacto_cargo", type="string", example="Gerente de compras"),
 *     @OA\Property(property="contacto_telefono", type="string", example="6677654321"),
 *     @OA\Property(property="contacto_correo", type="string", format="email", example="juan.perez@materialesrivera.com"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T12:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-02T15:30:00Z")
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
        'rfc',
        'tipo_persona',
        'direccion_fiscal',
        'estado',
        'municipio',
        'codigo_postal',
        'estatus',
        'notas',
        'validado_por',
        'user_id',

        'nombre_propietario',
        'nombre_de_quien_registra',
        'nombre_comercial',
        'razon_social',
        'tipos_empresa_id',
        'tipos_empresa_otro',
        'descripcion_giro_empresa',
        'direccion_empresa',
        'email',
        'telefono',
        'pagina_web',
        // 'ubicacion',
        'contacto_nombre',
        'contacto_cargo',
        'contacto_telefono',
        'contacto_correo',
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
        'tipos_empresa' => 'tipos_empresa',
        'otro_tipos_empresa' => 'otro_tipos_empresa',
        'descripcion_giro_empresa' => 'descripcion_giro_empresa',
        'Direccion_empresa' => 'Direccion_empresa',
        'Ubicacion' => 'Ubicacion',
        'pagina_web' => 'pagina_web',
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
