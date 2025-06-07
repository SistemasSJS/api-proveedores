<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Catalogo;
use App\Models\Proveedor;

class CatalogoSeeder extends Seeder
{
    public function run()
    {
        $fierroYLamina = Proveedor::where('nombre_comercial', 'Fierro y Lámina')->first();
        $truper = Proveedor::where('nombre_comercial', 'Truper')->first();
        $granjasElGranGero = Proveedor::where('nombre_comercial', 'Granjas ElGranGero')->first();

        // Catálogos específicos
        $fierroYLaminaCatalogos = [
            'Láminas y Aceros',
            'Material de Construcción',
            'Herramientas Básicas'
        ];

        $truperCatalogos = [
            'Herramientas Manuales',
            'Herramientas Eléctricas',
            'Accesorios Industriales'
        ];

        $granjasElGranGeroCatalogos = [
            'Equipamiento Agroindustrial',
            'Insumos para Granjas',
            'Mantenimiento de Instalaciones'
        ];

        foreach ($fierroYLaminaCatalogos as $nombre) {
            Catalogo::factory()->create([
                'proveedor_id' => $fierroYLamina->id,
                'nombre' => $nombre,
            ]);
        }

        foreach ($truperCatalogos as $nombre) {
            Catalogo::factory()->create([
                'proveedor_id' => $truper->id,
                'nombre' => $nombre,
            ]);
        }

        foreach ($granjasElGranGeroCatalogos as $nombre) {
            Catalogo::factory()->create([
                'proveedor_id' => $granjasElGranGero->id,
                'nombre' => $nombre,
            ]);
        }

        // Otros proveedores sin nombre comercial específico
        $otrosProveedores = Proveedor::whereNotIn('id', [
            $fierroYLamina->id,
            $truper->id,
            $granjasElGranGero->id,
        ])->get();

        foreach ($otrosProveedores as $proveedor) {
            Catalogo::factory()->count(2)->create([
                'proveedor_id' => $proveedor->id,
            ]);
        }
    }
}
