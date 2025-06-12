<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{User, Proveedor};

class UserProveedorSeeder extends Seeder
{
  public function run()
  {
    // Obtener usuarios con roles de proveedor
    $usuariosProveedor = User::whereHas('role', function ($query) {
      $query->whereIn('nombre', ['GERENTE', 'SUPERVISOR', 'VENTAS', 'AUXILIAR']);
    })->get();

    $proveedores = Proveedor::all();

    foreach ($usuariosProveedor as $user) {
      // Asignar usuario a un proveedor aleatorio como principal
      $proveedor = $proveedores->random();

      $user->proveedores()->attach($proveedor->id, [
        'tipo_relacion' => 'PRINCIPAL',
        'activo' => true,
        'fecha_asignacion' => now(),
        'observaciones' => 'Usuario principal del proveedor',
      ]);

      // 30% de probabilidad de asignar a un segundo proveedor como secundario
      if (rand(1, 100) <= 30) {
        $segundoProveedor = $proveedores->where('id', '!=', $proveedor->id)->random();

        $user->proveedores()->attach($segundoProveedor->id, [
          'tipo_relacion' => 'SECUNDARIO',
          'activo' => true,
          'fecha_asignacion' => now(),
          'observaciones' => 'Usuario de apoyo',
        ]);
      }
    }
  }
}
