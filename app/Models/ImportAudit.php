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
    'formato',
    'estado',
    'fase',
    'logs',
    'eta_seconds',
    'mem_peak_mb',
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
    'logs' => 'array',
    'inicio_proceso' => 'datetime',
    'fin_proceso' => 'datetime'
  ];

  public function proveedor()
  {
    return $this->belongsTo(Proveedor::class);
  }

  /**
   * Append a timestamped log entry to the logs array
   *
   * @param string $message
   * @param array $context
   * @return $this
   */
  public function appendLog(string $message, array $context = []): self
  {
    $logs = $this->logs ?? [];
    
    $logEntry = [
      'timestamp' => now()->toISOString(),
      'message' => $message,
    ];
    
    if (!empty($context)) {
      $logEntry['context'] = $context;
    }
    
    $logs[] = $logEntry;
    
    $this->logs = $logs;
    
    return $this;
  }
}
