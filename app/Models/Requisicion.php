<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Requisicion extends BaseModel
{
    use HasFactory;

    protected $table = 'requisiciones';

    protected $fillable = [
        'numero_requisicion',
        'usuario_id',
        'proveedor_id',
        'estatus',
        'fecha_requerida',
        'fecha_cancelacion',
        'motivo_cancelacion',
        'observaciones',
        'observaciones_proveedor',
        'total_estimado',
    ];

    protected $casts = [
        'fecha_requerida' => 'date',
        'fecha_cancelacion' => 'datetime',
        'total_estimado' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($requisicion) {
            if (!$requisicion->numero_requisicion) {
                $requisicion->numero_requisicion = 'REQ-' . now()->format('Ymd') . '-' . str_pad(
                    static::whereDate('created_at', today())->count() + 1,
                    4,
                    '0',
                    STR_PAD_LEFT
                );
            }
        });
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(RequisicionDetalle::class);
    }

    public function cotizacion(): HasOne
    {
        return $this->hasOne(Cotizacion::class);
    }

    public function productos(): HasManyThrough
    {
        return $this->hasManyThrough(
            Producto::class,
            RequisicionDetalle::class,
            'requisicion_id', // Foreign key en RequisicionDetalle
            'id',             // Foreign key en Producto
            'id',             // Local key en Requisicion
            'producto_id'     // Local key en RequisicionDetalle
        );
    }

    public function scopePendientes($query)
    {
        return $query->where('estatus', 'pendiente');
    }

    public function scopeDelUsuario($query, $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }

    public function scopeDelProveedor($query, $proveedorId)
    {
        return $query->where('proveedor_id', $proveedorId);
    }

    public function scopePorEstatus($query, $estatus)
    {
        return $query->where('estatus', $estatus);
    }
}
