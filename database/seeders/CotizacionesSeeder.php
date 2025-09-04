<?php

namespace Database\Seeders;

use App\Enums\EstadoCotizacion;
use Illuminate\Database\Seeder;
use App\Models\Cotizacion;
use App\Models\CotizacionDetalle;
use App\Models\Proveedor;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CotizacionesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $proveedores = Proveedor::with('productos')->get();

            $this->command->info('Generando cotizaciones para cada proveedor...');

            foreach ($proveedores as $proveedor) {
                if ($proveedor->productos->isEmpty()) {
                    continue;
                }

                $this->generarCotizacionesParaProveedor($proveedor);
            }

            $this->command->info('¡Cotizaciones generadas exitosamente!');
        });
    }

    /**
     * Generar cotizaciones para un proveedor específico
     */
    private function generarCotizacionesParaProveedor(Proveedor $proveedor): void
    {
        $estados = EstadoCotizacion::values();

        foreach ($estados as $estatus) {
            $cantidad =  rand(1, 50); // Generar 5 cotizaciones por cada estatus
            for ($i = 0; $i < $cantidad; $i++) {
                $fechaCotizacion = Carbon::now()->subDays(rand(1, 90));
                $fechaVencimiento = (clone $fechaCotizacion)->addDays(rand(15, 60));

                $subtotal = 0;
                $descuento = rand(0, 15);
                $impuestos = 16;

                $cotizacion = Cotizacion::create([
                    'proveedor_id' => $proveedor->id,
                    'fecha_cotizacion' => $fechaCotizacion,
                    'fecha_vencimiento' => $fechaVencimiento,
                    'total' => 0,
                    'observaciones' => $this->generarObservaciones(),
                    'estatus' => $estatus,
                ]);

                $subtotal = $this->crearDetallesCotizacion($cotizacion, $proveedor);

                $montoDescuento = $subtotal * ($descuento / 100);
                $baseGravable = $subtotal - $montoDescuento;
                $montoImpuestos = $baseGravable * ($impuestos / 100);
                $totalFinal = $baseGravable + $montoImpuestos;

                $cotizacion->update([
                    'total' => round($totalFinal, 2),
                ]);
            }
        }

        $this->command->info("  → Cotizaciones generadas por estatus para {$proveedor->nombre_comercial}");
    }
    /**
     * Crear una cotización individual
     */
    private function crearCotizacion(Proveedor $proveedor): void
    {
        $fechaCotizacion = Carbon::now()->subDays(rand(1, 90));
        $fechaVencimiento = (clone $fechaCotizacion)->addDays(rand(15, 60));

        // Determinar estatus basado en las fechas
        $estatus = $this->determinarEstatus($fechaCotizacion, $fechaVencimiento);

        $subtotal = 0;
        $descuento = rand(0, 15);
        $impuestos = 16;

        // Generar número de cotización único
        $numeroCotizacion = 'COT-' . $fechaCotizacion->format('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        $cotizacion = Cotizacion::create([
            'proveedor_id' => $proveedor->id,
            'fecha_cotizacion' => $fechaCotizacion,
            'fecha_vencimiento' => $fechaVencimiento,
            'total' => 0, // Se calculará después
            'observaciones' => $this->generarObservaciones(),
        ]);

        // Crear detalles de la cotización
        $subtotal = $this->crearDetallesCotizacion($cotizacion, $proveedor);

        // Calcular totales finales
        $montoDescuento = $subtotal * ($descuento / 100);
        $baseGravable = $subtotal - $montoDescuento;
        $montoImpuestos = $baseGravable * ($impuestos / 100);
        $totalFinal = $baseGravable + $montoImpuestos;

        // Actualizar la cotización con los totales
        $cotizacion->update([
            'total' => round($totalFinal, 2),
        ]);
    }

    /**
     * Crear detalles para una cotización
     */
    private function crearDetallesCotizacion(Cotizacion $cotizacion, Proveedor $proveedor): float
    {
        $productos = $proveedor->productos->random(rand(1, min(8, $proveedor->productos->count())));
        $subtotal = 0;

        foreach ($productos as $producto) {
            $cantidad = rand(1, 50);
            $precioUnitario = rand(100, 50000) / 100; // Precios entre $1.00 y $500.00
            $subtotalDetalle = $cantidad * $precioUnitario;

            CotizacionDetalle::create([
                'proveedor_id' => $proveedor->id,
                'cotizacion_id' => $cotizacion->id,
                'producto_id' => $producto->id,
                'cantidad_cotizada' => $cantidad,
                'precio_unitario' => round($precioUnitario, 2),
                'subtotal' => round($subtotalDetalle, 2),
                'tiempo_entrega_dias' => rand(1, 21),
                'observaciones' => $this->generarObservacionesDetalle(),
            ]);

            $subtotal += $subtotalDetalle;
        }

        return $subtotal;
    }

    /**
     * Determinar el estatus de una cotización basado en las fechas
     */
    private function determinarEstatus(Carbon $fechaCotizacion, Carbon $fechaVencimiento): string
    {
        $ahora = Carbon::now();

        // Si ya venció, está vencida
        if ($fechaVencimiento->isPast()) {
            return collect(['vencida'])->random();
        }

        // Si es reciente (últimos 30 días), más probabilidad de estar activa
        if ($fechaCotizacion->diffInDays($ahora) <= 30) {
            return collect(['pendiente', 'enviada', 'aceptada'])->random();
        }

        // Para cotizaciones más antiguas, distribución más variada
        return collect(['pendiente', 'enviada', 'aceptada', 'rechazada', 'vencida'])->random();
    }

    /**
     * Generar observaciones aleatorias para la cotización
     */
    private function generarObservaciones(): string
    {
        $observaciones = [
            'Cotización elaborada según especificaciones solicitadas.',
            'Precios vigentes por 30 días. Sujeto a disponibilidad.',
            'Incluye garantía del fabricante. Entrega FOB destino.',
            'Cotización válida hasta la fecha indicada. No incluye instalación.',
            'Precios en pesos mexicanos. IVA incluido.',
            'Tiempo de entrega sujeto a confirmación de existencias.',
            'Se requiere anticipo del 50% para iniciar producción.',
            'Cotización elaborada con base en volúmenes especificados.',
        ];

        return collect($observaciones)->random();
    }

    /**
     * Generar observaciones para detalles de cotización
     */
    private function generarObservacionesDetalle(): ?string
    {
        $observaciones = [
            'Producto en existencia inmediata.',
            'Requiere pedido especial.',
            'Disponible en diferentes colores.',
            'Se maneja por lotes mínimos.',
            'Incluye certificado de calidad.',
            'Producto importado.',
            null, // Sin observaciones
            null,
        ];

        return collect($observaciones)->random();
    }
}
