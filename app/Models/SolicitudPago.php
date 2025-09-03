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
        'sucursal_id',
        'fecha_registro_pendiente',
        'fecha_inicio_procesamiento',
        'fecha_confirmacion_pago',
    ];

    protected static $filters = [
        'numero_folio_solicitud'   => 'NumeroFolioSolicitud',
        'descripcion_concepto'     => 'DescripcionConcepto',
        'estado_solicitud'         => 'EstadoSolicitud',
        'proveedor_id'             => 'ProveedorId',
        'fecha_registro_pendiente' => 'FechaRegistroPendiente',
        'fecha_inicio_procesamiento' => 'FechaInicioProcesamiento',
        'fecha_confirmacion_pago'  => 'FechaConfirmacionPago',
    ];

    protected $casts = [
        'fecha_registro_pendiente'   => 'datetime',
        'fecha_inicio_procesamiento' => 'datetime',
        'fecha_confirmacion_pago'    => 'datetime',
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
}
