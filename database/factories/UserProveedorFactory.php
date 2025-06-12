<?php
// database/factories/UserProveedorFactory.php

use App\Models\Proveedor;
use App\Models\User;
use App\Models\UserProveedor;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserProveedorFactory extends Factory
{
  protected $model = UserProveedor::class;

  public function definition()
  {
    return [
      'user_id' => User::factory(),
      'proveedor_id' => Proveedor::factory(),
      'tipo_relacion' => $this->faker->randomElement(['PRINCIPAL', 'SECUNDARIO']),
      'activo' => true,
      'fecha_asignacion' => $this->faker->dateTimeBetween('-1 year', 'now'),
      'fecha_desasignacion' => null,
      'observaciones' => $this->faker->optional()->sentence(),
    ];
  }

  public function principal()
  {
    return $this->state(['tipo_relacion' => 'PRINCIPAL']);
  }

  public function secundario()
  {
    return $this->state(['tipo_relacion' => 'SECUNDARIO']);
  }

  public function inactivo()
  {
    return $this->state([
      'activo' => false,
      'fecha_desasignacion' => $this->faker->dateTimeBetween('-6 months', 'now'),
    ]);
  }
}
