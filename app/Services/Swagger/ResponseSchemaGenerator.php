<?php

namespace App\Services\Swagger;

use Illuminate\Support\Facades\File;

class ResponseSchemaGenerator
{
    public function generate()
    {
        $content = '<?php

/**
 * Respuestas estándar de la API
 * Generado automáticamente el ' . now()->format('Y-m-d H:i:s') . '
 */

/**
 * @OA\Schema(schema="ApiResponse", type="object")
 */';
        File::put(app_path('Http/Swagger/Responses.php'), $content);
        echo "✓ Respuestas estándar generadas\n";
    }
}
