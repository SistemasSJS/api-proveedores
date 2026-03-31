<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\MarksAsNotified;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Presupuesto extends BaseModel
{
    use HasFactory, MarksAsNotified, Filterable;

    protected $table = 'presupuestos';

    protected static $filters = [
        'search' => 'Search',
        'uuid' => 'Uuid',
        'numero_presupuesto' => 'NumeroPresupuesto',
        'proveedor_id' => 'ProveedorId',
        'proveedor_receptor_id' => 'ProveedorReceptorId',
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
        'item_visto' => 'ItemVisto',
        'segmento' => 'Segmento',
        'ultimas_presupuestos' => 'UltimasPresupuestos',
    ];

    public const ESTADO_BORRADOR = 'borrador';
    public const ESTADO_ENVIADO = 'enviado';
    public const ESTADO_ACEPTADO = 'aceptado';
    public const ESTADO_RECHAZADO = 'rechazado';
    public const ESTADO_RECHAZADO_CON_OBSERVACION = 'rechazado_con_observacion';
    public const ESTADO_VENCIDO = 'vencido';

    /**
     * Receptor del presupuesto (quien recibe la cotización):
     * - Sin empresa_receptora_id: solo datos en texto (empresa_receptora_*), p. ej. cliente que no está en cartera.
     * - Con empresa_receptora_id: id de {@see CarteraCliente} del emisor (FK); la relación empresaReceptora() aplica.
     * - Proveedor del catálogo: empresa_receptora_id null; proveedor_receptor_id → {@see Proveedor} (FK).
     * proveedor_id: proveedor emisor del presupuesto. user_id: usuario que creó/editó el registro.
     * configuracion_condiciones: JSON (términos/opciones); no usar para id de receptor (columna dedicada).
     */
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
        'empresa_receptora_alias',
        'empresa_receptora_telefono',
        'empresa_receptora_correo',
        'term_cond_dias_vigencia',
        'term_cond_moneda',
        'term_cond_impuestos_en_pdf',
        'term_cond_iva',
        'term_cond_anticipo_porcentaje',
        'term_cond_tiempo_entrega_dias',
        'obs_garantia_dias',
        'obs_traslados',
        'obs_viaticos',
        'configuracion_condiciones',
        'motivo_rechazo',
        'estado',
        'item_visto',
        'notification_id',
        'token_publico',
        'proveedor_id',
        'empresa_receptora_id',
        'proveedor_receptor_id',
        'user_id',
    ];

    /**
     * Términos y condiciones: term_cond_* (columnas explícitas).
     * Observaciones: obs_* (columnas explícitas).
     */
    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_vencimiento' => 'date',
        'con_iva' => 'boolean',
        'term_cond_impuestos_en_pdf' => 'boolean',
        'item_visto' => 'boolean',
        'subtotal' => 'decimal:2',
        'iva_porcentaje' => 'decimal:2',
        'iva_total' => 'decimal:2',
        'total' => 'decimal:2',
        'term_cond_iva' => 'decimal:2',
        'term_cond_anticipo_porcentaje' => 'decimal:2',
        'obs_traslados' => 'boolean',
        'obs_viaticos' => 'boolean',
        'configuracion_condiciones' => 'array',
    ];

    /**
     * Constantes para enunciados de términos y condiciones.
     */
    public const ENUNCIADO_VIGENCIA = 'Este presupuesto tiene una vigencia de %d días naturales a partir de su fecha de emisión.';
    public const ENUNCIADO_MONEDA = 'Los precios están expresados en moneda nacional (%s), salvo que se indique lo contrario.';
    public const ENUNCIADO_IVA_INCLUIDO = 'Los precios incluyen el Impuesto al Valor Agregado (IVA) al %d%%.';
    public const ENUNCIADO_IVA_NO_INCLUIDO = 'Los precios no incluyen el Impuesto al Valor Agregado (IVA).';
    public const ENUNCIADO_ANTICIPO = 'Para iniciar los trabajos se requiere un anticipo del %d%% del monto total.';
    public const ENUNCIADO_TIEMPO_ENTREGA = 'Una vez recibido el anticipo, el tiempo estimado de entrega o ejecución total de los trabajos será de %d días naturales.';

    /**
     * Constantes para enunciados de observaciones.
     */
    public const ENUNCIADO_GARANTIA = 'La garantía de los trabajos o productos tendrá una vigencia de %d días a partir de la finalización de los trabajos o entrega de los productos.';
    public const ENUNCIADO_TRASLADOS_INCLUIDOS = 'Los trabajos contemplados en este presupuesto sí incluyen los gastos de traslado al sitio donde se realizarán los trabajos.';
    public const ENUNCIADO_TRASLADOS_NO_INCLUIDOS = 'Los trabajos contemplados en este presupuesto no incluyen los gastos de traslado al sitio donde se realizarán los trabajos.';
    public const ENUNCIADO_VIATICOS_INCLUIDOS = 'Los trabajos contemplados en este presupuesto sí incluyen los gastos de viáticos derivados de la ubicación donde deberán realizarse los trabajos.';
    public const ENUNCIADO_VIATICOS_NO_INCLUIDOS = 'Los trabajos contemplados en este presupuesto no incluyen los gastos de viáticos derivados de la ubicación donde deberán realizarse los trabajos.';
    public const ENUNCIADO_REVISION_TECNICA = 'Requiere revisión técnica previa.';
    public const ENUNCIADO_CONDICIONES_SITIO = 'Condiciones del sitio de trabajo deben ser adecuadas.';

    /**
     * Construye la lista de enunciados de términos y condiciones para el PDF.
     *
     * @return array<int, string>
     */
    public function getTerminosEnunciados(): array
    {
        $lista = [];
        $config = is_array($this->configuracion_condiciones) ? $this->configuracion_condiciones : [];

        if ($this->term_cond_dias_vigencia > 0) {
            $lista[] = sprintf(self::ENUNCIADO_VIGENCIA, (int) $this->term_cond_dias_vigencia);
        }

        $moneda = $this->term_cond_moneda ?: 'MXN';
        $lista[] = sprintf(self::ENUNCIADO_MONEDA, $moneda);

        if ($this->term_cond_impuestos_en_pdf !== false) {
            $ivaPct = (float) ($this->term_cond_iva ?? 16);
            $lista[] = $this->con_iva
                ? sprintf(self::ENUNCIADO_IVA_INCLUIDO, (int) $ivaPct)
                : self::ENUNCIADO_IVA_NO_INCLUIDO;
        }

        if ($this->term_cond_anticipo_porcentaje !== null && $this->term_cond_anticipo_porcentaje > 0) {
            $lista[] = sprintf(self::ENUNCIADO_ANTICIPO, (int) $this->term_cond_anticipo_porcentaje);
        }

        if ($this->term_cond_tiempo_entrega_dias !== null && $this->term_cond_tiempo_entrega_dias > 0) {
            $lista[] = sprintf(self::ENUNCIADO_TIEMPO_ENTREGA, (int) $this->term_cond_tiempo_entrega_dias);
        }

        foreach ([
            'condicionantes_adicionales_1',
            'condicionantes_adicionales_2',
            'condicionantes_adicionales_3',
            'condicionantes_adicionales_4',
        ] as $key) {
            $txt = trim((string) ($config[$key] ?? ''));
            if ($txt !== '') {
                $lista[] = $txt;
            }
        }

        return $lista;
    }

    /**
     * Construye la lista de enunciados de observaciones para el PDF.
     *
     * @return array<int, string>
     */
    public function getObservacionesEnunciados(): array
    {
        $lista = [];
        $config = is_array($this->configuracion_condiciones) ? $this->configuracion_condiciones : [];

        if ($this->obs_garantia_dias > 0) {
            $lista[] = sprintf(self::ENUNCIADO_GARANTIA, (int) $this->obs_garantia_dias);
        }

        if ($this->obs_traslados !== null) {
            $lista[] = $this->obs_traslados
                ? self::ENUNCIADO_TRASLADOS_INCLUIDOS
                : self::ENUNCIADO_TRASLADOS_NO_INCLUIDOS;
        }

        if ($this->obs_viaticos !== null) {
            $lista[] = $this->obs_viaticos
                ? self::ENUNCIADO_VIATICOS_INCLUIDOS
                : self::ENUNCIADO_VIATICOS_NO_INCLUIDOS;
        }

        if (! empty($config['revision_tecnica_activo'])) {
            $lista[] = self::ENUNCIADO_REVISION_TECNICA;
        }

        if (! empty($config['condiciones_sitio_activo'])) {
            $lista[] = self::ENUNCIADO_CONDICIONES_SITIO;
        }

        foreach ([
            'observaciones_adicionales_1',
            'observaciones_adicionales_2',
            'observaciones_adicionales_3',
            'observaciones_adicionales_4',
        ] as $key) {
            $txt = trim((string) ($config[$key] ?? ''));
            if ($txt !== '') {
                $lista[] = $txt;
            }
        }

        return $lista;
    }

    /**
     * Construye enunciados de términos desde un array (ej: datos de formulario).
     *
     * @param  array<string, mixed>  $data
     * @return array<int, string>
     */
    public static function buildTerminosEnunciadosFromArray(array $data): array
    {
        $lista = [];
        $conIva = $data['con_iva'] ?? true;
        $ivaPct = (float) ($data['term_cond_iva'] ?? $data['iva_porcentaje'] ?? 16);
        $config = is_array($data['configuracion_condiciones'] ?? null) ? $data['configuracion_condiciones'] : [];

        if (! empty($data['term_cond_dias_vigencia']) && (int) $data['term_cond_dias_vigencia'] > 0) {
            $lista[] = sprintf(self::ENUNCIADO_VIGENCIA, (int) $data['term_cond_dias_vigencia']);
        }

        $moneda = $data['term_cond_moneda'] ?? 'MXN';
        $lista[] = sprintf(self::ENUNCIADO_MONEDA, $moneda);

        $mostrarImpuestos = $data['term_cond_impuestos_en_pdf'] ?? true;
        if ($mostrarImpuestos !== false) {
            $lista[] = $conIva
                ? sprintf(self::ENUNCIADO_IVA_INCLUIDO, (int) $ivaPct)
                : self::ENUNCIADO_IVA_NO_INCLUIDO;
        }

        $anticipo = $data['term_cond_anticipo_porcentaje'] ?? null;
        if ($anticipo !== null && (float) $anticipo > 0) {
            $lista[] = sprintf(self::ENUNCIADO_ANTICIPO, (int) $anticipo);
        }

            $tiempoEntrega = $data['term_cond_tiempo_entrega_dias'] ?? null;
            if ($tiempoEntrega !== null && (int) $tiempoEntrega > 0) {
            $lista[] = sprintf(self::ENUNCIADO_TIEMPO_ENTREGA, (int) $tiempoEntrega);
        }

        foreach ([
            'condicionantes_adicionales_1',
            'condicionantes_adicionales_2',
            'condicionantes_adicionales_3',
            'condicionantes_adicionales_4',
        ] as $key) {
            $txt = trim((string) ($config[$key] ?? ''));
            if ($txt !== '') {
                $lista[] = $txt;
            }
        }

        return $lista;
    }

    /**
     * Construye enunciados de observaciones desde un array.
     *
     * @param  array<string, mixed>  $data
     * @return array<int, string>
     */
    public static function buildObservacionesEnunciadosFromArray(array $data): array
    {
        $lista = [];
        $config = is_array($data['configuracion_condiciones'] ?? null) ? $data['configuracion_condiciones'] : [];

        $garantiaDias = (int) ($data['obs_garantia_dias'] ?? 0);
        if ($garantiaDias > 0) {
            $lista[] = sprintf(self::ENUNCIADO_GARANTIA, $garantiaDias);
        }

        if (! array_key_exists('obs_traslados', $data)) {
            $lista[] = self::ENUNCIADO_TRASLADOS_NO_INCLUIDOS;
        } elseif ($data['obs_traslados'] !== null) {
            $lista[] = ((bool) $data['obs_traslados'])
                ? self::ENUNCIADO_TRASLADOS_INCLUIDOS
                : self::ENUNCIADO_TRASLADOS_NO_INCLUIDOS;
        }

        if (! array_key_exists('obs_viaticos', $data)) {
            $lista[] = self::ENUNCIADO_VIATICOS_NO_INCLUIDOS;
        } elseif ($data['obs_viaticos'] !== null) {
            $lista[] = ((bool) $data['obs_viaticos'])
                ? self::ENUNCIADO_VIATICOS_INCLUIDOS
                : self::ENUNCIADO_VIATICOS_NO_INCLUIDOS;
        }

        if (! empty($config['revision_tecnica_activo'])) {
            $lista[] = self::ENUNCIADO_REVISION_TECNICA;
        }

        if (! empty($config['condiciones_sitio_activo'])) {
            $lista[] = self::ENUNCIADO_CONDICIONES_SITIO;
        }

        foreach ([
            'observaciones_adicionales_1',
            'observaciones_adicionales_2',
            'observaciones_adicionales_3',
            'observaciones_adicionales_4',
        ] as $key) {
            $txt = trim((string) ($config[$key] ?? ''));
            if ($txt !== '') {
                $lista[] = $txt;
            }
        }

        return $lista;
    }

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

    // /**
    //  * Marca el presupuesto como visto y la notificación del usuario como leída.
    //  * Sobrescribe el trait para también buscar notificaciones por presupuesto_id,
    //  * ya que cada usuario del proveedor tiene su propia notificación.
    //  */
    // public function markRead(?User $user = null): void
    // {
    //     MarksAsNotified::markRead($user);

    //     // Marcar también cualquier notificación del usuario actual que referencie este presupuesto
    //     if ($user) {
    //         $notification = $user->unreadNotifications()
    //             ->where('data->presupuesto_id', $this->id)
    //             ->first();
    //         if ($notification) {
    //             $notification->markAsRead();
    //         }
    //     }
    // }

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
            'proveedorReceptor',
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
     * Cliente de cartera del emisor cuando empresa_receptora_id es un id de {@see CarteraCliente}.
     */
    public function empresaReceptora(): BelongsTo
    {
        return $this->belongsTo(CarteraCliente::class, 'empresa_receptora_id');
    }

    /**
     * Proveedor del catálogo receptor (si aplica); mutuamente excluyente con cartera salvo datos legados.
     */
    public function proveedorReceptor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_receptor_id');
    }

    /**
     * Datos del bloque «Dirigido a» para PDF y API: si el receptor es proveedor de catálogo,
     * se completan desde {@see Proveedor} cuando faltan columnas en el presupuesto.
     *
     * @return array{nombre: ?string, puesto: ?string, empresa: ?string, alias_empresa: ?string, telefono: ?string, correo: ?string, direccion: ?string}
     */
    public function empresaReceptoraParaDocumento(): array
    {
        $prov = null;
        if ($this->proveedor_receptor_id) {
            $prov = $this->relationLoaded('proveedorReceptor')
                ? $this->proveedorReceptor
                : $this->proveedorReceptor()->first();
        }

        if ($prov) {
            $nombre = self::primerTextoNoVacio(
                $this->empresa_receptora_nombre,
                $prov->contacto_nombre,
                $prov->nombre_propietario,
                $prov->nombre_comercial,
                $prov->razon_social
            );
            $empresa = self::primerTextoNoVacio(
                $this->empresa_receptora_empresa,
                $prov->nombre_comercial,
                $prov->razon_social
            );
            $puesto = self::primerTextoNoVacio($this->empresa_receptora_puesto, $prov->contacto_cargo);
            $alias = self::primerTextoNoVacio($this->empresa_receptora_alias);
            $telefono = self::primerTextoNoVacio(
                $this->empresa_receptora_telefono,
                $prov->contacto_telefono,
                $prov->telefono,
                $prov->celular
            );
            $correo = self::primerTextoNoVacio(
                $this->empresa_receptora_correo,
                $prov->contacto_correo,
                $prov->email
            );
            $direccion = self::primerTextoNoVacio(
                $this->empresa_receptora_direccion,
                $prov->direccion_empresa
            );

            return [
                'nombre' => $nombre,
                'puesto' => $puesto,
                'empresa' => $empresa,
                'alias_empresa' => $alias,
                'telefono' => $telefono,
                'correo' => $correo,
                'direccion' => $direccion,
            ];
        }

        if ($this->empresa_receptora_id) {
            $this->loadMissing('empresaReceptora');
        }

        $cartera = $this->empresaReceptora;

        return [
            'nombre' => self::primerTextoNoVacio(
                $this->empresa_receptora_nombre,
                $cartera?->nombre
            ),
            'puesto' => self::primerTextoNoVacio(
                $this->empresa_receptora_puesto,
                $cartera?->puesto
            ),
            'empresa' => self::primerTextoNoVacio(
                $this->empresa_receptora_empresa,
                $cartera?->empresa
            ),
            'alias_empresa' => self::primerTextoNoVacio(
                $this->empresa_receptora_alias,
                $cartera?->alias_empresa
            ),
            'telefono' => self::primerTextoNoVacio(
                $this->empresa_receptora_telefono,
                $cartera?->telefono
            ),
            'correo' => self::primerTextoNoVacio(
                $this->empresa_receptora_correo,
                $cartera?->correo
            ),
            'direccion' => self::primerTextoNoVacio(
                $this->empresa_receptora_direccion,
                $cartera?->direccion
            ),
        ];
    }

    /**
     * @param  string|null  ...$candidatos
     */
    private static function primerTextoNoVacio(?string ...$candidatos): ?string
    {
        foreach ($candidatos as $c) {
            if ($c === null) {
                continue;
            }
            $t = trim((string) $c);
            if ($t !== '') {
                return $t;
            }
        }

        return null;
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
     * Restringe el query a los N presupuestos más recientes según created_at (y desempate por PK).
     * Debe aplicarse cuando ya están el resto de condiciones (proveedor, filtros, etc.).
     */
    public function scopeUltimasPresupuestos(Builder $query, int $n): Builder
    {
        if ($n <= 0) {
            return $query;
        }

        $model = $query->getModel();
        $table = $model->getTable();
        $pk = $model->getKeyName();

        $clone = clone $query;
        $clone->reorder();

        $ids = $clone
            ->orderByDesc("{$table}.created_at")
            ->orderByDesc("{$table}.{$pk}")
            ->limit($n)
            ->pluck("{$table}.{$pk}");

        if ($ids->isEmpty()) {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereIn("{$table}.{$pk}", $ids);
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
     * Filtro por proveedor receptor (presupuestos recibidos en el catálogo).
     */
    public function filterByProveedorReceptorId($query, $value)
    {
        return $query->whereIn('proveedor_receptor_id', explode(',', (string) $value));
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
     * Acepta valor único o varios separados por coma (ej: rechazado,vencido).
     */
    public function filterByEstado($query, $value)
    {
        if (empty($value)) {
            return $query;
        }
        $estados = array_map('trim', explode(',', (string) $value));
        $estados = array_filter($estados);

        return $estados === [] ? $query : $query->whereIn('estado', $estados);
    }

    /**
     * Filtro por segmento del listado.
     * observados: rechazado con motivo_rechazo y no visto (item_visto=false)
     * rechazados: el resto (rechazados sin motivo, vistos, o vencidos)
     */
    public function filterBySegmento($query, $value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return $query;
        }

        $estadosRechazados = [self::ESTADO_RECHAZADO, self::ESTADO_RECHAZADO_CON_OBSERVACION];

        if ($value === 'observados') {
            return $query
                ->whereIn('estado', $estadosRechazados)
                ->whereNotNull('motivo_rechazo')
                ->whereRaw('TRIM(motivo_rechazo) != ?', [''])
                ->where(function ($q) {
                    $q->where('item_visto', false)->orWhereNull('item_visto');
                });
        }

        if ($value === 'rechazados') {
            return $query->where(function ($q) use ($estadosRechazados) {
                $q->whereIn('estado', array_merge($estadosRechazados, [self::ESTADO_VENCIDO]))
                    ->where(function ($sub) {
                        // Sin motivo O está visto
                        $sub->whereNull('motivo_rechazo')
                            ->orWhereRaw('TRIM(COALESCE(motivo_rechazo, "")) = ?', [''])
                            ->orWhere('item_visto', true);
                    });
            });
        }

        return $query;
    }

    /**
     * Filtro por item_visto (presupuesto visto/no visto).
     */
    public function filterByItemVisto($query, $value)
    {
        $boolValue = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($boolValue === null) {
            return $query;
        }

        return $query->where('item_visto', $boolValue);
    }

    public function filterByUltimasPresupuestos($query, $value)
    {
        return $query->ultimasPresupuestos((int) $value);
    }
}
