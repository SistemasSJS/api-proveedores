<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Categoria;
use App\Models\Proveedor;
use PhpParser\Node\Stmt\Catch_;

class CategoriaSeeder extends Seeder
{
    public function run()
    {
        $fierroYLamina = Proveedor::where('nombre_comercial', 'Fierro y Lámina')->first();
        $truper = Proveedor::where('nombre_comercial', 'Truper')->first();
        $granjasElGranGero = Proveedor::where('nombre_comercial', 'Granjas ElGranGero')->first();

        // Catálogos específicos con subcategorías
        $fierroYLaminaCatalogos = [
            ['Láminas y Aceros' => ['Galvanizada', 'Acero Inoxidable', 'Acero al Carbón']],
            'Material de Construcción',
            'Herramientas Básicas',
        ];

        $truperCatalogos = [
            ['Herramientas Manuales' => ['Llaves', 'Destornilladores', 'Martillos']],
            ['Herramientas Eléctricas' => ['Taladros', 'Pulidoras']],
            'Accesorios Industriales',
        ];

        $granjasElGranGeroCatalogos = [
            'Equipamiento Agroindustrial',
            ['Insumos para Granjas' => ['Alimento Balanceado', 'Suplementos']],
            'Mantenimiento de Instalaciones',
        ];

        // Crear categorías y subcategorías para Fierro y Lámina
        foreach ($fierroYLaminaCatalogos as $categoria) {
            if (is_array($categoria)) {
                foreach ($categoria as $nombre => $subcategorias) {
                    $categoriaModel = Categoria::factory()->create([
                        'proveedor_id' => $fierroYLamina->id,
                        'nombre' => $nombre,
                    ]);
                    foreach ($subcategorias as $subNombre) {
                        Categoria::factory()->create([
                            'proveedor_id' => $fierroYLamina->id,
                            'parent_id' => $categoriaModel->id,
                            'nombre' => $subNombre,
                        ]);
                    }
                }
            } else {
                Categoria::factory()->create([
                    'proveedor_id' => $fierroYLamina->id,
                    'nombre' => $categoria,
                ]);
            }
        }

        // Crear categorías y subcategorías para Truper
        foreach ($truperCatalogos as $categoria) {
            if (is_array($categoria)) {
                foreach ($categoria as $nombre => $subcategorias) {
                    $categoriaModel = Categoria::factory()->create([
                        'proveedor_id' => $truper->id,
                        'nombre' => $nombre,
                    ]);
                    foreach ($subcategorias as $subNombre) {
                        Categoria::factory()->create([
                            'proveedor_id' => $truper->id,
                            'parent_id' => $categoriaModel->id,
                            'nombre' => $subNombre,
                        ]);
                    }
                }
            } else {
                Categoria::factory()->create([
                    'proveedor_id' => $truper->id,
                    'nombre' => $categoria,
                ]);
            }
        }

        // Crear categorías y subcategorías para Granjas El Gran Gero
        foreach ($granjasElGranGeroCatalogos as $categoria) {
            if (is_array($categoria)) {
                foreach ($categoria as $nombre => $subcategorias) {
                    $categoriaModel = Categoria::factory()->create([
                        'proveedor_id' => $granjasElGranGero->id,
                        'nombre' => $nombre,
                    ]);
                    foreach ($subcategorias as $subNombre) {
                        Categoria::factory()->create([
                            'proveedor_id' => $granjasElGranGero->id,
                            'parent_id' => $categoriaModel->id,
                            'nombre' => $subNombre,
                        ]);
                    }
                }
            } else {
                Categoria::factory()->create([
                    'proveedor_id' => $granjasElGranGero->id,
                    'nombre' => $categoria,
                ]);
            }
        }
    }
}
