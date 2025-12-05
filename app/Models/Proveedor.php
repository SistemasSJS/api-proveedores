<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proveedor extends BaseModel
{
    use HasFactory;

    protected $table = 'proveedores';

    protected $fillable = [
        // Información general
        'logo',
        'nombre_comercial',
        'pagina_web',
        'email',
        'telefono',
        'estatus',
        'notas',
        'validado_por',
        'user_id',
        'nombre_propietario',
        'nombre_de_quien_registra',

        // NOTA: El UNIQUE de razon_social se desactivó en una migración para permitir el registro manual de Manuel.
        'razon_social',
        'tipos_empresa_id',
        'tipos_empresa_otro',
        'descripcion_giro_empresa',
        'direccion_empresa',
        'estado',
        'municipio',
        'codigo_postal',
        'ciudad',
        'contacto_nombre',
        'contacto_cargo',
        'contacto_telefono',
        'contacto_correo',
        'principal',
        'calificacion',
        'categoria',
        'is_proveedor_sp',
        'is_proveedor_catalogo',
        'cambiar_pass_default',
        'perfil_empresa_completo',

        // Información fiscal (al final)
        'rfc',
        'tipo_persona',
        'regimen_fiscal_clave',
        'regimen_fiscal_nombre',
        'constancia_fiscal',
        'direccion_fiscal',

        //
        'calle',
        'numero_exterior',
        'numero_interior',
        'colonia',
        'ciudad',
        'estado',
        'codigo_postal',
        'pais',
    ];

    protected $casts = [
        'fecha_registro' => 'datetime',
        'is_proveedor_sp' => 'boolean',
        'is_proveedor_catalogo' => 'boolean',
        'cambiar_pass_default' => 'boolean',
        'perfil_empresa_completo' => 'boolean',
    ];

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
        // 👇 Nuevo filtro
        'empresas_construcc' => 'EmpresasConstrucc',
    ];

    public static function eagerLodable(): array
    {
        return [
            'unidades',
            'categorias',
            'marcas',
            'sucursales',
            'productos',
            'cuentasBancarias',
        ];
    }

    // ================== SCOPES ==================

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

    // ================== RELACIONES ==================

    public function categorias(): HasMany
    {
        return $this->hasMany(Categoria::class);
    }

    public function marcas(): HasMany
    {
        return $this->hasMany(Marca::class);
    }

    public function unidades(): HasMany
    {
        return $this->hasMany(UnidadMedida::class);
    }

    public function sucursales(): HasMany
    {
        return $this->hasMany(Sucursal::class);
    }

    public function sucursalesActivas(): HasMany
    {
        return $this->hasMany(Sucursal::class)->where('activa', true);
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }

    public function tipos_empresa()
    {
        return $this->belongsTo(TipoEmpresa::class);
    }

    public function validador()
    {
        return $this->belongsTo(User::class, 'validado_por');
    }

    public function userProveedores(): HasMany
    {
        return $this->hasMany(UserProveedor::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_proveedor')
            ->withPivot('tipo_relacion', 'activo', 'fecha_asignacion', 'fecha_desasignacion', 'observaciones')
            ->withTimestamps();
    }

    public function usuarioPrincipal()
    {
        return $this->userProveedores()
            ->where('activo', true)
            ->where('tipo_relacion', 'PRINCIPAL')
            ->with('user')
            ->first()?->user;
    }

    public function usuariosActivos(): BelongsToMany
    {
        return $this->users()->wherePivot('activo', true);
    }

    public function usuariosSecundarios(): BelongsToMany
    {
        return $this->users()
            ->wherePivot('activo', true)
            ->wherePivot('tipo_relacion', 'SECUNDARIO');
    }

    public function tieneUsuarioConAcceso(int $userId): bool
    {
        return $this->userProveedores()
            ->where('user_id', $userId)
            ->where('activo', true)
            ->exists();
    }

    public function scopeFilterByEmpresasConstrucc($query, $value)
    {
        $empresas = explode(',', $value);

        return $query->whereHas('empresasConstrucc', function ($q) use ($empresas) {
            $q->whereIn('empresa_construcc.id', $empresas);
        });
    }

    // ================== CUENTAS BANCARIAS ==================

    public function cuentasBancarias(): HasMany
    {
        return $this->hasMany(CuentaBancaria::class);
    }

    public function scopeCuentasActivas($query)
    {
        return $query->whereHas('cuentasBancarias', function ($q) {
            $q->where('estatus', 'activo');
        });
    }

    public function scopeFilterByCuentaAlias($query, $alias)
    {
        return $query->whereHas('cuentasBancarias', function ($q) use ($alias) {
            $q->where('alias', 'like', "%{$alias}%");
        });
    }

    public function scopeFilterByTipoCuenta($query, $tipoCuenta)
    {
        return $query->whereHas('cuentasBancarias', function ($q) use ($tipoCuenta) {
            $q->where('tipo_cuenta', $tipoCuenta);
        });
    }

    public function scopeFilterByBancoClave($query, $bancoClave)
    {
        return $query->whereHas('cuentasBancarias', function ($q) use ($bancoClave) {
            $q->where('banco_clave', $bancoClave);
        });
    }

    public function empresasConstrucc()
    {
        return $this->belongsToMany(EmpresaConstrucc::class, 'empresa_construcc_proveedor');
    }
}
