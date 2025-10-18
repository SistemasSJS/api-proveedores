<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrdenCompraEstadisticasResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'estadisticas_basicas' => [
                'total_oc' => (int) $this->resource['estadisticas_basicas']['total_oc'],
                'total_importe' => (float) $this->resource['estadisticas_basicas']['total_importe'],
                'pendientes' => (int) $this->resource['estadisticas_basicas']['pendientes'],
                'aprobadas' => (int) $this->resource['estadisticas_basicas']['aprobadas'],
                'rechazadas' => (int) $this->resource['estadisticas_basicas']['rechazadas'],
                'completadas' => (int) $this->resource['estadisticas_basicas']['completadas'],
                'con_sp' => (int) $this->resource['estadisticas_basicas']['con_sp'],
                'sin_sp' => (int) $this->resource['estadisticas_basicas']['sin_sp'],
            ],

            'distribucion_estados' => collect($this->resource['distribucion_estados'])->map(function ($cantidad, $estado) {
                $config = config("ordenes-compra.estados.{$estado}", []);
                return [
                    'estado' => $estado,
                    'cantidad' => (int) $cantidad,
                    'label' => $config['label'] ?? ucfirst($estado),
                    'color' => $config['color'] ?? '#6c757d',
                ];
            })->values(),

            'montos_por_estado' => collect($this->resource['montos_por_estado'])->map(function ($monto, $estado) {
                return [
                    'estado' => $estado,
                    'monto_total' => (float) $monto,
                    'monto_formateado' => number_format($monto, 2),
                ];
            })->values(),

            'alertas' => [
                'con_alertas' => (int) $this->resource['alertas']['con_alertas'],
                'sin_alertas' => (int) $this->resource['alertas']['sin_alertas'],
                'porcentaje_alertas' => $this->resource['estadisticas_basicas']['sin_sp'] > 0 ?
                    round(($this->resource['alertas']['con_alertas'] / $this->resource['estadisticas_basicas']['sin_sp']) * 100, 2) : 0,
            ],

            // Métricas calculadas
            'metricas' => [
                'tasa_conversion' => $this->resource['estadisticas_basicas']['total_oc'] > 0 ?
                    round(($this->resource['estadisticas_basicas']['con_sp'] / $this->resource['estadisticas_basicas']['total_oc']) * 100, 2) : 0,
                'promedio_sp_por_oc' => $this->resource['estadisticas_basicas']['con_sp'] > 0 ?
                    round($this->resource['estadisticas_basicas']['con_sp'] / $this->resource['estadisticas_basicas']['con_sp'], 2) : 0,
                'importe_promedio' => $this->resource['estadisticas_basicas']['total_oc'] > 0 ?
                    round($this->resource['estadisticas_basicas']['total_importe'] / $this->resource['estadisticas_basicas']['total_oc'], 2) : 0,
            ],

            // Indicadores de rendimiento
            'kpis' => [
                'eficiencia_conversion' => [
                    'valor' => $this->resource['estadisticas_basicas']['total_oc'] > 0 ?
                        round(($this->resource['estadisticas_basicas']['con_sp'] / $this->resource['estadisticas_basicas']['total_oc']) * 100, 2) : 0,
                    'descripcion' => 'Porcentaje de OC que han generado SP',
                    'color' => $this->getColorKPI($this->resource['estadisticas_basicas']['total_oc'] > 0 ?
                        ($this->resource['estadisticas_basicas']['con_sp'] / $this->resource['estadisticas_basicas']['total_oc']) * 100 : 0),
                ],
                'ordenes_pendientes' => [
                    'valor' => (int) $this->resource['estadisticas_basicas']['sin_sp'],
                    'descripcion' => 'Órdenes sin solicitudes de pago',
                    'color' => $this->resource['estadisticas_basicas']['sin_sp'] > 0 ? '#dc3545' : '#28a745',
                ],
                'nivel_alertas' => [
                    'valor' => (int) $this->resource['alertas']['con_alertas'],
                    'descripcion' => 'Órdenes que requieren atención',
                    'color' => $this->resource['alertas']['con_alertas'] > 0 ? '#ffc107' : '#28a745',
                ],
            ],
        ];
    }

    /**
     * Obtener color del KPI basado en el valor
     */
    private function getColorKPI(float $valor): string
    {
        if ($valor >= 80) return '#28a745'; // Verde - Excelente
        if ($valor >= 60) return '#ffc107'; // Amarillo - Bueno
        if ($valor >= 40) return '#fd7e14'; // Naranja - Regular
        return '#dc3545'; // Rojo - Malo
    }

    /**
     * Get additional data that should be returned with the resource array.
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'periodo_consulta' => [
                    'desde' => $request->input('fecha_desde'),
                    'hasta' => $request->input('fecha_hasta'),
                ],
                'configuracion' => [
                    'thresholds_alertas' => config('ordenes-compra.alertas.thresholds'),
                    'colores_estados' => collect(config('ordenes-compra.estados'))->mapWithKeys(function ($config, $estado) {
                        return [$estado => $config['color']];
                    }),
                ],
                'ultima_actualizacion' => now()->format('Y-m-d H:i:s'),
            ],
        ];
    }
}