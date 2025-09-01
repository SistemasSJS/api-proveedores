<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudPago extends BaseModel
{
    use HasFactory, Filterable;

    /**
     * Nombre de la tabla
     */
    protected $table = 'solicitudes_pago';

    /**
     * Campos asignables masivamente
     */
    protected $fillable = [
        'numero_folio_solicitud',
        'descripcion_concepto',
        'ruta_archivo_factura_xml',
        'ruta_archivo_factura_pdf',
        'estado_solicitud',
        'ruta_archivo_comprobante_pago',
        'id_proveedor',
        'fecha_registro_pendiente',
        'fecha_inicio_procesamiento',
        'fecha_confirmacion_pago',
    ];

    /**
     * Filtros disponibles
     */
    protected static $filters = [
        'numero_folio_solicitud'   => 'NumeroFolioSolicitud',
        'descripcion_concepto'     => 'DescripcionConcepto',
        'estado_solicitud'         => 'EstadoSolicitud',
        'id_proveedor'             => 'ProveedorId',
        'fecha_registro_pendiente' => 'FechaRegistroPendiente',
        'fecha_inicio_procesamiento' => 'FechaInicioProcesamiento',
        'fecha_confirmacion_pago'  => 'FechaConfirmacionPago',
    ];

    /**
     * Casts de atributos
     */
    protected $casts = [
        'fecha_registro_pendiente'  => 'datetime',
        'fecha_inicio_procesamiento' => 'datetime',
        'fecha_confirmacion_pago'   => 'datetime',
        'created_at'                => 'datetime',
        'updated_at'                => 'datetime',
    ];

    /** ----------------
     * Relaciones
     * ----------------- */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor');
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
        return $query->whereIn('id_proveedor', explode(',', $value));
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
}
