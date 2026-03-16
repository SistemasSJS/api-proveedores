<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Presupuesto extends BaseModel
{
    use HasFactory;

    protected $table = 'presupuestos';

    protected static $filters = [
        'search' => 'Search',
        'uuid' => 'Uuid',
        'numero_presupuesto' => 'NumeroPresupuesto',
        'proveedor_id' => 'ProveedorId',
        'empresa_receptora_id' => 'EmpresaReceptoraId',
        'user_id' => 'UserId',
        'fecha_emision' => 'FechaEmision',
        'fecha_desde' => 'FechaDesde',
        'fecha_hasta' => 'FechaHasta',
        'fecha_vencimiento_desde' => 'FechaVencimientoDesde',
        'fecha_vencimiento_hasta' => 'FechaVencimientoHasta',
        'con_iva' => 'ConIva',
        'total' => 'Total',
        'estado' => 'Estado',
    ];

    public const ESTADO_BORRADOR = 'borrador';
    public const ESTADO_ENVIADO = 'enviado';
    public const ESTADO_ACEPTADO = 'aceptado';
    public const ESTADO_RECHAZADO = 'rechazado';
    public const ESTADO_VENCIDO = 'vencido';

    protected $fillable = [
        'uuid',
        'numero_presupuesto',
        'fecha_emision',
        'fecha_vencimiento',
        'concepto_general',
        'subtotal',
        'con_iva',
        'iva_porcentaje',
        'iva_total',
        'total',
        'empresa_receptora_nombre',
        'empresa_receptora_puesto',
        'empresa_receptora_empresa',
        'empresa_receptora_telefono',
        'empresa_receptora_correo',
        'condiciones',
        'observaciones',
        'estado',
        'token_publico',
        'proveedor_id',
        'empresa_receptora_id',
        'user_id',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_vencimiento' => 'date',
        'con_iva' => 'boolean',
        'subtotal' => 'decimal:2',
        'iva_porcentaje' => 'decimal:2',
        'iva_total' => 'decimal:2',
        'total' => 'decimal:2',
        'condiciones' => 'array',
    ];

    /**
     * Boot del modelo.
     */
    protected static function booted(): void
    {
        static::creating(function (Presupuesto $presupuesto) {
            if (empty($presupuesto->uuid)) {
                $presupuesto->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Relaciones para carga eager estándar.
     *
     * @return array<int, string>
     */
    public static function eagerLodable(): array
    {
        return [
            'proveedor',
            'empresaReceptora',
            'user',
            'conceptos',
        ];
    }

    /**
     * Relación con proveedor emisor.
     */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    /**
     * Relación con cliente de cartera receptora.
     */
    public function empresaReceptora(): BelongsTo
    {
        return $this->belongsTo(CarteraCliente::class, 'empresa_receptora_id');
    }

    /**
     * Usuario que registró el presupuesto.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Conceptos del presupuesto.
     */
    public function conceptos(): HasMany
    {
        return $this->hasMany(PresupuestoConcepto::class);
    }


    /**
     * HELPERS
     */


    /**
     * Calcula subtotal, IVA y total con base en conceptos y configuración del IVA.
     */
    public function calcularTotales(): void
    {
        $this->recalcularDesdeConceptos();
    }

    /**
     * Recalcula el subtotal a partir de conceptos y luego aplica IVA.
     */
    public function recalcularDesdeConceptos(): void
    {
        $subtotal = $this->relationLoaded('conceptos')
            ? $this->conceptos->sum(fn(PresupuestoConcepto $concepto) => (float) $concepto->precio_total)
            : (float) $this->conceptos()->sum('precio_total');

        $this->subtotal = round($subtotal, 2);
        $this->aplicarIva();
    }

    /**
     * Aplica IVA según configuración actual (`con_iva` e `iva_porcentaje`).
     */
    public function aplicarIva(): void
    {
        $subtotal = (float) $this->subtotal;
        $porcentajeIva = (float) $this->iva_porcentaje;

        if ($this->con_iva) {
            $ivaTotal = round(($subtotal * $porcentajeIva) / 100, 2);
            $this->iva_total = $ivaTotal;
            $this->total = round($subtotal + $ivaTotal, 2);

            return;
        }

        $this->iva_total = 0;
        $this->total = round($subtotal, 2);
    }

    /**
     * Genera un token público único para compartir el presupuesto.
     */
    public function generarTokenPublico(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->token_publico = $token;
        $this->save();

        return $token;
    }

    /**
     * Asegura que el presupuesto tenga un token público.
     */
    public function asegurarTokenPublico(): string
    {
        if ($this->token_publico) {
            return $this->token_publico;
        }

        return $this->generarTokenPublico();
    }

    /**
     * Genera un número de presupuesto consecutivo por proveedor.
     */
    public static function generarNumeroPresupuesto(int $proveedorId): string
    {
        $proveedor = Proveedor::query()->findOrFail($proveedorId);

        return $proveedor->obtenerFolioSiguientePresupuesto();
    }

    /**
     * Marca como vencidos los presupuestos enviados cuya fecha_vencimiento ya pasó.
     */
    public static function actualizarVencidos(): int
    {
        return self::query()
            ->where('estado', self::ESTADO_ENVIADO)
            ->whereNotNull('fecha_vencimiento')
            ->whereDate('fecha_vencimiento', '<', now()->toDateString())
            ->update(['estado' => self::ESTADO_VENCIDO]);
    }


    /**
     * SCOPES
     */


    /**
     * Filtra por UUID.
     */
    public function scopeByUuid($query, string $uuid)
    {
        return $query->where('uuid', $uuid);
    }

    /**
     * Filtra por proveedor.
     */
    public function scopeByProveedor($query, int $proveedorId)
    {
        return $query->where('proveedor_id', $proveedorId);
    }

    /**
     * Filtra por usuario.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Filtra por rango de fechas de emisión.
     */
    public function scopeByFechaRango($query, ?string $desde, ?string $hasta)
    {
        return $query
            ->when($desde, fn($q) => $q->whereDate('fecha_emision', '>=', $desde))
            ->when($hasta, fn($q) => $q->whereDate('fecha_emision', '<=', $hasta));
    }

    /**
     * Presupuestos con IVA.
     */
    public function scopeConIva($query)
    {
        return $query->where('con_iva', true);
    }

    /**
     * Presupuestos sin IVA.
     */
    public function scopeSinIva($query)
    {
        return $query->where('con_iva', false);
    }

    /**
     * FILTERS
     */

    /**
     * Filtro por búsqueda general.
     * Busca en: numero_presupuesto, concepto_general, empresa_receptora_nombre, empresaReceptora.nombre
     */
    public function filterBySearch($query, string $value)
    {
        return $query->where(function ($query) use ($value) {
            $query
                ->where('numero_presupuesto', 'like', "%{$value}%")
                ->orWhere('concepto_general', 'like', "%{$value}%")
                ->orWhere('empresa_receptora_nombre', 'like', "%{$value}%")
                ->orWhere('empresa_receptora_empresa', 'like', "%{$value}%")
                ->orWhereHas('empresaReceptora', function ($q) use ($value) {
                    $q->where('nombre', 'like', "%{$value}%")
                      ->orWhere('empresa', 'like', "%{$value}%");
                });
        });
    }

    /**
     * Filtro por UUID.
     */
    public function filterByUuid($query, string $value)
    {
        return $query->where('uuid', $value);
    }

    /**
     * Filtro por número de presupuesto.
     */
    public function filterByNumeroPresupuesto($query, string $value)
    {
        return $query->where('numero_presupuesto', 'like', "%{$value}%");
    }

    /**
     * Filtro por proveedor.
     */
    public function filterByProveedorId($query, $value)
    {
        return $query->whereIn('proveedor_id', explode(',', (string) $value));
    }

    /**
     * Filtro por empresa receptora (solo registros del sistema).
     */
    public function filterByEmpresaReceptoraId($query, $value)
    {
        return $query->whereIn('empresa_receptora_id', explode(',', (string) $value));
    }

    /**
     * Filtro por usuario.
     */
    public function filterByUserId($query, $value)
    {
        return $query->whereIn('user_id', explode(',', (string) $value));
    }

    /**
     * Filtro por fecha exacta de emisión.
     */
    public function filterByFechaEmision($query, string $value)
    {
        return $query->whereDate('created_at', $value);
    }

    /**
     * Filtro por fecha de emisión desde.
     */
    public function filterByFechaDesde($query, string $value)
    {
        return $query->whereDate('created_at', '>=', $value);
    }

    /**
     * Filtro por fecha de emisión hasta.
     */
    public function filterByFechaHasta($query, string $value)
    {
        return $query->whereDate('created_at', '<=', $value);
    }

    /**
     * Filtro por fecha de vencimiento desde.
     */
    public function filterByFechaVencimientoDesde($query, string $value)
    {
        return $query->whereDate('fecha_vencimiento', '>=', $value);
    }

    /**
     * Filtro por fecha de vencimiento hasta.
     */
    public function filterByFechaVencimientoHasta($query, string $value)
    {
        return $query->whereDate('fecha_vencimiento', '<=', $value);
    }

    /**
     * Filtro por indicador de IVA.
     */
    public function filterByConIva($query, $value)
    {
        $boolValue = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($boolValue === null) {
            return $query;
        }

        return $query->where('con_iva', $boolValue);
    }

    /**
     * Filtro por total exacto.
     */
    public function filterByTotal($query, $value)
    {
        return $query->where('total', $value);
    }

    /**
     * Filtro por estado del presupuesto.
     */
    public function filterByEstado($query, $value)
    {
        return $query->where('estado', $value);
    }
}
