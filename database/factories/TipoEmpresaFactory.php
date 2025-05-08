<?php

namespace Database\Factories;

use App\Models\TipoEmpresa;
use Illuminate\Database\Eloquent\Factories\Factory;

class TipoEmpresaFactory extends Factory
{
    protected $model = TipoEmpresa::class;
    public function definition(): array
    {
        return [
            'nombre' => $this->faker->name,
            'estatus' => 'activo',
        ];
    }
}
