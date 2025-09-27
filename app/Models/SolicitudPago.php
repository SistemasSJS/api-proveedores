<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudPago extends BaseModel
{
    use HasFactory, Filterable;

    protected $table = 'solicitudes_pago';

    protected $fillable = [
        'numero_folio_solicitud',
        'descripcion_concepto',
        'ruta_archivo_factura_xml',
        'ruta_archivo_factura_pdf',
        'estado_solicitud',
        'ruta_archivo_comprobante_pago',
        'proveedor_id',
        'empresa_construcc_id',
        'residente',
        'cotizacion_id',
        'sucursal_id',
        'fecha_registro_pendiente',
        'fecha_inicio_procesamiento',
        'fecha_confirmacion_pago',
        'fecha_con_comprobante',
        'fecha_rechazado',
        'fecha_aprobado',
        'motivo_rechazo',
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
        'fecha_rechazado'                 => 'FechaRechazado',
        'fecha_rechazado_desde'           => 'FechaRechazadoDesde',
        'fecha_rechazado_hasta'           => 'FechaRechazadoHasta',
        'fecha_aprobado'                  => 'FechaAprobado',
        'fecha_aprobado_desde'            => 'FechaAprobadoDesde',
        'fecha_aprobado_hasta'            => 'FechaAprobadoHasta',
    ];

    protected $casts = [
        'fecha_registro_pendiente'   => 'datetime',
        'fecha_inicio_procesamiento' => 'datetime',
        'fecha_confirmacion_pago'    => 'datetime',
        'fecha_con_comprobante'      => 'datetime',
        'fecha_rechazado'            => 'datetime',
        'fecha_aprobado'             => 'datetime',
        'created_at'                 => 'datetime',
        'updated_at'                 => 'datetime',
    ];

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

    public function filterByFechaRechazado($query, $value)
    {
        return $query->whereDate('fecha_rechazado', $value);
    }

    public function filterByFechaRechazadoDesde($query, $value)
    {
        return $query->whereDate('fecha_rechazado', '>=', $value);
    }

    public function filterByFechaRechazadoHasta($query, $value)
    {
        return $query->whereDate('fecha_rechazado', '<=', $value);
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
}
