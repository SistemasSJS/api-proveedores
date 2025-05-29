<?php

namespace Database\Factories;

use App\Models\Catalogo;
use Illuminate\Database\Eloquent\Factories\Factory;

class CatalogoFactory extends Factory
{
    protected $model = Catalogo::class;

    public function definition()
    {
        return [
            'nombre'     => $this->faker->word . ' Catalogo',
            'descripcion' => $this->faker->sentence,
            'photo_path' => null,
        ];
    }
}
