<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Proveedor;
use App\Models\Sucursal;
use App\Enums\EstadoGeneral;

class SucursalSeeder extends Seeder
{
  public function run()
  {
    $proveedores = Proveedor::all();

    foreach ($proveedores as $proveedor) {
      // Puedes ajustar el número según tu necesidad
      $cantidadSucursales = rand(1, 3);

      for ($i = 1; $i <= $cantidadSucursales; $i++) {
        Sucursal::create([
          'proveedor_id'      => $proveedor->id,
          'nombre'            => 'Sucursal ' . $i . ' de ' . $proveedor->nombre_comercial,
          'direccion'         => 'Calle Falsa #' . rand(100, 999) . ', Col. Centro',
          'telefono'          => '667' . rand(1000000, 9999999),
          'email'             => 'sucursal' . $i . '@' . strtolower(str_replace(' ', '', $proveedor->nombre_comercial)) . '.com',
          'encargado'         => 'Encargado ' . $i,
          'activa'            => true,
          'coordenadas_lat'   => 24.80 + (rand(0, 1000) / 10000), // Ej. Culiacán
          'coordenadas_lng'   => -107.39 + (rand(0, 1000) / 10000),
          'estatus'           => EstadoGeneral::ACTIVO->value,
        ]);
      }
    }
  }
}
