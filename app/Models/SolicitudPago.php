<?php

namespace App\Models;

use App\Enums\EstadoCuentaBancaria;
use App\Enums\EstadoSolicitud;
use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;


class SolicitudPago extends BaseModel
{
    use Filterable, HasFactory;

    protected $connection = 'mysql5';
    protected $table = 'solicitudes_pago';

    protected $fillable = [
        'numero_folio_solicitud', // CONSECUTIVO POR PROVEEDOR
        'folio_sp_consecutivo', // MANEJO INTERNO cONSTRUCC
        'descripcion_concepto',
        'ruta_archivo_factura_xml',
        'ruta_archivo_factura_pdf',
        'ruta_archivo_cotizacion',
        'estado_solicitud',
        'ruta_archivo_comprobante_pago',
        'proveedor_id',
        // Datos del usuario que registra la SP en Construcc
        'empresa_construcc_id',
        'usuario_id',
        'usuario_nombre',
        'cuenta_bancaria_empresa_construcc_id',
        // 
        'cotizacion_id',
        'sucursal_id',
        // 'fecha_registro_pendiente',
        // 'fecha_inicio_procesamiento',
        // 'fecha_confirmacion_pago',
        // 'fecha_con_comprobante',
        // 'fecha_rechazado',
        // 'fecha_aprobado',
        'motivo_rechazo',
        'monto_total',
        'monto_abonado',
        'saldo_pendiente',
        'pago_completo',
        'verificada',
        'tipo',
        // FIXME: tipo_id se migrara a una tabla y este almacenara el id asignado al tipo de SP
        // valores actuales que se aklmacenana en tipo como string: 1: DIRECTA, 2: REQUISICION
        'tipo_id',
        'obra_id',
        'observaciones',
        'notas',
        'utilizara',
        'equipo',
        'notas_abono',
        'fecha_rechazo',
        'fecha_pago',
        'notification_id',

        // Nuevos campos
        'dg',
        'dg_fecha',
        'dt',
        'dt_fecha',
        'pc',
        'pc_fecha',
        'si',
        'si_fecha',
        'da',
        'da_fecha',
        'ro',
        'ro_fecha',

        // Campos de tracking para OC
        'referencia_oc',
        'origen_oc',
        'monto_oc_original',

        // 
        'visto_rechazada',

        // 
        'folio_factura',
        'datos_factura_xml',
    ];

    protected static $filters = [
        'search' => 'Search',
        'numero_folio_solicitud' => 'NumeroFolioSolicitud',
        'folio_factura' => 'FolioFactura',
        'descripcion_concepto' => 'DescripcionConcepto',
        'estado_solicitud' => 'EstadoSolicitud',
        'proveedor_id' => 'ProveedorId',
        'empresa_construcc_id' => 'EmpresaConstruccId',
        'usuario_id' => 'UsuarioId',
        'usuario_nombre' => 'UsuarioNombre',
        'cotizacion_id' => 'CotizacionId',

        'fecha_registro_pendiente' => 'FechaRegistroPendiente',
        'fecha_registro_pendiente_desde' => 'FechaRegistroPendienteDesde',
        'fecha_registro_pendiente_hasta' => 'FechaRegistroPendienteHasta',
        'fecha_inicio_procesamiento' => 'FechaInicioProcesamiento',
        'fecha_inicio_procesamiento_desde' => 'FechaInicioProcesamientoDesde',
        'fecha_inicio_procesamiento_hasta' => 'FechaInicioProcesamientoHasta',
        'fecha_confirmacion_pago' => 'FechaConfirmacionPago',
        'fecha_confirmacion_pago_desde' => 'FechaConfirmacionPagoDesde',
        'fecha_confirmacion_pago_hasta' => 'FechaConfirmacionPagoHasta',
        'fecha_con_comprobante' => 'FechaConComprobante',
        'fecha_con_comprobante_desde' => 'FechaConComprobanteDesde',
        'fecha_con_comprobante_hasta' => 'FechaConComprobanteHasta',
        'fecha_aprobado' => 'FechaAprobado',
        'fecha_aprobado_desde' => 'FechaAprobadoDesde',
        'fecha_aprobado_hasta' => 'FechaAprobadoHasta',
        'fecha_rechazo' => 'FechaRechazo',
        'fecha_rechazo_desde' => 'FechaRechazoDesde',
        'fecha_rechazo_hasta' => 'FechaRechazo1Hasta',
        'fecha_pago' => 'FechaPago',
        'fecha_pago_desde' => 'FechaPagoDesde',
        'fecha_pago_hasta' => 'FechaPagoHasta',

        // Filtros para los nuevos campos
        'dg' => 'Dg',
        'dt' => 'Dt',
        'pc' => 'Pc',
        'si' => 'Si',
        'da' => 'Da',
        'ro' => 'Ro',

        // Filtros para campos OC
        'referencia_oc' => 'ReferenciaOc',
        'origen_oc' => 'OrigenOc',

        // Filtro para verificada
        'verificada' => 'Verificada',

        // Filtros para tipo, tipo_id y obra_id
        'tipo' => 'Tipo',
        'tipo_id' => 'TipoId',
        'obra_id' => 'ObraId',

        //
        'visto_rechazada' => 'VistoRechazada',
    ];

    protected $casts = [
        'fecha_registro_pendiente' => 'datetime',
        'fecha_inicio_procesamiento' => 'datetime',
        'fecha_confirmacion_pago' => 'datetime',
        'fecha_con_comprobante' => 'datetime',
        'fecha_aprobado' => 'datetime',
        'fecha_rechazo' => 'datetime',
        'fecha_pago' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',

        // Nuevos campos como enum numérico
        'dg' => EstadoSolicitud::class,
        'dg_fecha' => 'datetime',
        'dt' => EstadoSolicitud::class,
        'dt_fecha' => 'datetime',
        'pc' => EstadoSolicitud::class,
        'pc_fecha' => 'datetime',
        'si' => EstadoSolicitud::class,
        'si_fecha' => 'datetime',
        'da' => EstadoSolicitud::class,
        'da_fecha' => 'datetime',
        'ro' => EstadoSolicitud::class,
        'ro_fecha' => 'datetime',

        // Campos de abono
        'monto_total' => 'decimal:2',
        'monto_abonado' => 'decimal:2',
        'saldo_pendiente' => 'decimal:2',
        'pago_completo' => 'boolean',
        'verificada' => 'boolean',

        // Campos de tracking OC
        'origen_oc' => 'boolean',
        'monto_oc_original' => 'decimal:2',

        'visto_rechazada' => 'boolean',

        // Datos XML como JSON
        'datos_factura_xml' => 'array',
    ];

    /** ----------------
     * Eager loading disponible
     * ----------------- */
    public static function eagerLodable(): array
    {
        return [
            'proveedor',
            'sucursal',
            'empresaConstrucc',
            'cotizacion',
            'cuentasBancarias',
            'ordenCompra',
        ];
    }

    /** ----------------
     * Relaciones
     * ----------------- */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function empresaConstrucc(): BelongsTo
    {
        return $this->belongsTo(EmpresaConstrucc::class, 'empresa_construcc_id');
    }

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class, 'cotizacion_id')->with('detalles');
    }

    public function cuentasBancarias(): HasMany
    {
        return $this->hasMany(SolicitudPagoCuentaBancaria::class);
    }

    public function ordenCompra(): BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class, 'referencia_oc', 'numero_orden');
    }

    /** ----------------
     * Filtros básicos (ejemplo nuevos)
     * ----------------- */
    public function filterByDg($query, $value)
    {
        return $query->where('dg', $value instanceof EstadoSolicitud ? $value->value : $value);
    }

    public function filterByDt($query, $value)
    {
        return $query->where('dt', $value instanceof EstadoSolicitud ? $value->value : $value);
    }

    public function filterByPc($query, $value)
    {
        return $query->where('pc', $value instanceof EstadoSolicitud ? $value->value : $value);
    }

    public function filterBySi($query, $value)
    {
        return $query->where('si', $value instanceof EstadoSolicitud ? $value->value : $value);
    }

    public function filterByDa($query, $value)
    {
        return $query->where('da', $value instanceof EstadoSolicitud ? $value->value : $value);
    }

    public function filterByRo($query, $value)
    {
        return $query->where('ro', $value instanceof EstadoSolicitud ? $value->value : $value);
    }

    public function filterByReferenciaOc($query, $value)
    {
        return $query->where('referencia_oc', 'like', "%{$value}%");
    }

    public function filterByOrigenOc($query, $value)
    {
        return $query->where('origen_oc', (bool) $value);
    }

    public function filterByVerificada($query, $value)
    {
        return $query->where('verificada', (bool) $value);
    }

    public function filterByTipo($query, $value)
    {
        return $query->where('tipo', $value);
    }

    public function filterByTipoId($query, $value)
    {
        return $query->where('tipo_id', $value);
    }

    public function filterByObraId($query, $value)
    {
        return $query->where('obra_id', $value);
    }

    /** ----------------
     * Filtros
     * ----------------- */

    /**
     * Filtro de búsqueda global
     * Busca en múltiples campos: folio, concepto, observaciones, usuario, referencia OC y empresa
     */
    public function filterBySearch($query, $value)
    {
        return $query->where(function ($q) use ($value) {
            $q->where('numero_folio_solicitud', 'like', "%$value%")
                ->orWhere('folio_factura', 'like', "%$value%")
                ->orWhere('descripcion_concepto', 'like', "%$value%")
                ->orWhere('observaciones', 'like', "%$value%")
                ->orWhere('usuario_nombre', 'like', "%$value%")
                ->orWhere('referencia_oc', 'like', "%$value%")
                ->orWhereHas('empresaConstrucc', function ($empresa) use ($value) {
                    $empresa->where('nombre', 'like', "%$value%");
                });
        });
    }

    public function filterByNumeroFolioSolicitud($query, $value)
    {
        return $query->where('numero_folio_solicitud', 'like', "%$value%");
    }

    public function filterByDescripcionConcepto($query, $value)
    {
        return $query->where('descripcion_concepto', 'like', "%$value%");
    }

    public function filterByEstadoSolicitud($query, $value)
    {
        return $query->where('estado_solicitud', $value);
    }

    public function filterByProveedorId($query, $value)
    {
        return $query->whereIn('proveedor_id', explode(',', $value));
    }

    public function filterByFechaRegistroPendiente($query, $value)
    {
        return $query->whereDate('fecha_registro_pendiente', $value);
    }

    public function filterByFechaInicioProcesamiento($query, $value)
    {
        return $query->whereDate('fecha_inicio_procesamiento', $value);
    }

    public function filterByFechaConfirmacionPago($query, $value)
    {
        return $query->whereDate('fecha_confirmacion_pago', $value);
    }

    public function filterByEmpresaConstruccId($query, $value)
    {
        // Evitar aplicar el filtro cuando el valor viene vacío (",", null, espacios, etc.)
        $ids = array_filter(
            is_array($value) ? $value : explode(',', (string) $value),
            fn($id) => trim((string) $id) !== ''
        );

        if (empty($ids)) {
            // Si no hay IDs válidos, no filtramos para evitar consultas "colgadas"
            return $query;
        }

        return $query->whereIn('empresa_construcc_id', $ids);
    }

    public function filterByUsuarioId($query, $value)
    {
        // Evitar aplicar el filtro cuando el valor viene vacío (",", null, espacios, etc.)
        $ids = array_filter(
            is_array($value) ? $value : explode(',', (string) $value),
            fn($id) => trim((string) $id) !== ''
        );

        if (empty($ids)) {
            // Si no hay IDs válidos, no filtramos para evitar consultas "colgadas"
            return $query;
        }

        return $query->whereIn('usuario_id', $ids);
        // return $query->where('usuario_id', $value);
    }

    public function filterByUsuarioNombre($query, $value)
    {
        return $query->where('usuario_nombre', 'like', "%$value%");
    }

    public function filterByCotizacionId($query, $value)
    {
        return $query->whereIn('cotizacion_id', explode(',', $value));
    }

    /** ----------------
     * Filtros por rango de fechas
     * ----------------- */
    public function filterByFechaRegistroPendienteDesde($query, $value)
    {
        return $query->whereDate('fecha_registro_pendiente', '>=', $value);
    }

    public function filterByFechaRegistroPendienteHasta($query, $value)
    {
        return $query->whereDate('fecha_registro_pendiente', '<=', $value);
    }

    public function filterByFechaInicioProcesamientoDesde($query, $value)
    {
        return $query->whereDate('fecha_inicio_procesamiento', '>=', $value);
    }

    public function filterByFechaInicioProcesamientoHasta($query, $value)
    {
        return $query->whereDate('fecha_inicio_procesamiento', '<=', $value);
    }

    public function filterByFechaConfirmacionPagoDesde($query, $value)
    {
        return $query->whereDate('fecha_confirmacion_pago', '>=', $value);
    }

    public function filterByFechaConfirmacionPagoHasta($query, $value)
    {
        return $query->whereDate('fecha_confirmacion_pago', '<=', $value);
    }

    public function filterByFechaConComprobante($query, $value)
    {
        return $query->whereDate('fecha_con_comprobante', $value);
    }

    public function filterByFechaConComprobanteDesde($query, $value)
    {
        return $query->whereDate('fecha_con_comprobante', '>=', $value);
    }

    public function filterByFechaConComprobanteHasta($query, $value)
    {
        return $query->whereDate('fecha_con_comprobante', '<=', $value);
    }

    public function filterByFechaAprobado($query, $value)
    {
        return $query->whereDate('fecha_aprobado', $value);
    }

    public function filterByFechaAprobadoDesde($query, $value)
    {
        return $query->whereDate('fecha_aprobado', '>=', $value);
    }

    public function filterByFechaAprobadoHasta($query, $value)
    {
        return $query->whereDate('fecha_aprobado', '<=', $value);
    }

    public function filterByFechaRechazo($query, $value)
    {
        return $query->whereDate('fecha_rechazo', $value);
    }

    public function filterByFechaRechazoDesde($query, $value)
    {
        return $query->whereDate('fecha_rechazo', '>=', $value);
    }

    public function filterByFechaRechazoHasta($query, $value)
    {
        return $query->whereDate('fecha_rechazo', '<=', $value);
    }

    public function filterByFechaPago($query, $value)
    {
        return $query->whereDate('fecha_pago', $value);
    }

    public function filterByFechaPagoDesde($query, $value)
    {
        return $query->whereDate('fecha_pago', '>=', $value);
    }

    public function filterByFechaPagoHasta($query, $value)
    {
        return $query->whereDate('fecha_pago', '<=', $value);
    }

    /** ----------------
     * Métodos para manejo de pagos parciales
     * ----------------- */
    public function actualizarSaldos($montoAbono)
    {
        $nuevoMontoAbonado = $this->monto_abonado + $montoAbono;
        $nuevoSaldoPendiente = $this->monto_total - $nuevoMontoAbonado;
        $pagoCompleto = $nuevoSaldoPendiente <= 0;

        $this->update([
            'monto_abonado' => $nuevoMontoAbonado,
            'saldo_pendiente' => max(0, $nuevoSaldoPendiente),
            'pago_completo' => $pagoCompleto,
        ]);

        return $pagoCompleto;
    }

    public function inicializarSaldos()
    {
        if ($this->saldo_pendiente == 0 && $this->monto_abonado == 0) {
            $this->update([
                'saldo_pendiente' => $this->monto_total,
                'monto_abonado' => 0,
                'pago_completo' => false,
            ]);
        }
    }

    public function filterByVistoRechazada($query, $value)
    {
        return $query->where('visto_rechazada', (bool) $value);
    }

    public function filterByFolioFactura($query, $value)
    {
        return $query->where('folio_factura', 'like', "%{$value}%");
    }

    /**
     * Sincroniza las cuentas bancarias de la solicitud de pago
     * Si no se especifica un método, se da de baja
     */
    public function sincronizarCuentasBancarias(array $cuentasBancarias)
    {
        // Obtener IDs de cuentas bancarias actuales
        $idsActuales = $this->cuentasBancarias()->pluck('cuenta_bancaria_id')->toArray();

        // Obtener IDs de cuentas bancarias del array
        $idsNuevos = collect($cuentasBancarias)->pluck('cuenta_bancaria_id')->toArray();

        // Eliminar cuentas que ya no están en el array
        $idsAEliminar = array_diff($idsActuales, $idsNuevos);
        $this->cuentasBancarias()->whereIn('cuenta_bancaria_id', $idsAEliminar)->delete();

        // Actualizar o crear cuentas bancarias
        foreach ($cuentasBancarias as $cuentaData) {
            $cuentaBancaria = CuentaBancaria::find($cuentaData['cuenta_bancaria_id']);
            if (! $cuentaBancaria) {
                continue;
            }

            // Preparar datos para la tabla pivote
            $pivotData = [
                'alias' => $cuentaBancaria->alias,
                'banco_clave' => $cuentaBancaria->banco_clave,
                'banco_nombre' => $cuentaBancaria->banco_nombre,
                'tipo_cuenta' => $cuentaBancaria->tipo_cuenta,
                'campo_dependiente' => $cuentaBancaria->campo_dependiente,
                'titular_cuenta' => $cuentaBancaria->titular_cuenta,
                'referencia' => $cuentaBancaria->referencia ?? '',
                // 'estatus' => EstadoCuentaBancaria::ACTIVA->value,
                'sucursal' => $cuentaBancaria->sucursal,
                'swift' => $cuentaBancaria->swift,
                'preferida' => $cuentaBancaria->preferida,
            ];

            // Si hay datos específicos en el array, los sobrescribe
            if (isset($cuentaData['datos_especificos'])) {
                $pivotData = array_merge($pivotData, $cuentaData['datos_especificos']);
            }

            // Actualizar o crear en la tabla pivote
            $this->cuentasBancarias()->updateOrCreate(
                ['cuenta_bancaria_id' => $cuentaData['cuenta_bancaria_id']],
                $pivotData
            );
        }
    }

    /** ----------------
     * Utilidades s
     * ----------------- */

    /**
     * Generar siguiente número de folio para una nueva solicitud de pago para un proveedor
     * nopmeclatura:
     *  SP-
     *  proveedor abrevicado en tres letras mayusculas, sacadas paartir del nomnre comerical
     *  seguido de un guion
     *  6 digitos consecutivos, iniciando en 00001
     *  pasado los numoers posibles con 6 digitos, se aumenta a 7 digitos y asi sucesivamente
     *
     *
     * ej. SP-ABC-000001
     */
    public static function generarNumeroFolio(Proveedor $proveedor)
    {
        // Contamos cuántas solicitudes tiene el proveedor
        $count = self::where('proveedor_id', $proveedor->id)->count();

        // El siguiente número será count + 1
        $siguienteNumero = $count + 1;

        // Tomamos las primeras 3 letras del nombre comercial, solo letras
        $proveedorClave = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $proveedor->nombre_comercial), 0, 3));

        // Generamos el folio con formato SP-ABC-000001
        // return sprintf('SP-%s-%06d', $proveedorClave, $siguienteNumero);
        return sprintf('%04d', $siguienteNumero);
    }


    /** ----------------
     * Métodos de negocio para Órdenes de Compra
     * ----------------- */
    public function esDeOrdenCompra(): bool
    {
        return $this->origen_oc ?? true;
    }

    public function scopeWhereFromOrdenCompra(Builder $query): Builder
    {
        return $query->where('origen_oc', true);
    }

    public function getOrdenCompraAsociada()
    {
        return $this->ordenCompra;
    }

    public function validarMontoContraOC(): bool
    {
        return true;
        if (! $this->esDeOrdenCompra() || ! $this->monto_oc_original) {
            return true; // No aplica validación si no es de OC
        }

        return $this->monto_total <= $this->monto_oc_original;
    }
}
