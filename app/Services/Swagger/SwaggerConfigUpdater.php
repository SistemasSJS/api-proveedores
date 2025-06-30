<?php

namespace App\Services\Swagger;

use Illuminate\Support\Facades\File;

class SwaggerConfigUpdater
{
    public function update()
    {
        $configPath = config_path('l5-swagger.php');

        if (!File::exists($configPath)) return;

        $config = File::get($configPath);

        if (!str_contains($config, 'app/Http/Swagger')) {
            $newAnnotations = "'annotations' => [
                base_path('app'),
                base_path('app/Http/Swagger'),
                base_path('app/Http/Swagger/Models'),
                base_path('app/Http/Swagger/Controllers'),
                base_path('app/Http/Swagger/Resources'),
                base_path('app/Http/Swagger/Responses'),
            ]";

            $config = str_replace(
                "'annotations' => [\n                    base_path('app'),\n                ]",
                $newAnnotations,
                $config
            );

            File::put($configPath, $config);
        }

        if (str_contains($config, "'title' => 'L5 Swagger UI'")) {
            $config = str_replace(
                "'title' => 'L5 Swagger UI'",
                "'title' => 'API Proveedores - Documentación'",
                $config
            );

            File::put($configPath, $config);
        }

        echo "✓ Configuración Swagger actualizada\n";
    }
}
