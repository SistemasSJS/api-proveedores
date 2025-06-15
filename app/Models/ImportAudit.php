<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportAudit extends BaseModel
{

  protected $fillable = [
    'job_id',
    'proveedor_id',
    'tipo',
    'archivo',
    'estado',
    'total_registros',
    'nuevos',
    'actualizados',
    'eliminados',
    'errores',
    'preview_data',
    'errores_detalle',
    'progreso',
    'inicio_proceso',
    'fin_proceso'
  ];

  protected $casts = [
    'preview_data' => 'array',
    'errores_detalle' => 'array',
    'inicio_proceso' => 'datetime',
    'fin_proceso' => 'datetime'
  ];

  public function proveedor()
  {
    return $this->belongsTo(Proveedor::class);
  }
}
