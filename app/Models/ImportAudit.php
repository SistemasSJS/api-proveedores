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
   * Get the filterable fields
   */
  public static function getFilters(): array
  {
    return [
      'search', 'tipo', 'estado', 'formato', 'date_from', 'date_to',
      'fase', 'has_errors', 'min_registros', 'max_registros'
    ];
  }

  /**
   * Apply filters to query
   */
  public function scopeFilter($query, array $filters)
  {
    return $query
      ->when($filters['search'] ?? null, function ($query, $search) {
        $query->where(function ($query) use ($search) {
          $query->where('archivo', 'like', "%{$search}%")
                ->orWhere('job_id', 'like', "%{$search}%")
                ->orWhere('tipo', 'like', "%{$search}%")
                ->orWhereJsonContains('logs', ['message' => $search]);
        });
      })
      ->when($filters['tipo'] ?? null, function ($query, $tipo) {
        $query->where('tipo', $tipo);
      })
      ->when($filters['estado'] ?? null, function ($query, $estado) {
        $query->where('estado', $estado);
      })
      ->when($filters['formato'] ?? null, function ($query, $formato) {
        $query->where('formato', $formato);
      })
      ->when($filters['fase'] ?? null, function ($query, $fase) {
        $query->where('fase', $fase);
      })
      ->when(isset($filters['has_errors']), function ($query) use ($filters) {
        if ($filters['has_errors']) {
          $query->where('errores', '>', 0);
        } else {
          $query->where('errores', '=', 0);
        }
      })
      ->when($filters['min_registros'] ?? null, function ($query, $min) {
        $query->where('total_registros', '>=', $min);
      })
      ->when($filters['max_registros'] ?? null, function ($query, $max) {
        $query->where('total_registros', '<=', $max);
      })
      ->when($filters['date_from'] ?? null, function ($query, $dateFrom) {
        $query->whereDate('created_at', '>=', $dateFrom);
      })
      ->when($filters['date_to'] ?? null, function ($query, $dateTo) {
        $query->whereDate('created_at', '<=', $dateTo);
      });
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
