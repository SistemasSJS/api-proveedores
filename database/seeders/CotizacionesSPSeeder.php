<?php

namespace Database\Seeders;

use App\Enums\EstadoCotizacion;
use Illuminate\Database\Seeder;
use App\Models\Cotizacion;
use App\Models\Proveedor;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CotizacionesSPSeeder extends Seeder
{
    /**
     * Seeder para generar cotizaciones relacionadas con proveedores SP
     * Configurado para Los Mochis, Sinaloa, México
     */
    public function run(): void
    {
        DB::transaction(function () {
            // Obtener todos los proveedores SP (is_proveedor_sp = true)
            $proveedoresSP = Proveedor::where('is_proveedor_sp', true)->pluck('id')->toArray();

            if (empty($proveedoresSP)) {
                echo "⚠️  No se encontraron proveedores SP. Ejecuta primero ProveedorSeeder y ProveedoresSPSeeder.\n";
                return;
            }

            $now = Carbon::now('America/Mazatlan'); // Zona horaria de Los Mochis
            $cotizaciones = [];

            foreach ($proveedoresSP as $proveedorId) {
                // Generar entre 2-4 cotizaciones por proveedor SP
                $numCotizaciones = rand(2, 4);

                for ($i = 0; $i < $numCotizaciones; $i++) {
                    $fechaCotizacion = $now->copy()->subDays(rand(1, 90));
                    $fechaVencimiento = $fechaCotizacion->copy()->addDays(rand(15, 45));

                    $cotizaciones[] = [
                        'proveedor_id' => $proveedorId,
                        'fecha_cotizacion' => $fechaCotizacion,
                        'fecha_vencimiento' => $fechaVencimiento,
                        'total' => round(rand(5000, 500000) / 100, 2), // Entre $50.00 y $5,000.00
                        'observaciones' => $this->generarObservacionesAleatorias(),
                        'estatus' => $this->generarEstatusAleatorio($fechaVencimiento, $now),
                        'created_at' => $fechaCotizacion,
                        'updated_at' => $fechaCotizacion->copy()->addDays(rand(0, 5)),
                    ];
                }
            }

            // Insertar cotizaciones en lotes para mejor rendimiento
            $chunks = array_chunk($cotizaciones, 50);
            foreach ($chunks as $chunk) {
                DB::table('cotizaciones')->insert($chunk);
            }

            echo "✅ Seeder CotizacionesSPSeeder ejecutado correctamente.\n";
            echo "📊 Se generaron " . count($cotizaciones) . " cotizaciones para " . count($proveedoresSP) . " proveedores SP.\n";
            echo "📍 Configurado para zona horaria America/Mazatlan (Los Mochis, Sinaloa).\n";
        });
    }

    /**
     * Genera observaciones aleatorias para las cotizaciones
     */
    private function generarObservacionesAleatorias(): string
    {
        $observaciones = [
            'Cotización para proyecto de construcción residencial',
            'Materiales para obra de pavimentación urbana',
            'Suministro de herramientas y equipos especializados',
            'Cotización para remodelación de edificio comercial',
            'Materiales para construcción de infraestructura pública',
            'Suministro para proyecto de urbanización',
            'Cotización para mantenimiento de carreteras',
            'Materiales para construcción de puente vehicular',
            'Suministro de maquinaria para movimiento de tierra',
            'Cotización para proyecto de drenaje pluvial',
            'Materiales para construcción de planta industrial',
            'Suministro para proyecto habitacional Los Mochis',
            'Cotización para obra civil en zona costera',
            'Materiales para ampliación de puerto Topolobampo',
            'Suministro para desarrollo inmobiliario Sinaloa',
        ];

        return $observaciones[array_rand($observaciones)];
    }

    /**
     * Genera estatus aleatorio basado en la fecha de vencimiento
     */
    private function generarEstatusAleatorio(Carbon $fechaVencimiento, Carbon $now): string
    {
        if ($fechaVencimiento->lt($now)) {
            $probabilidades = [
                EstadoCotizacion::RECHAZADA->value => 15,
                EstadoCotizacion::ACEPTADA->value  => 20,
                EstadoCotizacion::ENVIADA->value   => 5,
                EstadoCotizacion::APROBADA->value  => 0, // opcional si no quieres que aparezca
                EstadoCotizacion::PENDIENTE->value => 0,
                EstadoCotizacion::RECHAZADA->value => 60, // mayor probabilidad de vencida
            ];
        } else {
            $probabilidades = [
                EstadoCotizacion::ENVIADA->value   => 40,
                EstadoCotizacion::ACEPTADA->value  => 30,
                EstadoCotizacion::PENDIENTE->value => 20,
                EstadoCotizacion::RECHAZADA->value => 10,
            ];
        }

        $random = rand(1, 100);
        $acumulado = 0;

        foreach ($probabilidades as $estatus => $probabilidad) {
            $acumulado += $probabilidad;
            if ($random <= $acumulado) {
                return $estatus;
            }
        }

        return EstadoCotizacion::PENDIENTE->value; // fallback
    }
}
