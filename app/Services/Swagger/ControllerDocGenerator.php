<?php

namespace App\Services\Swagger;

use Illuminate\Support\Facades\File;

class ControllerDocGenerator
{
    public function generate()
    {
        $controllersPath = app_path('Http/Controllers');
        $controllerFiles = File::allFiles($controllersPath);

        foreach ($controllerFiles as $file) {
            $controllerName = pathinfo($file->getFilename(), PATHINFO_FILENAME);

            if (in_array($controllerName, ['Controller', 'BaseController'])) {
                continue;
            }

            $documentation = "<?php

/**
 * Documentación para {$controllerName}
 * Generado automáticamente el " . now()->format('Y-m-d H:i:s') . "
 * 
 * La documentación de este controlador se encuentra en el archivo de clase:
 * app/Http/Controllers/{$controllerName}.php
 */";

            File::put(app_path("Http/Swagger/Controllers/{$controllerName}.php"), $documentation);
            echo "✓ Documentación generada: Controllers/{$controllerName}.php\n";
        }
    }
}
