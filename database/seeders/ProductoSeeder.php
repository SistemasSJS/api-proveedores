<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Producto;
use App\Models\Marca;
use App\Models\Linea;
use App\Models\Catalogo;
use App\Models\Categoria;
use App\Models\UnidadMedida;

class ProductoSeeder extends Seeder
{
    public function run()
    {
        $marcas = Marca::all();
        $lineas = Linea::all();
        $categorias = Categoria::all();
        $unidadMedidas = UnidadMedida::all();
        $catalogos = Catalogo::with('proveedor')->get();

        $productosPorCatalogo = [
            'Láminas y Aceros' => ['Lámina galvanizada', 'Lámina negra', 'Ángulo de acero', 'PTR 2x2', 'Canal U'],
            'Material de Construcción' => ['Cemento gris', 'Block hueco', 'Arena fina', 'Grava ¾', 'Varilla 3/8'],
            'Herramientas Básicas' => ['Martillo carpintero', 'Pinza universal', 'Cinta métrica 5m', 'Desarmador plano', 'Llave inglesa'],

            'Herramientas Manuales' => ['Sierra manual', 'Llave allen', 'Cuchilla retractil', 'Tenaza Truper', 'Espátula metálica'],
            'Herramientas Eléctricas' => ['Taladro percutor', 'Rotomartillo', 'Pulidora angular', 'Caladora eléctrica', 'Sierra circular'],
            'Accesorios Industriales' => ['Brocas de acero rápido', 'Disco de corte', 'Cepillo de alambre', 'Guantes de carnaza', 'Gafas de seguridad'],

            'Equipamiento Agroindustrial' => ['Tolva de alimentación', 'Tanque de almacenamiento', 'Extractor de aire', 'Molinillo de granos', 'Sistema de riego'],
            'Insumos para Granjas' => ['Alimento para pollos', 'Vacuna multidosis', 'Vitaminas solubles', 'Bebedero automático', 'Desinfectante agrícola'],
            'Mantenimiento de Instalaciones' => ['Pintura anticorrosiva', 'Malla ciclónica', 'Foco infrarrojo', 'Motor de repuesto', 'Kit de herramientas básicas'],
        ];

        foreach ($catalogos as $catalogo) {
            $nombresProductos = $productosPorCatalogo[$catalogo->nombre] ?? null;

            Producto::factory()->count(10)->create([
                'catalogo_id' => $catalogo->id,
                'unidad_medida_id' => $unidadMedidas->random()->id,
                'linea_id' => $lineas->random()->id,
                'marca_id' => $marcas->random()->id,
            ])->each(function ($producto, $index) use ($categorias, $nombresProductos) {
                if ($nombresProductos) {
                    $producto->nombre = $nombresProductos[array_rand($nombresProductos)];
                    $producto->save();
                }

                $producto->categorias()->attach(
                    $categorias->random(rand(1, 3))->pluck('id')->toArray()
                );
            });
        }
    }
}
