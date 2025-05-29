<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TipoEmpresa;

class TipoEmpresaSeeder extends Seeder
{
    public function run()
    {
        $tipos_empresas = [
            "Constructora de Obra Civil",
            "Constructora de Vivienda",
            "Constructora de Obra Comercial",
            "Constructora Industrial",
            "Constructora de Obra Pública",
            "Empresa de Remodelación y Mantenimiento",
            "Empresa de Urbanización",
            "Empresa de Construcción Sostenible",
            "Empresa de Servicios Especializados",
            "Desarrolladora Inmobiliaria",
        ];
        foreach ($tipos_empresas as $name) {
            TipoEmpresa::factory()->create(['nombre' => $name]);
        }
    }
}
