<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;

/**
 * Comando para generar documentación Swagger automáticamente
 * Analiza controllers, modelos y rutas para crear anotaciones
 */
class GenerateSwaggerDocs extends Command
{
    protected $signature = 'swagger:auto-generate 
                            {--force : Sobrescribir anotaciones existentes}
                            {--models : Solo generar schemas de modelos}
                            {--controllers : Solo generar anotaciones de controladores}';

    protected $description = 'Genera documentación Swagger automáticamente analizando controladores y modelos';

    protected $modelsPath = 'app/Models';
    protected $controllersPath = 'app/Http/Controllers';
    protected $swaggerPath = 'app/Http/Swagger';

    public function handle()
    {
        $this->info("🚀 Iniciando generación automática de documentación Swagger...");

        // Crear directorio para archivos Swagger si no existe
        $this->ensureSwaggerDirectory();

        if ($this->option('models') || (!$this->option('controllers') && !$this->option('models'))) {
            $this->generateModelSchemas();
        }

        if ($this->option('controllers') || (!$this->option('controllers') && !$this->option('models'))) {
            $this->generateControllerAnnotations();
        }

        $this->generateMainSwaggerFile();
        $this->generateSwaggerConfig();

        $this->info("✅ Documentación Swagger generada exitosamente!");
        $this->info("📖 Ver documentación en: http://localhost:8080/api/documentation");
        $this->line("🔄 Ejecuta: php artisan l5-swagger:generate");

        return 0;
    }

    protected function ensureSwaggerDirectory()
    {
        if (!File::exists(base_path($this->swaggerPath))) {
            File::makeDirectory(base_path($this->swaggerPath), 0755, true);
        }
    }

    protected function generateModelSchemas()
    {
        $this->info("📋 Generando schemas de modelos...");

        $modelsDir = base_path($this->modelsPath);
        $modelFiles = File::allFiles($modelsDir);

        $schemas = [];

        foreach ($modelFiles as $file) {
            $className = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            $fullClassName = "App\\Models\\{$className}";

            if (class_exists($fullClassName)) {
                $schema = $this->generateModelSchema($fullClassName, $className);
                if ($schema) {
                    $schemas[] = $schema;
                    $this->line("  ✓ Schema generado para: {$className}");
                }
            }
        }

        $this->saveSwaggerFile('Schemas.php', $this->buildSchemasFile($schemas));
    }

    protected function generateModelSchema($className, $modelName)
    {
        try {
            $reflection = new ReflectionClass($className);
            $instance = $reflection->newInstanceWithoutConstructor();

            // Obtener fillable fields
            $fillable = $instance->getFillable() ?? [];

            // Obtener hidden fields
            $hidden = $instance->getHidden() ?? [];

            // Obtener casts para tipos
            $casts = method_exists($instance, 'getCasts') ? $instance->getCasts() : [];

            $properties = $this->generateSchemaProperties($fillable, $casts, $hidden);
            $relations = $this->detectRelations($reflection);

            return $this->buildSchemaAnnotation($modelName, $properties, $relations);
        } catch (\Exception $e) {
            $this->warn("Error generando schema para {$modelName}: {$e->getMessage()}");
            return null;
        }
    }

    protected function generateSchemaProperties($fillable, $casts, $hidden)
    {
        $properties = [];

        // ID siempre presente
        $properties['id'] = [
            'type' => 'integer',
            'example' => 1,
            'description' => 'ID único del registro'
        ];

        foreach ($fillable as $field) {
            if (in_array($field, $hidden)) continue;

            $type = $this->getPropertyType($field, $casts);
            $properties[$field] = [
                'type' => $type['type'],
                'format' => $type['format'] ?? null,
                'example' => $this->generateExample($field, $type),
                'description' => $this->generateDescription($field)
            ];
        }

        // Timestamps
        if (!in_array('created_at', $hidden)) {
            $properties['created_at'] = [
                'type' => 'string',
                'format' => 'datetime',
                'example' => '2024-01-01T00:00:00.000000Z'
            ];
        }

        if (!in_array('updated_at', $hidden)) {
            $properties['updated_at'] = [
                'type' => 'string',
                'format' => 'datetime',
                'example' => '2024-01-01T00:00:00.000000Z'
            ];
        }

        return $properties;
    }

    protected function getPropertyType($field, $casts)
    {
        // Si está en casts, usar ese tipo
        if (isset($casts[$field])) {
            $cast = $casts[$field];
            switch ($cast) {
                case 'boolean':
                case 'bool':
                    return ['type' => 'boolean'];
                case 'integer':
                case 'int':
                    return ['type' => 'integer'];
                case 'float':
                case 'double':
                case 'decimal':
                    return ['type' => 'number', 'format' => 'float'];
                case 'datetime':
                case 'timestamp':
                    return ['type' => 'string', 'format' => 'datetime'];
                case 'date':
                    return ['type' => 'string', 'format' => 'date'];
                case 'array':
                case 'json':
                    return ['type' => 'array'];
            }
        }

        // Inferir tipo por nombre de campo
        if (Str::endsWith($field, '_id')) {
            return ['type' => 'integer'];
        }

        if (Str::endsWith($field, ['_at', '_time'])) {
            return ['type' => 'string', 'format' => 'datetime'];
        }

        if (Str::contains($field, ['email'])) {
            return ['type' => 'string', 'format' => 'email'];
        }

        if (Str::contains($field, ['password'])) {
            return ['type' => 'string', 'format' => 'password'];
        }

        if (Str::contains($field, ['precio', 'costo', 'amount', 'total'])) {
            return ['type' => 'number', 'format' => 'float'];
        }

        if (Str::contains($field, ['activo', 'enabled', 'visible', 'status'])) {
            return ['type' => 'boolean'];
        }

        return ['type' => 'string'];
    }

    protected function generateExample($field, $type)
    {
        switch ($type['type']) {
            case 'integer':
                return Str::endsWith($field, '_id') ? 1 : 100;
            case 'number':
                return Str::contains($field, ['precio', 'cost']) ? 150.50 : 10.5;
            case 'boolean':
                return true;
            case 'string':
                if ($type['format'] === 'email') return 'usuario@example.com';
                if ($type['format'] === 'password') return 'password123';
                if (Str::contains($field, 'nombre')) return 'Ejemplo Nombre';
                if (Str::contains($field, 'descripcion')) return 'Descripción de ejemplo';
                if (Str::contains($field, 'codigo')) return 'COD-001';
                if (Str::contains($field, 'sku')) return 'SKU-001';
                return 'ejemplo';
            default:
                return null;
        }
    }

    protected function generateDescription($field)
    {
        $descriptions = [
            'nombre' => 'Nombre del registro',
            'descripcion' => 'Descripción detallada',
            'email' => 'Dirección de correo electrónico',
            'telefono' => 'Número de teléfono',
            'direccion' => 'Dirección física',
            'activo' => 'Estado activo/inactivo',
            'created_at' => 'Fecha de creación',
            'updated_at' => 'Fecha de última actualización'
        ];

        return $descriptions[$field] ?? Str::title(str_replace('_', ' ', $field));
    }

    protected function detectRelations($reflection)
    {
        $relations = [];
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if (
                $method->getNumberOfParameters() === 0 &&
                !in_array($method->getName(), ['toArray', 'toJson', 'getAttribute', 'setAttribute'])
            ) {

                $methodName = $method->getName();

                // Detectar relaciones comunes
                if (Str::endsWith($methodName, ['s']) && !Str::endsWith($methodName, 'ies')) {
                    // Posible hasMany
                    $relations[] = [
                        'name' => $methodName,
                        'type' => 'array',
                        'items' => Str::singular(Str::studly($methodName))
                    ];
                } elseif (Str::contains($methodName, ['proveedor', 'categoria', 'marca', 'user'])) {
                    // Posible belongsTo
                    $relations[] = [
                        'name' => $methodName,
                        'type' => 'object',
                        'ref' => Str::studly($methodName)
                    ];
                }
            }
        }

        return $relations;
    }

    protected function generateControllerAnnotations()
    {
        $this->info("🎮 Generando anotaciones de controladores...");

        // Analizar rutas del archivo api.php
        $routeFile = base_path('routes/api.php');
        $routes = $this->parseRoutes($routeFile);

        foreach ($routes as $route) {
            $this->generateControllerDocumentation($route);
        }
    }

    protected function parseRoutes($routeFile)
    {
        $content = File::get($routeFile);
        $routes = [];

        // Regex para capturar rutas
        preg_match_all('/Route::(get|post|put|patch|delete)\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*\[([^\]]+)\]/', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $method = $match[1];
            $path = $match[2];
            $controller = $match[3];

            // Parsear controller y método
            if (preg_match('/([^:]+)::([^,\]]+)/', $controller, $controllerMatch)) {
                $routes[] = [
                    'method' => strtoupper($method),
                    'path' => '/api/' . ltrim($path, '/'),
                    'controller' => $controllerMatch[1],
                    'action' => $controllerMatch[2]
                ];
            }
        }

        return $routes;
    }

    protected function generateControllerDocumentation($route)
    {
        $controllerClass = str_replace(['[', ']', "'", '"'], '', $route['controller']);
        $action = str_replace(['[', ']', "'", '"'], '', $route['action']);

        $annotation = $this->buildRouteAnnotation($route, $controllerClass, $action);

        if ($annotation) {
            $this->line("  ✓ Ruta documentada: {$route['method']} {$route['path']}");
        }
    }

    protected function buildRouteAnnotation($route, $controller, $action)
    {
        $method = strtolower($route['method']);
        $path = $route['path'];
        $tag = $this->extractTagFromController($controller);

        $summary = $this->generateSummary($action, $method);
        $description = $this->generateDescription($action, $method);

        $parameters = $this->extractParameters($path);
        $requestBody = $this->generateRequestBody($method, $tag);
        $responses = $this->generateResponses($method, $tag);

        return [
            'method' => $method,
            'path' => $path,
            'summary' => $summary,
            'description' => $description,
            'tag' => $tag,
            'parameters' => $parameters,
            'requestBody' => $requestBody,
            'responses' => $responses
        ];
    }

    protected function extractTagFromController($controller)
    {
        // Extraer nombre del controlador
        $parts = explode('\\', $controller);
        $className = end($parts);
        return str_replace('Controller', '', $className);
    }

    protected function generateSummary($action, $method)
    {
        $actionMap = [
            'index' => 'Listar',
            'store' => 'Crear',
            'show' => 'Obtener',
            'update' => 'Actualizar',
            'destroy' => 'Eliminar'
        ];

        return $actionMap[$action] ?? ucfirst($action);
    }

    protected function buildSchemasFile($schemas)
    {
        $content = "<?php\n\n";
        $content .= "/**\n";
        $content .= " * SCHEMAS GENERADOS AUTOMÁTICAMENTE\n";
        $content .= " * Generado el: " . now()->format('Y-m-d H:i:s') . "\n";
        $content .= " */\n\n";

        foreach ($schemas as $schema) {
            $content .= $schema . "\n\n";
        }

        return $content;
    }

    protected function buildSchemaAnnotation($modelName, $properties, $relations)
    {
        $annotation = "/**\n";
        $annotation .= " * @OA\\Schema(\n";
        $annotation .= " *     schema=\"{$modelName}\",\n";
        $annotation .= " *     type=\"object\",\n";
        $annotation .= " *     title=\"{$modelName}\",\n";
        $annotation .= " *     description=\"Modelo de {$modelName}\",\n";

        foreach ($properties as $name => $prop) {
            $annotation .= " *     @OA\\Property(\n";
            $annotation .= " *         property=\"{$name}\",\n";
            $annotation .= " *         type=\"{$prop['type']}\",\n";

            if ($prop['format']) {
                $annotation .= " *         format=\"{$prop['format']}\",\n";
            }

            if ($prop['example'] !== null) {
                $example = is_string($prop['example']) ? "\"{$prop['example']}\"" : $prop['example'];
                $annotation .= " *         example={$example},\n";
            }

            $annotation .= " *         description=\"{$prop['description']}\"\n";
            $annotation .= " *     ),\n";
        }

        // Agregar relaciones
        foreach ($relations as $relation) {
            $annotation .= " *     @OA\\Property(\n";
            $annotation .= " *         property=\"{$relation['name']}\",\n";

            if ($relation['type'] === 'array') {
                $annotation .= " *         type=\"array\",\n";
                $annotation .= " *         @OA\\Items(ref=\"#/components/schemas/{$relation['items']}\")\n";
            } else {
                $annotation .= " *         ref=\"#/components/schemas/{$relation['ref']}\"\n";
            }

            $annotation .= " *     ),\n";
        }

        $annotation .= " * )\n";
        $annotation .= " */";

        return $annotation;
    }

    protected function generateMainSwaggerFile()
    {
        $content = "<?php\n\n";
        $content .= "/**\n";
        $content .= " * @OA\\Info(\n";
        $content .= " *     title=\"API Proveedores\",\n";
        $content .= " *     version=\"1.0.0\",\n";
        $content .= " *     description=\"API para gestión de proveedores, productos y requisiciones\",\n";
        $content .= " *     @OA\\Contact(\n";
        $content .= " *         email=\"admin@example.com\"\n";
        $content .= " *     )\n";
        $content .= " * )\n";
        $content .= " *\n";
        $content .= " * @OA\\Server(\n";
        $content .= " *     url=\"http://localhost:8080/api\",\n";
        $content .= " *     description=\"Servidor de desarrollo\"\n";
        $content .= " * )\n";
        $content .= " *\n";
        $content .= " * @OA\\SecurityScheme(\n";
        $content .= " *     securityScheme=\"bearerAuth\",\n";
        $content .= " *     type=\"http\",\n";
        $content .= " *     scheme=\"bearer\",\n";
        $content .= " *     bearerFormat=\"JWT\"\n";
        $content .= " * )\n";
        $content .= " */\n";

        $this->saveSwaggerFile('Main.php', $content);
    }

    protected function generateSwaggerConfig()
    {
        // Actualizar configuración de l5-swagger
        $configPath = config_path('l5-swagger.php');
        $config = File::get($configPath);

        // Asegurar que escanee el directorio de Swagger
        if (strpos($config, 'app/Http/Swagger') === false) {
            $config = str_replace(
                "'annotations' => [\n                    base_path('app'),\n                ]",
                "'annotations' => [\n                    base_path('app'),\n                    base_path('app/Http/Swagger'),\n                ]",
                $config
            );
            File::put($configPath, $config);
        }
    }

    protected function saveSwaggerFile($filename, $content)
    {
        $path = base_path($this->swaggerPath . '/' . $filename);
        File::put($path, $content);
    }

    protected function extractParameters($path)
    {
        $parameters = [];
        preg_match_all('/\{([^}]+)\}/', $path, $matches);

        foreach ($matches[1] as $param) {
            $parameters[] = [
                'name' => $param,
                'type' => 'integer',
                'required' => true
            ];
        }

        return $parameters;
    }

    protected function generateRequestBody($method, $tag)
    {
        if (!in_array($method, ['post', 'put', 'patch'])) {
            return null;
        }

        return [
            'required' => true,
            'content' => 'application/json'
        ];
    }

    protected function generateResponses($method, $tag)
    {
        $responses = [];

        switch ($method) {
            case 'get':
                $responses['200'] = 'Datos obtenidos exitosamente';
                break;
            case 'post':
                $responses['201'] = 'Recurso creado exitosamente';
                break;
            case 'put':
            case 'patch':
                $responses['200'] = 'Recurso actualizado exitosamente';
                break;
            case 'delete':
                $responses['204'] = 'Recurso eliminado exitosamente';
                break;
        }

        $responses['400'] = 'Solicitud incorrecta';
        $responses['401'] = 'No autorizado';
        $responses['404'] = 'Recurso no encontrado';
        $responses['500'] = 'Error interno del servidor';

        return $responses;
    }
}
