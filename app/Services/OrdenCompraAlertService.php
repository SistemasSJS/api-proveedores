<?php

namespace App\Services;

use App\Enums\EstadoOrdenCompra;
use App\Models\OrdenCompra;
use App\Models\Proveedor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class OrdenCompraAlertService
{
    protected array $thresholds;

    public function __construct()
    {
        $this->thresholds = config('ordenes-compra.alertas.thresholds', [
            'warning' => 7,
            'danger' => 15
        ]);
    }

    /**
     * Obtener órdenes de compra sin SP por días transcurridos
     */
    public function getOrdenesCompraSinSP(
        ?Proveedor $proveedor = null,
        ?int $diasMinimo = null,
        ?string $nivelAlerta = null
    ): Collection {
        $diasMinimo = $diasMinimo ?? $this->thresholds['warning'];

        $query = OrdenCompra::query()
            ->sinSolicitudesPago()
            ->aprobadas()
            ->with(['proveedor', 'empresaConstrucc'])
            ->selectRaw('ordenes_compra.*, 
                DATEDIFF(NOW(), COALESCE(fecha_aprobacion, created_at)) as dias_sin_sp')
            ->havingRaw('dias_sin_sp >= ?', [$diasMinimo]);

        if ($proveedor) {
            $query->where('proveedor_id', $proveedor->id);
        }

        $ordenes = $query->orderByRaw('dias_sin_sp DESC')->get();

        // Filtrar por nivel de alerta si se especifica
        if ($nivelAlerta) {
            $ordenes = $ordenes->filter(function ($orden) use ($nivelAlerta) {
                return $this->getNivelAlerta($orden->dias_sin_sp) === $nivelAlerta;
            });
        }

        // Agregar información de alerta
        return $ordenes->map(function ($orden) {
            $orden->nivel_alerta = $this->getNivelAlerta($orden->dias_sin_sp);
            $orden->mensaje_alerta = $this->getMensajeAlerta($orden->dias_sin_sp);
            $orden->prioridad = $this->getPrioridad($orden->dias_sin_sp);
            return $orden;
        });
    }

    /**
     * Calcular días desde aprobación
     */
    public function calculateDaysSinceApproval(OrdenCompra $ordenCompra): int
    {
        $fechaBase = $ordenCompra->fecha_aprobacion ?? $ordenCompra->created_at;
        return $fechaBase->diffInDays(now());
    }

    /**
     * Obtener alertas específicas por proveedor
     */
    public function getAlertasProveedor(Proveedor $proveedor): array
    {
        $cacheKey = "alertas_proveedor_{$proveedor->id}";
        
        return Cache::remember($cacheKey, 300, function () use ($proveedor) { // 5 minutos
            $ordenesSinSP = $this->getOrdenesCompraSinSP($proveedor);
            
            $alertas = [
                'total_alertas' => $ordenesSinSP->count(),
                'alertas_warning' => $ordenesSinSP->where('nivel_alerta', 'warning')->count(),
                'alertas_danger' => $ordenesSinSP->where('nivel_alerta', 'danger')->count(),
                'mas_urgente' => null,
                'resumen_por_empresa' => [],
                'ordenes_criticas' => []
            ];

            if ($ordenesSinSP->isNotEmpty()) {
                // Orden más urgente
                $alertas['mas_urgente'] = $ordenesSinSP->first();

                // Resumen por empresa
                $alertas['resumen_por_empresa'] = $ordenesSinSP
                    ->groupBy('empresaConstrucc.nombre')
                    ->map(function ($ordenes, $empresa) {
                        return [
                            'empresa' => $empresa,
                            'total_ordenes' => $ordenes->count(),
                            'importe_total' => $ordenes->sum('importe_total'),
                            'dias_promedio' => round($ordenes->avg('dias_sin_sp'), 1)
                        ];
                    })
                    ->values();

                // Órdenes críticas (más de threshold danger)
                $alertas['ordenes_criticas'] = $ordenesSinSP
                    ->where('nivel_alerta', 'danger')
                    ->take(5)
                    ->map(function ($orden) {
                        return [
                            'id' => $orden->id,
                            'numero_orden' => $orden->numero_orden,
                            'importe_total' => $orden->importe_total,
                            'dias_sin_sp' => $orden->dias_sin_sp,
                            'empresa' => $orden->empresaConstrucc->nombre,
                            'fecha_orden' => $orden->fecha_orden
                        ];
                    })
                    ->toArray();
            }

            return $alertas;
        });
    }

    /**
     * Obtener estadísticas de alertas del sistema
     */
    public function getEstadisticasAlertas(?Proveedor $proveedor = null): array
    {
        $query = OrdenCompra::query()
            ->sinSolicitudesPago()
            ->aprobadas()
            ->selectRaw('ordenes_compra.*, 
                DATEDIFF(NOW(), COALESCE(fecha_aprobacion, created_at)) as dias_sin_sp');

        if ($proveedor) {
            $query->where('proveedor_id', $proveedor->id);
        }

        $ordenes = $query->get();
        $ordenesConAlerta = $ordenes->filter(fn($o) => $o->dias_sin_sp >= $this->thresholds['warning']);

        return [
            'total_ordenes_sin_sp' => $ordenes->count(),
            'total_con_alertas' => $ordenesConAlerta->count(),
            'porcentaje_con_alertas' => $ordenes->count() > 0 ? 
                round(($ordenesConAlerta->count() / $ordenes->count()) * 100, 2) : 0,
            'distribucion_alertas' => [
                'sin_alerta' => $ordenes->filter(fn($o) => $o->dias_sin_sp < $this->thresholds['warning'])->count(),
                'warning' => $ordenesConAlerta->filter(fn($o) => $this->getNivelAlerta($o->dias_sin_sp) === 'warning')->count(),
                'danger' => $ordenesConAlerta->filter(fn($o) => $this->getNivelAlerta($o->dias_sin_sp) === 'danger')->count(),
            ],
            'dias_promedio_sin_sp' => round($ordenes->avg('dias_sin_sp'), 1),
            'importe_total_en_alerta' => $ordenesConAlerta->sum('importe_total'),
            'thresholds' => $this->thresholds
        ];
    }

    /**
     * Obtener nivel de alerta basado en días transcurridos
     */
    public function getNivelAlerta(int $dias): ?string
    {
        if ($dias >= $this->thresholds['danger']) {
            return 'danger';
        } elseif ($dias >= $this->thresholds['warning']) {
            return 'warning';
        }
        
        return null;
    }

    /**
     * Obtener mensaje de alerta
     */
    public function getMensajeAlerta(int $dias): ?string
    {
        $nivel = $this->getNivelAlerta($dias);
        
        return match($nivel) {
            'danger' => "Orden crítica: {$dias} días sin solicitud de pago",
            'warning' => "Atención: {$dias} días sin solicitud de pago",
            default => null
        };
    }

    /**
     * Obtener prioridad numérica (mayor número = mayor prioridad)
     */
    public function getPrioridad(int $dias): int
    {
        if ($dias >= $this->thresholds['danger']) {
            return 3; // Alta
        } elseif ($dias >= $this->thresholds['warning']) {
            return 2; // Media
        }
        
        return 1; // Baja
    }

    /**
     * Obtener color para UI
     */
    public function getColorAlerta(int $dias): string
    {
        $nivel = $this->getNivelAlerta($dias);
        
        return match($nivel) {
            'danger' => '#dc3545',  // Rojo
            'warning' => '#ffc107', // Amarillo
            default => '#28a745'    // Verde
        };
    }

    /**
     * Configurar thresholds personalizados
     */
    public function setThresholds(array $thresholds): self
    {
        $this->thresholds = array_merge($this->thresholds, $thresholds);
        return $this;
    }

    /**
     * Obtener notificaciones pendientes para un proveedor
     */
    public function getNotificacionesPendientes(Proveedor $proveedor): array
    {
        $alertas = $this->getAlertasProveedor($proveedor);
        $notificaciones = [];

        if ($alertas['alertas_danger'] > 0) {
            $notificaciones[] = [
                'tipo' => 'danger',
                'titulo' => 'Órdenes críticas pendientes',
                'mensaje' => "Tienes {$alertas['alertas_danger']} órdenes de compra con más de {$this->thresholds['danger']} días sin solicitud de pago",
                'accion' => 'Ver órdenes críticas',
                'url' => '/ordenes-compra?alerta=danger'
            ];
        }

        if ($alertas['alertas_warning'] > 0) {
            $notificaciones[] = [
                'tipo' => 'warning',
                'titulo' => 'Órdenes requieren atención',
                'mensaje' => "Tienes {$alertas['alertas_warning']} órdenes de compra con más de {$this->thresholds['warning']} días sin solicitud de pago",
                'accion' => 'Revisar órdenes',
                'url' => '/ordenes-compra?alerta=warning'
            ];
        }

        return $notificaciones;
    }

    /**
     * Marcar órdenes de compra próximas a vencer
     */
    public function getOrdenesProximasVencer(Proveedor $proveedor, int $diasAnticipacion = 2): Collection
    {
        $diasLimite = $this->thresholds['warning'] - $diasAnticipacion;

        return OrdenCompra::query()
            ->where('proveedor_id', $proveedor->id)
            ->sinSolicitudesPago()
            ->aprobadas()
            ->with(['empresaConstrucc'])
            ->selectRaw('ordenes_compra.*, 
                DATEDIFF(NOW(), COALESCE(fecha_aprobacion, created_at)) as dias_sin_sp')
            ->havingRaw('dias_sin_sp >= ? AND dias_sin_sp < ?', [$diasLimite, $this->thresholds['warning']])
            ->orderByRaw('dias_sin_sp DESC')
            ->get()
            ->map(function ($orden) {
                $orden->dias_para_alerta = $this->thresholds['warning'] - $orden->dias_sin_sp;
                return $orden;
            });
    }

    /**
     * Limpiar caché de alertas
     */
    public function limpiarCacheAlertas(?Proveedor $proveedor = null): void
    {
        if ($proveedor) {
            Cache::forget("alertas_proveedor_{$proveedor->id}");
        } else {
            // Limpiar todo el caché relacionado con alertas
            Cache::tags(['alertas_ordenes_compra'])->flush();
        }
    }

    /**
     * Obtener configuración de alertas
     */
    public function getConfiguracion(): array
    {
        return [
            'thresholds' => $this->thresholds,
            'colores' => [
                'warning' => '#ffc107',
                'danger' => '#dc3545',
                'success' => '#28a745'
            ],
            'descripciones' => [
                'warning' => "Órdenes con {$this->thresholds['warning']}+ días sin SP",
                'danger' => "Órdenes con {$this->thresholds['danger']}+ días sin SP (críticas)"
            ]
        ];
    }
}