<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoDetalle extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'pedido_id',
        'producto_id',
        'cotizacion_detalle_id',
        'cantidad_confirmada',
        'precio_unitario_final',
        'subtotal',
        'descuento_unitario',
        'descuento_total',
        'observaciones',
        'cantidad_entregada',
        'cantidad_pendiente',
        'entrega_completa',
    ];

    protected $casts = [
        'precio_unitario_final' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'descuento_unitario' => 'decimal:2',
        'descuento_total' => 'decimal:2',
        'entrega_completa' => 'boolean',
    ];

    // Relaciones
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    public function cotizacionDetalle(): BelongsTo
    {
        return $this->belongsTo(CotizacionDetalle::class);
    }

    // Relaciones derivadas
    public function requisicionDetalle(): BelongsTo
    {
        return $this->belongsTo(RequisicionDetalle::class, 'requisicion_detalle_id', 'id')
            ->through($this->cotizacionDetalle());
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
        //  'producto_id', 'id')
        //     ->through($this->requisicionDetalle());
    }

    // Scopes
    public function scopeEntregados($query)
    {
        return $query->where('entrega_completa', true);
    }

    public function scopePendientes($query)
    {
        return $query->where('entrega_completa', false);
    }

    public function scopeEntregaParcial($query)
    {
        return $query->where('cantidad_entregada', '>', 0)
            ->where('entrega_completa', false);
    }

    public function scopeConDescuento($query)
    {
        return $query->where('descuento_total', '>', 0);
    }

    // Métodos de utilidad
    public function calcularSubtotal(): void
    {
        $subtotal = $this->cantidad_confirmada * $this->precio_unitario_final;
        $descuento = $this->cantidad_confirmada * $this->descuento_unitario;

        $this->update([
            'subtotal' => $subtotal,
            'descuento_total' => $descuento,
        ]);
    }

    public function actualizarCantidadPendiente(): void
    {
        $pendiente = $this->cantidad_confirmada - $this->cantidad_entregada;
        $completa = $pendiente <= 0;

        $this->update([
            'cantidad_pendiente' => max(0, $pendiente),
            'entrega_completa' => $completa,
        ]);
    }

    public function entregarCantidad(int $cantidad): bool
    {
        if ($cantidad <= 0 || $cantidad > $this->cantidad_pendiente) {
            return false;
        }

        $this->increment('cantidad_entregada', $cantidad);
        $this->actualizarCantidadPendiente();

        return true;
    }

    public function marcarComoEntregado(): void
    {
        $this->update([
            'cantidad_entregada' => $this->cantidad_confirmada,
            'cantidad_pendiente' => 0,
            'entrega_completa' => true,
        ]);
    }

    public function getPorcentajeEntregado(): float
    {
        if ($this->cantidad_confirmada == 0) {
            return 0;
        }

        return round(($this->cantidad_entregada / $this->cantidad_confirmada) * 100, 2);
    }

    public function getEstadoEntrega(): string
    {
        if ($this->entrega_completa) {
            return 'completa';
        }

        if ($this->cantidad_entregada > 0) {
            return 'parcial';
        }

        return 'pendiente';
    }

    public function getColorEstadoEntrega(): string
    {
        return match ($this->getEstadoEntrega()) {
            'completa' => 'green',
            'parcial' => 'orange',
            'pendiente' => 'red',
            default => 'gray'
        };
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($detalle) {
            $detalle->cantidad_pendiente = $detalle->cantidad_confirmada;
        });

        static::created(function ($detalle) {
            $detalle->calcularSubtotal();
        });

        static::updated(function ($detalle) {
            if ($detalle->wasChanged(['cantidad_confirmada', 'precio_unitario_final', 'descuento_unitario'])) {
                $detalle->calcularSubtotal();
            }
        });
    }
}
