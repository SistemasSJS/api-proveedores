<?php

namespace Database\Seeders;

use App\Models\AccesoRapido;
use Illuminate\Database\Seeder;

class AccesoRapidoSeeder extends Seeder
{
    public function run(): void
    {
        $accesos = [
            [
                'titulo' => 'Productos Destacados',
                'descripcion' => 'Ver productos más populares y recomendados',
                'icono' => 'star-outline',
                'url' => '/tienda/productos/destacados',
                'color' => '#ffc107',
                'orden' => 1,
                'activo' => true
            ],
            [
                'titulo' => 'Proveedores Principales',
                'descripcion' => 'Explorar proveedores de confianza',
                'icono' => 'storefront-outline',
                'url' => '/tienda/proveedores/principales',
                'color' => '#007bff',
                'orden' => 2,
                'activo' => true
            ],
            [
                'titulo' => 'Más Pedidos',
                'descripcion' => 'Productos con mayor demanda',
                'icono' => 'bag-handle-outline',
                'url' => '/tienda/productos/mas-pedidos',
                'color' => '#28a745',
                'orden' => 3,
                'activo' => true
            ],
            [
                'titulo' => 'Novedades',
                'descripcion' => 'Productos agregados recientemente',
                'icono' => 'time-outline',
                'url' => '/tienda/productos/recientes',
                'color' => '#17a2b8',
                'orden' => 4,
                'activo' => true
            ],
            [
                'titulo' => 'Mi Carrito',
                'descripcion' => 'Ver productos en el carrito de compras',
                'icono' => 'cart-outline',
                'url' => '/tienda/carrito',
                'color' => '#dc3545',
                'orden' => 5,
                'activo' => true
            ],
            [
                'titulo' => 'Mis Pedidos',
                'descripcion' => 'Historial y estado de pedidos',
                'icono' => 'receipt-outline',
                'url' => '/tienda/mis-pedidos',
                'color' => '#6f42c1',
                'orden' => 6,
                'activo' => true
            ],
            [
                'titulo' => 'Catálogo',
                'descripcion' => 'Explorar todo el catálogo de productos',
                'icono' => 'grid-outline',
                'url' => '/tienda/catalogo',
                'color' => '#fd7e14',
                'orden' => 7,
                'activo' => true
            ],
            [
                'titulo' => 'Favoritos',
                'descripcion' => 'Productos marcados como favoritos',
                'icono' => 'heart-outline',
                'url' => '/tienda/favoritos',
                'color' => '#e83e8c',
                'orden' => 8,
                'activo' => true
            ]
        ];
        foreach ($accesos as $acceso) {
            AccesoRapido::create($acceso);
        }

        // Crear algunos accesos adicionales usando factory
        // AccesoRapido::factory(5)->create();

    }
}
