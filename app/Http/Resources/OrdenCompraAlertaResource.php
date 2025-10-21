<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrdenCompraAlertaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero_orden' => $this->numero_orden,
            'fecha_orden' => $this->fecha_orden?->format('Y-m-d'),
            'fecha_aprobacion' => $this->fecha_aprobacion?->format('Y-m-d H:i:s'),
            'importe_total' => (float) $this->importe_total,
            'estado' => [
                'codigo' => $this->estado->value,
                'label' => $this->estado->label(),
                'color' => $this->estado->color(),
            ],

            // Información de alerta
            'alerta' => [
                'dias_sin_sp' => (int) $this->dias_sin_sp,
                'nivel_alerta' => $this->nivel_alerta,
                'mensaje_alerta' => $this->mensaje_alerta,
                'prioridad' => $this->prioridad ?? $this->getPrioridad(),
                'color_alerta' => $this->getColorAlerta(),
                'urgente' => $this->nivel_alerta === 'danger',
            ],

            // Información contextual
            'proveedor' => $this->whenLoaded('proveedor', function () {
                return [
                    'id' => $this->proveedor->id,
                    'nombre_comercial' => $this->proveedor->nombre_comercial,
                ];
            }),

            'empresa_construcc' => $this->whenLoaded('empresaConstrucc', function () {
                return [
                    'id' => $this->empresaConstrucc->id,
                    'nombre' => $this->empresaConstrucc->nombre,
                ];
            }),

            // Acciones recomendadas
            'acciones_recomendadas' => $this->getAccionesRecomendadas(),

            // Información adicional para contexto
            'contexto' => [
                'puede_generar_sp' => $this->puedeGenerarSolicitudPago(),
                'monto_disponible' => (float) $this->getMontoDisponible(),
                'observaciones' => $this->observaciones,
            ],

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Obtener color de alerta basado en el nivel
     */
    private function getColorAlerta(): string
    {
        return match ($this->nivel_alerta) {
            'danger' => '#dc3545',
            'warning' => '#ffc107',
            default => '#28a745'
        };
    }

    /**
     * Obtener prioridad numérica
     */
    private function getPrioridad(): int
    {
        $dias = $this->dias_sin_sp ?? 0;
        if ($dias >= 15) {
            return 3;
        } // Alta
        if ($dias >= 7) {
            return 2;
        }  // Media

        return 1; // Baja
    }

    /**
     * Obtener acciones recomendadas basadas en el nivel de alerta
     */
    private function getAccionesRecomendadas(): array
    {
        $acciones = [];

        if ($this->nivel_alerta === 'danger') {
            $acciones[] = [
                'accion' => 'crear_sp_urgente',
                'titulo' => 'Crear Solicitud de Pago Urgente',
                'descripcion' => 'Esta orden requiere atención inmediata',
                'icono' => 'exclamation-triangle',
                'color' => '#dc3545',
                'url' => "/ordenes-compra/{$this->id}/crear-sp",
            ];

            $acciones[] = [
                'accion' => 'contactar_empresa',
                'titulo' => 'Contactar Empresa',
                'descripcion' => 'Verificar el estado con la empresa constructora',
                'icono' => 'phone',
                'color' => '#17a2b8',
            ];
        } elseif ($this->nivel_alerta === 'warning') {
            $acciones[] = [
                'accion' => 'crear_sp',
                'titulo' => 'Crear Solicitud de Pago',
                'descripcion' => 'Generar solicitud antes que se vuelva crítica',
                'icono' => 'plus-circle',
                'color' => '#ffc107',
                'url' => "/ordenes-compra/{$this->id}/crear-sp",
            ];

            $acciones[] = [
                'accion' => 'revisar_documentos',
                'titulo' => 'Revisar Documentación',
                'descripcion' => 'Verificar que se tiene toda la documentación necesaria',
                'icono' => 'file-text',
                'color' => '#6c757d',
            ];
        }

        // Acción siempre disponible
        $acciones[] = [
            'accion' => 'ver_detalle',
            'titulo' => 'Ver Detalle Completo',
            'descripcion' => 'Ver información completa de la orden',
            'icono' => 'eye',
            'color' => '#007bff',
            'url' => "/ordenes-compra/{$this->id}",
        ];

        return $acciones;
    }

    /**
     * Get additional data that should be returned with the resource array.
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'thresholds' => config('ordenes-compra.alertas.thresholds'),
                'configuracion_alertas' => [
                    'niveles' => [
                        'warning' => [
                            'label' => 'Advertencia',
                            'descripcion' => 'Orden requiere atención',
                            'color' => '#ffc107',
                            'dias_minimo' => config('ordenes-compra.alertas.thresholds.warning', 7),
                        ],
                        'danger' => [
                            'label' => 'Crítico',
                            'descripcion' => 'Orden crítica, requiere acción inmediata',
                            'color' => '#dc3545',
                            'dias_minimo' => config('ordenes-compra.alertas.thresholds.danger', 15),
                        ],
                    ],
                ],
            ],
        ];
    }
}
