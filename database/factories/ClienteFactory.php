<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    public function definition()
    {
        $tipoCliente = $this->faker->randomElement(['EMPRESA', 'PERSONA_FISICA']);
        $empresa = $tipoCliente === 'EMPRESA' ? $this->faker->company() : null;
        
        return [
            'user_id' => User::factory(),
            'nombre' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'telefono' => '667' . $this->faker->numberBetween(1000000, 9999999),
            'empresa' => $empresa,
            'rfc' => $this->generateRFC(),
            'direccion' => $this->faker->streetAddress(),
            'ciudad' => $this->faker->randomElement(['Los Mochis', 'Ahome', 'Guasave', 'El Fuerte', 'Choix']),
            'estado' => 'Sinaloa',
            'codigo_postal' => $this->faker->randomElement(['81200', '81220', '81000', '81890', '81940']),
            'tipo_cliente' => $tipoCliente,
            'estatus' => $this->faker->randomElement(['ACTIVO', 'INACTIVO']),
            'fecha_registro' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'notas' => $this->faker->optional()->sentence(10)
        ];
    }

    private function generateRFC()
    {
        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numbers = '0123456789';
        
        return substr(str_shuffle($letters), 0, 3) . 
               substr(str_shuffle($numbers), 0, 6) . 
               substr(str_shuffle($letters . $numbers), 0, 3);
    }

    public function empresa()
    {
        return $this->state(function (array $attributes) {
            return [
                'tipo_cliente' => 'EMPRESA',
                'empresa' => $this->faker->company(),
            ];
        });
    }

    public function personaFisica()
    {
        return $this->state(function (array $attributes) {
            return [
                'tipo_cliente' => 'PERSONA_FISICA',
                'empresa' => null,
            ];
        });
    }

    public function activo()
    {
        return $this->state(function (array $attributes) {
            return [
                'estatus' => 'ACTIVO',
            ];
        });
    }
}
