<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CotizacionDetalle;
use App\Models\Cotizacion;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;

class CotizacionDetalleSeeder extends Seeder
{
    /**
     * Seeder para generar detalles de cotizaciones con productos
     * Configurado para Los Mochis, Sinaloa, México
     */
    public function run(): void
    {
        DB::transaction(function () {
            // Obtener todas las cotizaciones de proveedores SP
            $cotizaciones = Cotizacion::whereHas('proveedor', function ($query) {
                $query->where('is_proveedor_sp', true);
            })->with('proveedor')->get();

            if ($cotizaciones->isEmpty()) {
                echo "⚠️  No se encontraron cotizaciones de proveedores SP. Ejecuta primero CotizacionesSPSeeder.\n";
                return;
            }

            $detalles = [];
            $totalDetallesCreados = 0;

            foreach ($cotizaciones as $cotizacion) {
                // Obtener productos del mismo proveedor
                $productos = Producto::where('proveedor_id', $cotizacion->proveedor_id)->get();

                if ($productos->isEmpty()) {
                    // Si no hay productos específicos del proveedor, usar productos aleatorios
                    $productos = Producto::inRandomOrder()->take(rand(3, 8))->get();
                }

                if ($productos->isEmpty()) {
                    echo "⚠️  No se encontraron productos. Ejecuta primero ProductoSeeder.\n";
                    continue;
                }

                // Generar entre 2-6 detalles por cotización, asegurando productos únicos
                $numDetalles = rand(2, 6);
                $productosUsados = $productos->shuffle()->take(min($numDetalles, $productos->count()));
                $subtotalCotizacion = 0;

                foreach ($productosUsados as $producto) {
                    // Evitar duplicados existentes
                    $exists = CotizacionDetalle::where('cotizacion_id', $cotizacion->id)
                        ->where('producto_id', $producto->id)
                        ->where('proveedor_id', $cotizacion->proveedor_id)
                        ->exists();

                    if ($exists) continue;

                    $cantidad = rand(1, 50);
                    $precioUnitario = $this->generarPrecioUnitario($producto->precio ?? 100);
                    $subtotal = $cantidad * $precioUnitario;
                    $subtotalCotizacion += $subtotal;

                    $detalles[] = [
                        'proveedor_id' => $cotizacion->proveedor_id,
                        'cotizacion_id' => $cotizacion->id,
                        'producto_id' => $producto->id,
                        'cantidad_cotizada' => $cantidad,
                        'precio_unitario' => $precioUnitario,
                        'subtotal' => $subtotal,
                        'tiempo_entrega_dias' => $this->generarTiempoEntrega(),
                        'observaciones' => $this->generarObservacionesDetalle($producto->nombre ?? 'Producto'),
                        'created_at' => $cotizacion->created_at,
                        'updated_at' => $cotizacion->updated_at,
                    ];

                    $totalDetallesCreados++;
                }

                // Actualizar el total de la cotización basado en los detalles
                $cotizacion->update(['total' => round($subtotalCotizacion, 2)]);
            }

            // Insertar detalles en lotes para mejor rendimiento
            if (!empty($detalles)) {
                $chunks = array_chunk($detalles, 100);
                foreach ($chunks as $chunk) {
                    DB::table('cotizacion_detalles')->insert($chunk);
                }
            }

            echo "✅ Seeder CotizacionDetalleSeeder ejecutado correctamente.\n";
            echo "📊 Se generaron {$totalDetallesCreados} detalles para " . $cotizaciones->count() . " cotizaciones.\n";
            echo "💰 Se actualizaron los totales de las cotizaciones basados en sus detalles.\n";
            echo "📍 Configurado para Los Mochis, Sinaloa, México.\n";
        });
    }

    private function generarPrecioUnitario($precioBase): float
    {
        $factor = rand(80, 150) / 100; // Variación -20% a +50%
        return round($precioBase * $factor, 2);
    }

    private function generarTiempoEntrega(): int
    {
        $tiemposComunes = [1, 3, 5, 7, 10, 15, 21, 30, 45];
        return $tiemposComunes[array_rand($tiemposComunes)];
    }

    private function generarObservacionesDetalle(string $nombreProducto): ?string
    {
        $observaciones = [
            "Entrega incluida en Los Mochis y zona metropolitana",
            "Precio sujeto a disponibilidad en almacén",
            "Incluye instalación básica sin costo adicional",
            "Garantía de 12 meses incluida",
            "Descuento por volumen aplicado",
            "Entrega programada según cronograma de obra",
            "Precio especial por ser cliente frecuente",
            "Incluye capacitación para uso del producto",
            "Cumple con normas mexicanas de construcción",
            "Disponible para entrega inmediata",
            null,
        ];

        return $observaciones[array_rand($observaciones)];
    }
}
