<?php

namespace Database\Factories;

use App\Enums\EstadoOrdenCompra;
use App\Models\EmpresaConstrucc;
use App\Models\OrdenCompra;
use App\Models\Proveedor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrdenCompra>
 */
class OrdenCompraFactory extends Factory
{
    protected $model = OrdenCompra::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fechaOrden = $this->faker->dateTimeBetween('-90 days', 'now');
        $estado = $this->faker->randomElement(EstadoOrdenCompra::cases());
        $tipoOrden = $this->faker->randomElement([
            'MATERIALES_CONSTRUCCION',
            'HERRAMIENTAS', 
            'EQUIPOS',
            'SERVICIOS',
            'MANTENIMIENTO',
            'SUMINISTROS'
        ]);

        $importeTotal = $this->generateImporteByTipo($tipoOrden);
        
        return [
            'numero_orden' => $this->generateNumeroOrden($fechaOrden),
            'fecha_orden' => $fechaOrden,
            'proveedor_id' => Proveedor::factory(),
            'empresa_construcc_id' => EmpresaConstrucc::factory(),
            'importe_total' => $importeTotal,
            'estado' => $estado,
            'fecha_aprobacion' => $this->getFechaAprobacion($estado, $fechaOrden),
            'observaciones' => $this->generateObservaciones($estado),
            'metadata_json' => $this->generateMetadata($tipoOrden),
            'monto_sp_asociado' => $this->getMontoSPAsociado($estado, $importeTotal),
            'sp_count' => $this->getSPCount($estado),
        ];
    }

    /**
     * Estado pendiente
     */
    public function pendiente(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => EstadoOrdenCompra::PENDIENTE,
            'fecha_aprobacion' => null,
            'monto_sp_asociado' => 0,
            'sp_count' => 0,
            'observaciones' => $this->faker->randomElement([
                'En proceso de revisión técnica',
                'Pendiente de autorización presupuestal',
                'Esperando cotización complementaria',
            ]),
        ]);
    }

    /**
     * Estado aprobada
     */
    public function aprobada(): static
    {
        return $this->state(function (array $attributes) {
            $fechaAprobacion = Carbon::parse($attributes['fecha_orden'])->addDays(rand(1, 15));
            $montoSP = rand(1, 100) <= 40 ? $attributes['importe_total'] * (rand(10, 60) / 100) : 0;
            
            return [
                'estado' => EstadoOrdenCompra::APROBADA,
                'fecha_aprobacion' => $fechaAprobacion,
                'monto_sp_asociado' => $montoSP,
                'sp_count' => $montoSP > 0 ? rand(1, 2) : 0,
                'observaciones' => $this->faker->randomElement([
                    'Autorizada para proceder con entrega',
                    'Cumple con especificaciones requeridas',
                    'Lista para programación de entrega',
                ]),
            ];
        });
    }

    /**
     * Estado completada
     */
    public function completada(): static
    {
        return $this->state(function (array $attributes) {
            $fechaAprobacion = Carbon::parse($attributes['fecha_orden'])->addDays(rand(1, 15));
            
            return [
                'estado' => EstadoOrdenCompra::COMPLETADA,
                'fecha_aprobacion' => $fechaAprobacion,
                'monto_sp_asociado' => $attributes['importe_total'],
                'sp_count' => rand(1, 3),
                'observaciones' => $this->faker->randomElement([
                    'Entrega completa y satisfactoria',
                    'Todos los conceptos entregados',
                    'Orden completada según especificaciones',
                ]),
            ];
        });
    }

    /**
     * Estado parcial
     */
    public function parcial(): static
    {
        return $this->state(function (array $attributes) {
            $fechaAprobacion = Carbon::parse($attributes['fecha_orden'])->addDays(rand(1, 15));
            $montoSP = $attributes['importe_total'] * (rand(25, 75) / 100);
            
            return [
                'estado' => EstadoOrdenCompra::PARCIAL,
                'fecha_aprobacion' => $fechaAprobacion,
                'monto_sp_asociado' => $montoSP,
                'sp_count' => rand(1, 2),
                'observaciones' => $this->faker->randomElement([
                    'Entrega parcial autorizada',
                    'Pendiente entrega de balance',
                    'Entrega por fases según cronograma',
                ]),
            ];
        });
    }

    /**
     * Estado rechazada
     */
    public function rechazada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => EstadoOrdenCompra::RECHAZADA,
            'fecha_aprobacion' => null,
            'monto_sp_asociado' => 0,
            'sp_count' => 0,
            'observaciones' => $this->faker->randomElement([
                'No cumple especificaciones técnicas',
                'Precio fuera de presupuesto autorizado',
                'Documentación incompleta',
            ]),
        ]);
    }

    /**
     * Con tipo específico de orden
     */
    public function tipoOrden(string $tipo): static
    {
        return $this->state(function (array $attributes) use ($tipo) {
            $importe = $this->generateImporteByTipo($tipo);
            
            return [
                'importe_total' => $importe,
                'metadata_json' => $this->generateMetadata($tipo),
            ];
        });
    }

    /**
     * Con proveedor específico
     */
    public function paraProveedor(int $proveedorId): static
    {
        return $this->state(fn (array $attributes) => [
            'proveedor_id' => $proveedorId,
        ]);
    }

    /**
     * Con empresa constructora específica
     */
    public function paraEmpresa(int $empresaId): static
    {
        return $this->state(fn (array $attributes) => [
            'empresa_construcc_id' => $empresaId,
        ]);
    }

    /**
     * Para fechas recientes (últimos 30 días)
     */
    public function reciente(): static
    {
        return $this->state(fn (array $attributes) => [
            'fecha_orden' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    /**
     * Sin solicitudes de pago asociadas
     */
    public function sinSolicitudesPago(): static
    {
        return $this->state(fn (array $attributes) => [
            'monto_sp_asociado' => 0,
            'sp_count' => 0,
        ]);
    }

    /**
     * Con solicitudes de pago
     */
    public function conSolicitudesPago(): static
    {
        return $this->state(function (array $attributes) {
            $montoSP = $attributes['importe_total'] * (rand(20, 80) / 100);
            
            return [
                'monto_sp_asociado' => $montoSP,
                'sp_count' => rand(1, 3),
            ];
        });
    }

    /**
     * Genera número de orden único
     */
    private function generateNumeroOrden($fechaOrden): string
    {
        $fecha = Carbon::parse($fechaOrden);
        return sprintf(
            'OC-%s-%04d-%03d',
            $fecha->format('Ym'),
            rand(1, 9999),
            rand(1, 999)
        );
    }

    /**
     * Genera importe según tipo de orden
     */
    private function generateImporteByTipo(string $tipo): float
    {
        $ranges = [
            'MATERIALES_CONSTRUCCION' => [5000, 150000],
            'HERRAMIENTAS' => [1500, 45000],
            'EQUIPOS' => [8000, 250000],
            'SERVICIOS' => [3000, 80000],
            'MANTENIMIENTO' => [2000, 35000],
            'SUMINISTROS' => [1000, 25000],
        ];

        $range = $ranges[$tipo] ?? [1000, 50000];
        return $this->faker->randomFloat(2, $range[0], $range[1]);
    }

    /**
     * Determina fecha de aprobación según estado
     */
    private function getFechaAprobacion(EstadoOrdenCompra $estado, $fechaOrden): ?Carbon
    {
        if (in_array($estado, [EstadoOrdenCompra::APROBADA, EstadoOrdenCompra::PARCIAL, EstadoOrdenCompra::COMPLETADA])) {
            return Carbon::parse($fechaOrden)->addDays(rand(1, 15));
        }
        
        return null;
    }

    /**
     * Genera observaciones según estado
     */
    private function generateObservaciones(EstadoOrdenCompra $estado): ?string
    {
        $observaciones = [
            EstadoOrdenCompra::PENDIENTE->value => [
                'En proceso de revisión técnica',
                'Pendiente de autorización presupuestal',
                'Esperando cotización complementaria',
            ],
            EstadoOrdenCompra::APROBADA->value => [
                'Autorizada para proceder con entrega',
                'Cumple con especificaciones requeridas',
                'Lista para programación de entrega',
            ],
            EstadoOrdenCompra::RECHAZADA->value => [
                'No cumple especificaciones técnicas',
                'Precio fuera de presupuesto autorizado',
                'Documentación incompleta',
            ],
            EstadoOrdenCompra::PARCIAL->value => [
                'Entrega parcial autorizada',
                'Pendiente entrega de balance',
                'Entrega por fases según cronograma',
            ],
            EstadoOrdenCompra::COMPLETADA->value => [
                'Entrega completa y satisfactoria',
                'Todos los conceptos entregados',
                'Orden completada según especificaciones',
            ],
        ];

        $opciones = $observaciones[$estado->value] ?? [];
        
        // 70% de probabilidad de tener observaciones
        if (rand(1, 100) <= 70 && !empty($opciones)) {
            return $opciones[array_rand($opciones)];
        }

        return null;
    }

    /**
     * Genera metadatos específicos del tipo de orden
     */
    private function generateMetadata(string $tipoOrden): array
    {
        $conceptos = [
            'MATERIALES_CONSTRUCCION' => ['Cemento', 'Varilla', 'Block', 'Arena'],
            'HERRAMIENTAS' => ['Taladro', 'Amoladora', 'Sierra', 'Martillo'],
            'EQUIPOS' => ['Revolvedora', 'Andamio', 'Compresor', 'Generador'],
            'SERVICIOS' => ['Topografía', 'Análisis', 'Diseño', 'Supervisión'],
            'MANTENIMIENTO' => ['Preventivo', 'Correctivo', 'Soldadura', 'Calibración'],
            'SUMINISTROS' => ['Combustible', 'Aceite', 'Oficina', 'Seguridad'],
        ];

        $conceptosDisponibles = $conceptos[$tipoOrden] ?? ['Concepto general'];
        $numConceptos = rand(1, min(3, count($conceptosDisponibles)));
        
        $detalles = [];
        for ($i = 0; $i < $numConceptos; $i++) {
            $concepto = $conceptosDisponibles[array_rand($conceptosDisponibles)];
            $detalles[] = [
                'concepto' => $concepto,
                'cantidad' => rand(1, 50),
                'unidad' => $this->faker->randomElement(['pza', 'm3', 'kg', 'servicio']),
                'precio_unitario' => rand(50, 5000),
            ];
        }

        return [
            'tipo_orden' => $tipoOrden,
            'detalles_conceptos' => $detalles,
            'condiciones_pago' => $this->faker->randomElement(['Contado', '15 días', '30 días']),
            'tiempo_entrega' => $this->faker->randomElement(['1 semana', '2 semanas', '1 mes']),
            'ubicacion_entrega' => $this->faker->address,
            'contacto_proveedor' => [
                'nombre' => $this->faker->name,
                'telefono' => $this->faker->phoneNumber,
                'email' => $this->faker->safeEmail,
            ],
            'referencias' => [
                'proyecto' => 'PROY-' . rand(1000, 9999),
                'centro_costos' => 'CC-' . rand(100, 999),
            ],
        ];
    }

    /**
     * Calcula monto de SP asociado según estado
     */
    private function getMontoSPAsociado(EstadoOrdenCompra $estado, float $importeTotal): float
    {
        return match ($estado) {
            EstadoOrdenCompra::COMPLETADA => $importeTotal,
            EstadoOrdenCompra::PARCIAL => $importeTotal * (rand(25, 75) / 100),
            EstadoOrdenCompra::APROBADA => rand(1, 100) <= 40 ? $importeTotal * (rand(10, 60) / 100) : 0,
            default => 0,
        };
    }

    /**
     * Calcula número de SPs según estado
     */
    private function getSPCount(EstadoOrdenCompra $estado): int
    {
        return match ($estado) {
            EstadoOrdenCompra::COMPLETADA => rand(1, 3),
            EstadoOrdenCompra::PARCIAL => rand(1, 2),
            EstadoOrdenCompra::APROBADA => rand(1, 100) <= 40 ? rand(1, 2) : 0,
            default => 0,
        };
    }
}