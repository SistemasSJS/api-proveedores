<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProveedorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre_comercial' => $this->faker->company,
            'razon_social' => $this->faker->company . ' S.A. de C.V.',
            'rfc' => strtoupper($this->faker->bothify('???######???')),
            'email' => $this->faker->companyEmail,
            'telefono' => $this->faker->phoneNumber,
            'sitio_web' => $this->faker->url,
            'direccion_fiscal' => $this->faker->address,
            'estado' => $this->faker->state,
            'municipio' => $this->faker->city,
            'codigo_postal' => $this->faker->postcode,
            'contacto_nombre' => $this->faker->name,
            'contacto_telefono' => $this->faker->phoneNumber,
            'contacto_email' => $this->faker->safeEmail,
            'estatus' => 'activo',
            'fecha_registro' => now(),
            'user_id' => User::inRandomOrder()->first()?->id ?? 1, // Asegúrate de tener un usuario con ID 1
            'notas' => $this->faker->sentence,
        ];
    }
}
