<?php

namespace Database\Seeders;

use App\Enums\EstadoOrdenCompra;
use App\Models\EmpresaConstrucc;
use App\Models\OrdenCompra;
use App\Models\Proveedor;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrdenCompraSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        
        // Obtener proveedores y empresas existentes
        $proveedores = Proveedor::all();
        $empresas = EmpresaConstrucc::all();
        
        if ($proveedores->isEmpty() || $empresas->isEmpty()) {
            $this->command->warn('No hay proveedores o empresas constructoras disponibles. Ejecute ProveedorSeeder y EmpresaConstruccSeeder primero.');
            return;
        }

        // Datos base para generar órdenes de compra realistas
        $tiposOrden = [
            'MATERIALES_CONSTRUCCION',
            'HERRAMIENTAS',
            'EQUIPOS',
            'SERVICIOS',
            'MANTENIMIENTO',
            'SUMINISTROS',
        ];

        $conceptosOrden = [
            'MATERIALES_CONSTRUCCION' => [
                'Cemento Portland CPC 30',
                'Varilla de acero #3',
                'Varilla de acero #4',
                'Block de concreto 15x20x40',
                'Arena de río',
                'Grava triturada',
                'Cal hidratada',
                'Alambrón galvanizado',
            ],
            'HERRAMIENTAS' => [
                'Taladro percutor 1/2"',
                'Amoladora angular 4.5"',
                'Sierra circular 7.25"',
                'Martillo demoledor',
                'Nivel de burbuja 60cm',
                'Flexómetro 8m',
                'Juego de llaves españolas',
                'Pistola de calor',
            ],
            'EQUIPOS' => [
                'Revolvedora de concreto 1 saco',
                'Andamio tubular 2.5m',
                'Compresor de aire 25L',
                'Generador eléctrico 3500W',
                'Cortadora de block',
                'Vibrador para concreto',
                'Bomba de agua sumergible',
                'Escalera extensible 6m',
            ],
            'SERVICIOS' => [
                'Servicio de topografía',
                'Análisis de suelos',
                'Diseño estructural',
                'Supervisión técnica',
                'Pruebas de resistencia',
                'Certificación de calidad',
                'Consultoría ambiental',
                'Gestión de permisos',
            ],
            'MANTENIMIENTO' => [
                'Mantenimiento preventivo equipos',
                'Reparación de maquinaria',
                'Servicio de soldadura',
                'Calibración de instrumentos',
                'Limpieza de obra',
                'Pintura y acabados',
                'Instalación eléctrica',
                'Plomería especializada',
            ],
            'SUMINISTROS' => [
                'Combustible diésel',
                'Aceite hidráulico',
                'Material de oficina',
                'Equipo de seguridad',
                'Uniformes de trabajo',
                'Cascos de protección',
                'Botas de seguridad',
                'Guantes industriales',
            ],
        ];

        $ordenesCompra = [];

        foreach ($proveedores as $proveedor) {
            $numeroOrdenesProveedor = rand(3, 8); // Entre 3 y 8 órdenes por proveedor

            for ($i = 1; $i <= $numeroOrdenesProveedor; $i++) {
                $empresa = $empresas->random();
                $pesosTipos = [35, 20, 15, 10, 12, 8]; // Corresponde al orden de $tiposOrden
                $tipoOrden = $this->selectWeightedRandom($tiposOrden, $pesosTipos);

                $fechaOrden = $this->generateRandomDate(60); // Últimos 60 días
                $estado = $this->selectWeightedEstado();
                
                // Generar número de orden único
                $numeroOrden = sprintf(
                    'OC-%s-%04d-%03d',
                    $fechaOrden->format('Ym'),
                    $proveedor->id,
                    $i
                );

                // Generar importe realista según tipo de orden
                $importeBase = $this->generateRealisticAmount($tipoOrden);
                $importe = $this->addRandomVariation($importeBase, 0.3); // ±30% de variación

                // Generar metadatos específicos del tipo de orden
                $metadata = $this->generateMetadata($tipoOrden, $conceptosOrden[$tipoOrden] ?? []);

                // Calcular fechas y montos asociados según estado
                $fechaAprobacion = null;
                $montoSPAsociado = 0;
                $spCount = 0;

                if (in_array($estado, [EstadoOrdenCompra::APROBADA, EstadoOrdenCompra::PARCIAL, EstadoOrdenCompra::COMPLETADA])) {
                    $fechaAprobacion = $fechaOrden->copy()->addDays(rand(1, 15));
                    
                    // Simular asociación con solicitudes de pago
                    if ($estado === EstadoOrdenCompra::PARCIAL) {
                        $spCount = rand(1, 2);
                        $montoSPAsociado = $importe * (rand(25, 75) / 100);
                    } elseif ($estado === EstadoOrdenCompra::COMPLETADA) {
                        $spCount = rand(1, 3);
                        $montoSPAsociado = $importe;
                    } elseif ($estado === EstadoOrdenCompra::APROBADA && rand(1, 100) <= 40) {
                        // 40% de probabilidad de tener SPs en estado aprobada
                        $spCount = rand(1, 2);
                        $montoSPAsociado = $importe * (rand(10, 60) / 100);
                    }
                }

                $ordenesCompra[] = [
                    'numero_orden' => $numeroOrden,
                    'fecha_orden' => $fechaOrden,
                    'proveedor_id' => $proveedor->id,
                    'empresa_construcc_id' => $empresa->id,
                    'importe_total' => $importe,
                    'estado' => $estado->value, // Convert enum to string
                    'fecha_aprobacion' => $fechaAprobacion,
                    'observaciones' => $this->generateObservations($estado, $tipoOrden),
                    'metadata_json' => json_encode($metadata), // Convert array to JSON string
                    'monto_sp_asociado' => $montoSPAsociado,
                    'sp_count' => $spCount,
                    'created_at' => $fechaOrden,
                    'updated_at' => $fechaAprobacion ?? $fechaOrden,
                ];
            }
        }

        // Insertar en lotes para mejor performance
        $chunks = array_chunk($ordenesCompra, 50);
        foreach ($chunks as $chunk) {
            DB::table('ordenes_compra')->insert($chunk);
        }

        $totalOrdenes = count($ordenesCompra);
        $this->command->info("✅ Se crearon {$totalOrdenes} órdenes de compra con datos válidos");
        
        // Mostrar estadísticas
        $this->showStatistics();
    }

    /**
     * Selecciona un estado con pesos probabilísticos realistas
     */
    private function selectWeightedEstado(): EstadoOrdenCompra
    {
        $estados = [
            EstadoOrdenCompra::PENDIENTE,
            EstadoOrdenCompra::APROBADA,
            EstadoOrdenCompra::PARCIAL,
            EstadoOrdenCompra::COMPLETADA,
            EstadoOrdenCompra::RECHAZADA,
        ];

        $pesos = [25, 35, 20, 15, 5]; // 25%, 35%, 20%, 15%, 5%

        return $this->selectWeightedRandom($estados, $pesos);
    }

    /**
     * Genera un importe realista según el tipo de orden
     */
    private function generateRealisticAmount(string $tipoOrden): float
    {
        $ranges = [
            'MATERIALES_CONSTRUCCION' => [5000, 150000],    // $5K - $150K
            'HERRAMIENTAS' => [1500, 45000],                // $1.5K - $45K
            'EQUIPOS' => [8000, 250000],                    // $8K - $250K
            'SERVICIOS' => [3000, 80000],                   // $3K - $80K
            'MANTENIMIENTO' => [2000, 35000],               // $2K - $35K
            'SUMINISTROS' => [1000, 25000],                 // $1K - $25K
        ];

        $range = $ranges[$tipoOrden] ?? [1000, 50000];
        return rand($range[0], $range[1]);
    }

    /**
     * Añade variación aleatoria a un monto
     */
    private function addRandomVariation(float $base, float $variation): float
    {
        $factor = 1 + (rand(-100, 100) / 100) * $variation;
        return round($base * $factor, 2);
    }

    /**
     * Genera fecha aleatoria en los últimos N días
     */
    private function generateRandomDate(int $daysBack): Carbon
    {
        $days = rand(0, $daysBack);
        $hours = rand(8, 18); // Horario laboral
        $minutes = rand(0, 59);
        
        return Carbon::now()
            ->subDays($days)
            ->setTime($hours, $minutes, 0);
    }

    /**
     * Genera metadatos específicos según tipo de orden
     */
    private function generateMetadata(string $tipoOrden, array $conceptos): array
    {
        $numConceptos = rand(1, min(4, count($conceptos)));
        $conceptosSeleccionados = array_rand($conceptos, $numConceptos);
        
        if (!is_array($conceptosSeleccionados)) {
            $conceptosSeleccionados = [$conceptosSeleccionados];
        }

        $detalles = [];
        foreach ($conceptosSeleccionados as $index) {
            $detalles[] = [
                'concepto' => $conceptos[$index],
                'cantidad' => rand(1, 50),
                'unidad' => $this->getUnidadMedida($tipoOrden),
                'precio_unitario' => rand(50, 5000),
            ];
        }

        return [
            'tipo_orden' => $tipoOrden,
            'detalles_conceptos' => $detalles,
            'condiciones_pago' => $this->getCondicionesPago(),
            'tiempo_entrega' => $this->getTiempoEntrega($tipoOrden),
            'ubicacion_entrega' => $this->getUbicacionEntrega(),
            'contacto_proveedor' => [
                'nombre' => fake()->name(),
                'telefono' => fake()->phoneNumber(),
                'email' => fake()->safeEmail(),
            ],
            'referencias' => [
                'proyecto' => 'PROY-' . rand(1000, 9999),
                'centro_costos' => 'CC-' . rand(100, 999),
                'presupuesto' => 'PRES-' . rand(2024, 2025) . '-' . rand(100, 999),
            ],
        ];
    }

    /**
     * Obtiene unidad de medida según tipo de orden
     */
    private function getUnidadMedida(string $tipoOrden): string
    {
        $unidades = [
            'MATERIALES_CONSTRUCCION' => ['m3', 'ton', 'saco', 'm2', 'pza'],
            'HERRAMIENTAS' => ['pza', 'juego', 'kit'],
            'EQUIPOS' => ['pza', 'unidad'],
            'SERVICIOS' => ['servicio', 'hr', 'día', 'mes'],
            'MANTENIMIENTO' => ['servicio', 'hr', 'visita'],
            'SUMINISTROS' => ['L', 'kg', 'pza', 'caja'],
        ];

        $opciones = $unidades[$tipoOrden] ?? ['pza'];
        return $opciones[array_rand($opciones)];
    }

    /**
     * Genera condiciones de pago realistas
     */
    private function getCondicionesPago(): string
    {
        $condiciones = [
            'Contado',
            '15 días',
            '30 días',
            '45 días',
            '60 días',
            '50% anticipo, 50% contra entrega',
            '30% anticipo, 70% a 30 días',
        ];

        return $condiciones[array_rand($condiciones)];
    }

    /**
     * Genera tiempo de entrega según tipo de orden
     */
    private function getTiempoEntrega(string $tipoOrden): string
    {
        $tiempos = [
            'MATERIALES_CONSTRUCCION' => ['3-5 días', '1 semana', '10 días', '2 semanas'],
            'HERRAMIENTAS' => ['Inmediato', '2-3 días', '1 semana'],
            'EQUIPOS' => ['1 semana', '2 semanas', '3 semanas', '1 mes'],
            'SERVICIOS' => ['Programado', '1 semana', '2 semanas'],
            'MANTENIMIENTO' => ['24-48 hrs', '1 semana'],
            'SUMINISTROS' => ['Inmediato', '2-3 días', '1 semana'],
        ];

        $opciones = $tiempos[$tipoOrden] ?? ['1 semana'];
        return $opciones[array_rand($opciones)];
    }

    /**
     * Genera ubicación de entrega
     */
    private function getUbicacionEntrega(): string
    {
        $ubicaciones = [
            'Obra Civil Centro - Av. Revolución 123',
            'Almacén Principal - Zona Industrial Norte',
            'Proyecto Residencial Los Pinos',
            'Oficinas Corporativas - Torre Ejecutiva',
            'Obra Vial Periférico Sur Km 15',
            'Centro de Distribución - Parque Industrial',
            'Proyecto Comercial Plaza del Sol',
            'Complejo Industrial Zona Este',
        ];

        return $ubicaciones[array_rand($ubicaciones)];
    }

    /**
     * Genera observaciones según estado y tipo
     */
    private function generateObservations(EstadoOrdenCompra $estado, string $tipoOrden): ?string
    {
        $observaciones = [];

        switch ($estado) {
            case EstadoOrdenCompra::PENDIENTE:
                $observaciones = [
                    'En proceso de revisión técnica',
                    'Pendiente de autorización presupuestal',
                    'Esperando cotización complementaria',
                    'En validación de especificaciones técnicas',
                ];
                break;

            case EstadoOrdenCompra::APROBADA:
                $observaciones = [
                    'Autorizada para proceder con entrega',
                    'Cumple con especificaciones requeridas',
                    'Proveedor confirmó disponibilidad',
                    'Lista para programación de entrega',
                ];
                break;

            case EstadoOrdenCompra::RECHAZADA:
                $observaciones = [
                    'No cumple especificaciones técnicas',
                    'Precio fuera de presupuesto autorizado',
                    'Tiempo de entrega no aceptable',
                    'Documentación incompleta',
                ];
                break;

            case EstadoOrdenCompra::PARCIAL:
                $observaciones = [
                    'Entrega parcial autorizada',
                    'Pendiente entrega de balance',
                    'Algunos conceptos modificados',
                    'Entrega por fases según cronograma',
                ];
                break;

            case EstadoOrdenCompra::COMPLETADA:
                $observaciones = [
                    'Entrega completa y satisfactoria',
                    'Todos los conceptos entregados',
                    'Documentación completa recibida',
                    'Orden completada según especificaciones',
                ];
                break;
        }

        // 70% de probabilidad de tener observaciones
        if (rand(1, 100) <= 70) {
            return $observaciones[array_rand($observaciones)];
        }

        return null;
    }

    /**
     * Selección aleatoria con pesos
     */
    private function selectWeightedRandom(array $options, array $weights): mixed
    {
        // Asegurar que tenemos el mismo número de opciones y pesos
        if (count($options) !== count($weights)) {
            throw new \InvalidArgumentException('Options and weights arrays must have the same length');
        }

        $total = array_sum($weights);
        $random = rand(1, $total);
        $current = 0;

        foreach ($options as $index => $option) {
            $current += $weights[$index];
            if ($random <= $current) {
                return $option;
            }
        }

        return $options[0]; // Fallback
    }

    /**
     * Muestra estadísticas de las órdenes creadas
     */
    private function showStatistics(): void
    {
        $stats = DB::table('ordenes_compra')
            ->select('estado', DB::raw('COUNT(*) as count'), DB::raw('SUM(importe_total) as total_importe'))
            ->groupBy('estado')
            ->get();

        $this->command->info("\n📊 Estadísticas de Órdenes de Compra:");
        $this->command->info("─────────────────────────────────────");

        $totalOrdenes = 0;
        $montoTotal = 0;

        foreach ($stats as $stat) {
            $estado = EstadoOrdenCompra::from($stat->estado);
            $porcentaje = 0; // Se calculará después
            
            $this->command->info(sprintf(
                "• %s: %d órdenes - $%s",
                $estado->label(),
                $stat->count,
                number_format($stat->total_importe, 2, '.', ',')
            ));

            $totalOrdenes += $stat->count;
            $montoTotal += $stat->total_importe;
        }

        $this->command->info("─────────────────────────────────────");
        $this->command->info(sprintf(
            "📈 Total: %d órdenes - $%s",
            $totalOrdenes,
            number_format($montoTotal, 2, '.', ',')
        ));
        $this->command->info(sprintf(
            "💰 Promedio por orden: $%s",
            number_format($montoTotal / max($totalOrdenes, 1), 2, '.', ',')
        ));
    }
}