<?php

namespace Database\Seeders;

use App\Models\Proveedor;
use Illuminate\Database\Seeder;
use App\Models\UnidadMedida;

class UnidadMedidaSeeder extends Seeder
{
  public function run()
  {
    $unidades = [
      ['descripcion' => 'm',     'nombre' => 'Metro',             'clave' => 'MTR'],
      ['descripcion' => 'm2',    'nombre' => 'Metro Cuadrado',    'clave' => 'MTK'],
      ['descripcion' => 'm3',    'nombre' => 'Metro Cúbico',      'clave' => 'MTQ'], // estándar UNECE
      ['descripcion' => 'kg',    'nombre' => 'Kilogramo',         'clave' => 'KGM'],
      ['descripcion' => 'g',     'nombre' => 'Gramo',             'clave' => 'GRM'],
      ['descripcion' => 't',     'nombre' => 'Tonelada',          'clave' => 'TNE'], // estándar UNECE
      ['descripcion' => 'lt',    'nombre' => 'Litro',             'clave' => 'LTR'],
      ['descripcion' => 'ml',    'nombre' => 'Mililitro',         'clave' => 'MLT'],
      ['descripcion' => 'pza',   'nombre' => 'Pieza',             'clave' => 'H87'],
      ['descripcion' => 'cj',    'nombre' => 'Caja',              'clave' => 'XBX'],
      ['descripcion' => 'bulto', 'nombre' => 'Bulto',             'clave' => 'BX'],
      ['descripcion' => 'par',   'nombre' => 'Par',               'clave' => 'PR'],
      ['descripcion' => 'jgo',   'nombre' => 'Juego',             'clave' => 'SET'],
      ['descripcion' => 'ro',    'nombre' => 'Rollo',             'clave' => 'RO'],
      ['descripcion' => 'mll',   'nombre' => 'Milla',             'clave' => 'SMI'],
    ];

    $fierroYLamina = Proveedor::where('nombre_comercial', 'Fierro y Lámina')->first();
    $truper = Proveedor::where('nombre_comercial', 'Truper')->first();
    $granjasElGranGero = Proveedor::where('nombre_comercial', 'Granjas ElGranGero')->first();


    foreach ([$fierroYLamina, $truper, $granjasElGranGero,] as $proveedor) {
      foreach ($unidades as $unidad) {
        UnidadMedida::firstOrCreate(
          [
            'proveedor_id' => $proveedor->id,
            'descripcion' => $unidad['descripcion'],
            'nombre' => $unidad['nombre'],
            'clave' => $unidad['clave']
          ]
        );
      }
    }
  }
}
