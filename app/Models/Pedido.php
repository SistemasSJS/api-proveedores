<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Pedido extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'requisicion_id',
        'cotizacion_id',
        'numero_pedido',
        'fecha_confirmacion',
        'fecha_entrega_estimada',
        'fecha_entrega_real',
        'estatus',
        'observaciones',
        'observaciones_entrega',
        'numero_guia',
        'transportista',
        'fecha_cancelacion',
        'motivo_cancelacion',
        'subtotal',
        'descuento',
        'impuestos',
        'total',
    ];

    protected $casts = [
        'fecha_confirmacion' => 'datetime',
        'fecha_entrega_estimada' => 'date',
        'fecha_entrega_real' => 'datetime',
        'fecha_cancelacion' => 'datetime',
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'impuestos' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // Relaciones
    public function requisicion(): BelongsTo
    {
        return $this->belongsTo(Requisicion::class);
    }

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(PedidoDetalle::class);
    }

    // Relaciones derivadas
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id', 'id')
            ->through($this->requisicion());
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id', 'id')
            ->through($this->requisicion());
    }

    // Scopes
    public function scopeConfirmados($query)
    {
        return $query->where('estatus', 'confirmado');
    }

    public function scopeEnPreparacion($query)
    {
        return $query->where('estatus', 'en_preparacion');
    }

    public function scopeListosParaEntrega($query)
    {
        return $query->where('estatus', 'listo_para_entrega');
    }

    public function scopeEnTransito($query)
    {
        return $query->where('estatus', 'en_transito');
    }

    public function scopeEntregados($query)
    {
        return $query->where('estatus', 'entregado');
    }

    public function scopeFacturados($query)
    {
        return $query->where('estatus', 'facturado');
    }

    public function scopeCancelados($query)
    {
        return $query->where('estatus', 'cancelado');
    }

    public function scopeVencidos($query)
    {
        return $query->where('fecha_entrega_estimada', '<', now()->toDateString())
            ->whereNotIn('estatus', ['entregado', 'facturado', 'cancelado']);
    }

    public function scopeProximosAVencer($query, $dias = 3)
    {
        return $query->whereBetween('fecha_entrega_estimada', [
            now()->toDateString(),
            now()->addDays($dias)->toDateString(),
        ])->whereNotIn('estatus', ['entregado', 'facturado', 'cancelado']);
    }

    // Métodos de utilidad
    public function puedeActualizarEstatus(string $nuevoEstatus): bool
    {
        $transicionesPermitidas = [
            'confirmado' => ['en_preparacion', 'cancelado'],
            'en_preparacion' => ['listo_para_entrega', 'cancelado'],
            'listo_para_entrega' => ['en_transito', 'entregado'],
            'en_transito' => ['entregado'],
            'entregado' => ['facturado'],
            'facturado' => [],
            'cancelado' => [],
        ];

        return in_array($nuevoEstatus, $transicionesPermitidas[$this->estatus] ?? []);
    }

    public function estaVencido(): bool
    {
        return $this->fecha_entrega_estimada < now()->toDateString() &&
            ! in_array($this->estatus, ['entregado', 'facturado', 'cancelado']);
    }

    public function diasParaVencimiento(): int
    {
        return now()->diffInDays($this->fecha_entrega_estimada, false);
    }

    public function getEstadoTexto(): string
    {
        return match ($this->estatus) {
            'confirmado' => 'Confirmado',
            'en_preparacion' => 'En Preparación',
            'listo_para_entrega' => 'Listo para Entrega',
            'en_transito' => 'En Tránsito',
            'entregado' => 'Entregado',
            'facturado' => 'Facturado',
            'cancelado' => 'Cancelado',
            default => 'Desconocido'
        };
    }

    public function getColorEstatus(): string
    {
        return match ($this->estatus) {
            'confirmado' => 'blue',
            'en_preparacion' => 'orange',
            'listo_para_entrega' => 'purple',
            'en_transito' => 'indigo',
            'entregado' => 'green',
            'facturado' => 'emerald',
            'cancelado' => 'red',
            default => 'gray'
        };
    }

    // Métodos de cálculo
    public function calcularTotales(): void
    {
        $subtotal = $this->detalles()->sum('subtotal');
        $descuento = $this->detalles()->sum('descuento_total');
        $impuestos = ($subtotal - $descuento) * 0.16; // IVA 16%
        $total = $subtotal - $descuento + $impuestos;

        $this->update([
            'subtotal' => $subtotal,
            'descuento' => $descuento,
            'impuestos' => $impuestos,
            'total' => $total,
        ]);
    }

    public function marcarComoEntregado(?string $observaciones = null): bool
    {
        if (! $this->puedeActualizarEstatus('entregado')) {
            return false;
        }

        $this->update([
            'estatus' => 'entregado',
            'fecha_entrega_real' => now(),
            'observaciones_entrega' => $observaciones,
        ]);

        // Marcar detalles como entregados
        $this->detalles()->update([
            'cantidad_entregada' => DB::raw('cantidad_confirmada'),
            'cantidad_pendiente' => 0,
            'entrega_completa' => true,
        ]);

        return true;
    }

    public function cancelar(string $motivo): bool
    {
        if (! $this->puedeActualizarEstatus('cancelado')) {
            return false;
        }

        $this->update([
            'estatus' => 'cancelado',
            'fecha_cancelacion' => now(),
            'motivo_cancelacion' => $motivo,
        ]);

        return true;
    }

    // Método para generar número de pedido
    public static function generarNumeroPedido(): string
    {
        $prefix = 'PED';
        $fecha = now()->format('Ymd');
        $ultimo = static::whereDate('created_at', now()->toDateString())->count() + 1;

        return $prefix.$fecha.str_pad($ultimo, 3, '0', STR_PAD_LEFT);
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($pedido) {
            if (empty($pedido->numero_pedido)) {
                $pedido->numero_pedido = static::generarNumeroPedido();
            }
        });

        static::created(function ($pedido) {
            $pedido->calcularTotales();
        });
    }
}
