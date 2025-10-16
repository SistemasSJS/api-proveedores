<?php

namespace App\Models;

use App\Enums\EstadoCuentaBancaria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudPagoCuentaBancaria extends BaseModel
{
    use HasFactory;

    protected $table = 'solicitud_pago_cuentas_bancarias';

    protected $fillable = [
        'solicitud_pago_id',
        'cuenta_bancaria_id',
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
        // 'estatus' => EstadoCuentaBancaria::class,
        'preferida' => 'boolean',
    ];

    /**
     * Relación con la solicitud de pago
     */
    public function solicitudPago(): BelongsTo
    {
        return $this->belongsTo(SolicitudPago::class);
    }

    /**
     * Relación con la cuenta bancaria original
     */
    public function cuentaBancaria(): BelongsTo
    {
        return $this->belongsTo(CuentaBancaria::class);
    }
}