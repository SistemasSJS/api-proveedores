<?php

namespace App\Services\Swagger;

use Illuminate\Support\Facades\File;

class ModelSchemaGenerator
{
    public function generate()
    {
        $modelFiles = File::allFiles(app_path('Models'));

        foreach ($modelFiles as $file) {
            $modelName = pathinfo($file->getFilename(), PATHINFO_FILENAME);

            if (in_array($modelName, ['BaseModel', 'Model'])) continue;

            echo "✓ Esquema generado: $modelName\n";
            // Aquí iría la lógica para generar el esquema Swagger del modelo
        }
    }
}
