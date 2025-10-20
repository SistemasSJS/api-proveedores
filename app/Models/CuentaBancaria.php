<?php

namespace App\Models;

use App\Enums\EstadoCuentaBancaria;
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
        'sucursal',
        'swift',
        'preferida',
    ];

    protected $casts = [
        'estatus' => EstadoCuentaBancaria::class,
        'preferida' => 'boolean',
    ];

    protected static $filters = [
        'proveedor_id' => 'ProveedorId',
        'alias' => 'Alias',
        'banco_clave' => 'BancoClave',
        'banco_nombre' => 'BancoNombre',
        'tipo_cuenta' => 'TipoCuenta',
        'campo_dependiente' => 'CampoDependiente',
        'titular_cuenta' => 'TitularCuenta',
        'referencia' => 'Referencia',
        'estatus' => 'Estatus',
        'sucursal' => 'Sucursal',
        'swift' => 'Swift',
        'preferida' => 'Preferida',
    ];

    /**
     * Relación con el proveedor
     */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    // ================== SCOPES ==================

    public function scopeProveedor($query, int $proveedorId)
    {
        return $query->where('proveedor_id', $proveedorId);
    }

    public function scopeAlias($query, string $alias)
    {
        return $query->where('alias', 'like', "%{$alias}%");
    }

    public function scopeTipo($query, string $tipo)
    {
        return $query->where('tipo_cuenta', $tipo);
    }

    public function scopeBanco($query, string $claveBanco)
    {
        return $query->where('banco_clave', $claveBanco);
    }

    public function scopeActivas($query)
    {
        return $query->where('estatus', EstadoCuentaBancaria::ACTIVA);
    }

    public function scopeTitular($query, string $titular)
    {
        return $query->where('titular_cuenta', 'like', "%{$titular}%");
    }

    public function scopeReferencia($query, string $referencia)
    {
        return $query->where('referencia', 'like', "%{$referencia}%");
    }

    public function scopeCampoDependiente($query, string $campo)
    {
        return $query->where('campo_dependiente', 'like', "%{$campo}%");
    }

    public function scopeSucursal($query, string $sucursal)
    {
        return $query->where('sucursal', 'like', "%{$sucursal}%");
    }

    public function scopeSwift($query, string $swift)
    {
        return $query->where('swift', 'like', "%{$swift}%");
    }

    public function scopePreferida($query, bool $preferida = true)
    {
        return $query->where('preferida', $preferida);
    }
}
