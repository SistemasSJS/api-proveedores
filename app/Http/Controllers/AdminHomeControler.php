<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Http\Request;

class AdminHomeControler extends Controller
{

    /**
     * Obtiene las métricas de la home del administrador.
     *  - # total de usuarios registrados
     *  - # users activos con actividad de no menos 1 semana
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function metricasHome(Request $request)
    {
        $metricas = [
            'proveedores' => $this->getCatalogosCountItems($request),
            'productos' => $this->getCatalogosCountItems($request),
            'usuarios' => $this->getCatalogosCountItems($request),
        ];
        return $this->success($metricas);
    }

    /**
     * Obtiene el número de items en los catálogos de proveedores, productos y usuarios.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCatalogosCountItems(Request $request)
    {
        $catalogos = [
            [
                'name' => 'Proveedores',
                'count' => Proveedor::all()->count(),
                'route' => '/pages/admin/proveedores',
                'icon' => 'briefcase',
            ],
            [
                'name' => 'Productos',
                'count' => Producto::all()->count(),
                'route' => '/pages/admin/productos',
                'icon' => 'cube',
            ],
            [
                'name' => 'Usuarios',
                'count' => User::all()->count(),
                'route' => '/pages/admin/users',
                'icon' => 'people',
            ],
        ];

        return $this->success($catalogos);
    }
}
