<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TipoEmpresa;

class TipoEmpresaSeeder extends Seeder
{
    public function run()
    {
        // Puedes generar una constante enum después, por ahora usamos nombres fijos
        $tipos_empresas = [
            'otro' => "Otro",
            'obra_civil' => "Constructora de Obra Civil",
            'vivienda' => "Constructora de Vivienda",
            'comercial' => "Constructora de Obra Comercial",
            'industrial' => "Constructora Industrial",
            'obra_publica' => "Constructora de Obra Pública",
            'remodelacion' => "Empresa de Remodelación y Mantenimiento",
            'urbanizacion' => "Empresa de Urbanización",
            'sostenible' => "Empresa de Construcción Sostenible",
            'especializados' => "Empresa de Servicios Especializados",
            'desarrolladora' => "Desarrolladora Inmobiliaria",
        ];

        foreach ($tipos_empresas as $key => $nombre) {
            TipoEmpresa::factory()->create([
                'clave' => $key,
                'nombre' => $nombre
            ]);
        }
    }
}
