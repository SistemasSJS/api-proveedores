<?php

namespace App\Models;

use App\Enums\EstadoCuentaBancaria;
use App\Enums\EstadoSolicitud;
use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SolicitudPago extends BaseModel
{
    use HasFactory, Filterable;

    protected $table = 'solicitudes_pago';

    protected $fillable = [
        'numero_folio_solicitud',
        'descripcion_concepto',
        'ruta_archivo_factura_xml',
        'ruta_archivo_factura_pdf',
        'ruta_archivo_cotizacion',
        'estado_solicitud',
        'ruta_archivo_comprobante_pago',
        'proveedor_id',
        'empresa_construcc_id',
        'residente',
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
        'notas_abono',
        'fecha_rechazo',
        'fecha_pago',

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
    ];

    protected static $filters = [
        'numero_folio_solicitud'          => 'NumeroFolioSolicitud',
        'descripcion_concepto'            => 'DescripcionConcepto',
        'estado_solicitud'                => 'EstadoSolicitud',
        'proveedor_id'                    => 'ProveedorId',
        'empresa_construcc_id'            => 'EmpresaConstruccId',
        'residente'                       => 'Residente',
        'cotizacion_id'                   => 'CotizacionId',

        'fecha_registro_pendiente'        => 'FechaRegistroPendiente',
        'fecha_registro_pendiente_desde'  => 'FechaRegistroPendienteDesde',
        'fecha_registro_pendiente_hasta'  => 'FechaRegistroPendienteHasta',
        'fecha_inicio_procesamiento'      => 'FechaInicioProcesamiento',
        'fecha_inicio_procesamiento_desde' => 'FechaInicioProcesamientoDesde',
        'fecha_inicio_procesamiento_hasta' => 'FechaInicioProcesamientoHasta',
        'fecha_confirmacion_pago'         => 'FechaConfirmacionPago',
        'fecha_confirmacion_pago_desde'   => 'FechaConfirmacionPagoDesde',
        'fecha_confirmacion_pago_hasta'   => 'FechaConfirmacionPagoHasta',
        'fecha_con_comprobante'           => 'FechaConComprobante',
        'fecha_con_comprobante_desde'     => 'FechaConComprobanteDesde',
        'fecha_con_comprobante_hasta'     => 'FechaConComprobanteHasta',
        'fecha_aprobado'                  => 'FechaAprobado',
        'fecha_aprobado_desde'            => 'FechaAprobadoDesde',
        'fecha_aprobado_hasta'            => 'FechaAprobadoHasta',
        'fecha_rechazo'                   => 'FechaRechazo',
        'fecha_rechazo_desde'             => 'FechaRechazoDesde',
        'fecha_rechazo_hasta'             => 'FechaRechazoHasta',
        'fecha_pago'                      => 'FechaPago',
        'fecha_pago_desde'                => 'FechaPagoDesde',
        'fecha_pago_hasta'                => 'FechaPagoHasta',

        // Filtros para los nuevos campos
        'dg'                              => 'Dg',
        'dt'                              => 'Dt',
        'pc'                              => 'Pc',
        'si'                              => 'Si',
        'da'                              => 'Da',
        'ro'                              => 'Ro',

        //
    ];

    protected $casts = [
        'fecha_registro_pendiente'   => 'datetime',
        'fecha_inicio_procesamiento' => 'datetime',
        'fecha_confirmacion_pago'    => 'datetime',
        'fecha_con_comprobante'      => 'datetime',
        'fecha_aprobado'             => 'datetime',
        'fecha_rechazo'              => 'datetime',
        'fecha_pago'                 => 'datetime',
        'created_at'                 => 'datetime',
        'updated_at'                 => 'datetime',

        // Nuevos campos como enum numérico
        'dg'        => EstadoSolicitud::class,
        'dg_fecha'  => 'datetime',
        'dt'        => EstadoSolicitud::class,
        'dt_fecha'  => 'datetime',
        'pc'        => EstadoSolicitud::class,
        'pc_fecha'  => 'datetime',
        'si'        => EstadoSolicitud::class,
        'si_fecha'  => 'datetime',
        'da'        => EstadoSolicitud::class,
        'da_fecha'  => 'datetime',
        'ro'        => EstadoSolicitud::class,
        'ro_fecha'  => 'datetime',

        // Campos de abono
        'monto_total' => 'decimal:2',
        'monto_abonado' => 'decimal:2',
        'saldo_pendiente' => 'decimal:2',
        'pago_completo' => 'boolean',
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


    /** ----------------
     * Filtros
     * ----------------- */
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
        return $query->whereIn('empresa_construcc_id', explode(',', $value));
    }

    public function filterByResidente($query, $value)
    {
        return $query->where('residente', 'like', "%$value%");
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
            'pago_completo' => $pagoCompleto
        ]);

        return $pagoCompleto;
    }

    public function inicializarSaldos()
    {
        if ($this->saldo_pendiente == 0 && $this->monto_abonado == 0) {
            $this->update([
                'saldo_pendiente' => $this->monto_total,
                'monto_abonado' => 0,
                'pago_completo' => false
            ]);
        }
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
            if (!$cuentaBancaria) continue;

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
        $ultimoFolio = self::where('proveedor_id', $proveedor->id)
            ->orderByDesc('id')
            ->value('numero_folio_solicitud');

        if ($ultimoFolio) {
            $numero = (int) filter_var($ultimoFolio, FILTER_SANITIZE_NUMBER_INT);
            $siguienteNumero = $numero + 1;
        } else {
            $siguienteNumero = 1;
        }

        $proveedorClave = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $proveedor->nombre_comercial), 0, 3));

        return sprintf('SP-%s-%06d', $proveedorClave, $siguienteNumero);
    }
}
