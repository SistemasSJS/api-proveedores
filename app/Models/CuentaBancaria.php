<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuentaBancaria extends BaseModel
{
    use HasFactory;

    protected $table = 'cuentas_bancarias';

    protected $fillable = [
        'proveedor_id',
        'alias',
        'banco_clave',
        'banco_nombre',
        'tipo_cuenta',
        'campo_dependiente',
        'titular_cuenta',
        'referencia',
        'estatus',
    ];

    protected static $filters = [
        'proveedor_id'      => 'ProveedorId',
        'alias'             => 'Alias',
        'banco_clave'       => 'BancoClave',
        'banco_nombre'       => 'BancoNombre',
        'tipo_cuenta'       => 'TipoCuenta',
        'campo_dependiente' => 'CampoDependiente',
        'titular_cuenta'    => 'TitularCuenta',
        'referencia'        => 'Referencia',
        'estatus'           => 'Estatus',
    ];

    /**
     * Relación con el proveedor
     */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    // ================== SCOPES ==================

    /**
     * Scope para filtrar por proveedor
     */
    public function scopeProveedor($query, int $proveedorId)
    {
        return $query->where('proveedor_id', $proveedorId);
    }

    /**
     * Scope para filtrar por alias
     */
    public function scopeAlias($query, string $alias)
    {
        return $query->where('alias', 'like', "%{$alias}%");
    }

    /**
     * Scope para filtrar por tipo de cuenta
     */
    public function scopeTipo($query, string $tipo)
    {
        return $query->where('tipo_cuenta', $tipo);
    }

    /**
     * Scope para filtrar por banco (clave)
     */
    public function scopeBanco($query, string $claveBanco)
    {
        return $query->where('banco_clave', $claveBanco);
    }

    /**
     * Scope para filtrar por estatus activo
     */
    public function scopeActivas($query)
    {
        return $query->where('estatus', 'activo');
    }

    /**
     * Scope para filtrar por titular de la cuenta
     */
    public function scopeTitular($query, string $titular)
    {
        return $query->where('titular_cuenta', 'like', "%{$titular}%");
    }

    /**
     * Scope para filtrar por referencia
     */
    public function scopeReferencia($query, string $referencia)
    {
        return $query->where('referencia', 'like', "%{$referencia}%");
    }

    /**
     * Scope para filtrar por campo_dependiente (CLABE, tarjeta, cuenta)
     */
    public function scopeCampoDependiente($query, string $campo)
    {
        return $query->where('campo_dependiente', 'like', "%{$campo}%");
    }
}
