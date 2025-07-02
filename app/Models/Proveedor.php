<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'pagina_web',
        'email',
        'telefono',

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
        'razon_social',
        'tipos_empresa_id',
        'tipos_empresa_otro',
        'descripcion_giro_empresa',
        'direccion_empresa',
        // 'ubicacion',
        'contacto_nombre',
        'contacto_cargo',
        'contacto_telefono',
        'contacto_correo',

        'principal',
        'calificacion',
        'categoria',
        'ciudad',
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
        'email' => 'email',
        'descripcion_giro_empresa' => 'descripcion_giro_empresa',
        'direccion_empresa' => 'direccion_empresa',
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
            // 'user',
            // 'tipos_empresa'
            'categorias',
            'marcas',
            'lineas',
            'sucursales',
            'productos',
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

    public function categorias()
    {
        return $this->hasMany(Categoria::class);
    }
    public function marcas()
    {
        return $this->hasMany(Marca::class);
    }
    public function lineas()
    {
        return $this->hasMany(Linea::class);
    }
    /**
     * Relación uno a muchos: un proveedor puede tener varias sucursales.
     */
    // public function sucursales()
    // {
    //     return $this->hasMany(Sucursal::class);
    // }

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
    public function tipos_empresa()
    {
        return $this->belongsTo(TipoEmpresa::class);
    }

    /**
     * Usuario que validó al proveedor (usando la columna validado_por).
     */
    public function validador()
    {
        return $this->belongsTo(User::class, 'validado_por');
    }

    // Relación directa con la tabla pivot
    public function userProveedores(): HasMany
    {
        return $this->hasMany(UserProveedor::class);
    }

    /**
     * Relación many-to-many con usuarios a través de tabla pivot
     * Incluye campos adicionales de la relación (tipo, estado, fechas)
     *
     * @return BelongsToMany<User> Colección de usuarios relacionados con datos pivot
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_proveedor')
            ->withPivot('tipo_relacion', 'activo', 'fecha_asignacion', 'fecha_desasignacion', 'observaciones')
            ->withTimestamps();
    }

    /**
     * Obtiene el usuario principal activo del proveedor
     * Un proveedor debe tener un usuario principal
     *
     * @return User|null El usuario principal del proveedor o null si no tiene
     */
    public function usuarioPrincipal()
    {
        return $this->userProveedores()
            ->where('activo', true)
            ->where('tipo_relacion', 'PRINCIPAL')
            ->with('user')
            ->first()?->user;
    }

    /**
     * Obtiene todos los usuarios activos del proveedor
     * Incluye tanto principales como secundarios que estén activos
     *
     * @return BelongsToMany<User> Colección de usuarios activos
     */
    public function usuariosActivos(): BelongsToMany
    {
        return $this->users()->wherePivot('activo', true);
    }

    /**
     * Obtiene todos los usuarios secundarios activos del proveedor
     * Excluye al usuario principal
     *
     * @return BelongsToMany<User> Colección de usuarios secundarios activos
     */
    public function usuariosSecundarios(): BelongsToMany
    {
        return $this->users()
            ->wherePivot('activo', true)
            ->wherePivot('tipo_relacion', 'SECUNDARIO');
    }

    /**
     * Verifica si un usuario específico tiene acceso al proveedor
     * Útil para validaciones de autorización
     *
     * @param int $userId ID del usuario a verificar
     * @return bool True si el usuario tiene acceso activo al proveedor
     */
    public function tieneUsuarioConAcceso(int $userId): bool
    {
        return $this->userProveedores()
            ->where('user_id', $userId)
            ->where('activo', true)
            ->exists();
    }

    public function sucursales(): HasMany
    {
        return $this->hasMany(Sucursal::class);
    }

    public function requisiciones(): HasMany
    {
        return $this->hasMany(Requisicion::class);
    }

    public function sucursalesActivas(): HasMany
    {
        return $this->hasMany(Sucursal::class)->where('activa', true);
    }
}
