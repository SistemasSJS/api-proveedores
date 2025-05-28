<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Http\Request;

class AdminHomeControler extends Controller
{

  public function getCatalogosCountItems(Request $request)
  {
    $catalogos = [
      [
        'name' => 'Proveedores',
        'count' => Proveedor::all()->count(),
        'route' => '/pages/admin/proveedor',
        'icon' => 'briefcase',
      ],
      [
        'name' => 'Productos',
        'count' => Producto::all()->count(),
        'route' => '/pages/admin/producto',
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
