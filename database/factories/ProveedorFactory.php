<?php

namespace Database\Factories;

use App\Models\Proveedor;
use App\Models\TipoEmpresa;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProveedorFactory extends Factory
{
    protected $model = Proveedor::class;

    public function definition(): array
    {
        // Aquí puedes agregar el rol si lo tienes
        return [
            'logo' => null,
            'nombre_comercial' => $this->faker->company,
            'razon_social' => $this->faker->company . ' S.A. de C.V.',
            'rfc' => strtoupper($this->faker->bothify('???######???')),
            // 'email' => $this->faker->companyEmail,
            'email' => $this->faker->unique()->safeEmail,
            'telefono' => $this->faker->phoneNumber,
            'direccion_fiscal' => $this->faker->address,
            'estado' => $this->faker->state,
            'municipio' => $this->faker->city,
            'codigo_postal' => $this->faker->postcode,
            'estatus' => 'activo',
            'tipos_empresa_id' => TipoEmpresa::inRandomOrder()->first()?->id, // Asignamos el admin por defecto si no hay usuario
            'notas' => $this->faker->sentence,

            // Nuevos campos agregados
            'nombre_propietario' => $this->faker->name,
            'nombre_de_quien_registra' => $this->faker->name,
            'descripcion_giro_empresa' => $this->faker->sentence,
            'direccion_empresa' => $this->faker->address,
            // 'ubicacion_empresa' => $this->faker->address, // Puedes generar coordenadas si deseas, con faker puedes usar 'latitude' y 'longitude' para obtener ubicaciones geográficas
            'contacto_nombre' => $this->faker->name,
            'contacto_cargo' => $this->faker->jobTitle,
            'contacto_telefono' => $this->faker->phoneNumber,
            'contacto_correo' => $this->faker->safeEmail,
            'pagina_web' => $this->faker->url,
        ];
    }
}
