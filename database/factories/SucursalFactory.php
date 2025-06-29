<?php

namespace Database\Factories;

use App\Models\Proveedor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sucursal>
 */

class SucursalFactory extends Factory
{
    public function definition()
    {
        return [
            'proveedor_id' => Proveedor::factory(),
            'nombre' => $this->faker->company . ' - Sucursal',
            'direccion' => $this->faker->address,
            'telefono' => $this->faker->phoneNumber,
            'email' => $this->faker->companyEmail,
            'encargado' => $this->faker->name,
            'activa' => true,
            'coordenadas_lat' => $this->faker->latitude(24.5, 25.5),
            'coordenadas_lng' => $this->faker->longitude(-107.5, -106.5),
        ];
    }

    public function inactiva()
    {
        return $this->state([
            'activa' => false,
        ]);
    }
}
