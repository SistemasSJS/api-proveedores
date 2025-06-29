<?php

class GenerateCompleteSwagger extends \Illuminate\Console\Command
{
  protected $signature = 'swagger:generate-all {--force}';
  protected $description = 'Genera documentación Swagger completa automáticamente';

  public function handle()
  {
    $this->info("🚀 Generando documentación Swagger completa...");

    // 1. Generar archivo principal de configuración
    $this->generateMainSwaggerFile();

    // 2. Generar schemas de todos los modelos
    $this->generateAllModelSchemas();

    // 3. Generar documentación de controladores
    $this->generateAllControllerDocs();

    // 4. Generar respuestas estándar
    $this->generateStandardResponses();

    // 5. Ejecutar generador de l5-swagger
    $this->call('l5-swagger:generate');

    $this->info("✅ Documentación generada exitosamente!");
    $this->info("📖 Ver en: http://localhost:8080/api/documentation");

    return 0;
  }

  private function generateMainSwaggerFile()
  {
    $content = '<?php

                /**
                 * @OA\Info(
                 *     title="API Proveedores",
                 *     version="1.0.0",
                 *     description="API para gestión de proveedores, productos, requisiciones y catálogos de materiales",
                 *     @OA\Contact(
                 *         email="admin@example.com"
                 *     ),
                 *     @OA\License(
                 *         name="MIT",
                 *         url="https://opensource.org/licenses/MIT"
                 *     )
                 * )
                 *
                 * @OA\Server(
                 *     url="http://localhost:8080/api",
                 *     description="Servidor de desarrollo"
                 * )
                 *
                 * @OA\Server(
                 *     url="https://api.construcc.com/api",
                 *     description="Servidor de producción"
                 * )
                 *
                 * @OA\SecurityScheme(
                 *     securityScheme="bearerAuth",
                 *     type="http",
                 *     scheme="bearer",
                 *     bearerFormat="JWT",
                 *     description="Token de autenticación JWT"
                 * )
                 *
                 * @OA\Tag(
                 *     name="Autenticación",
                 *     description="Endpoints de autenticación y gestión de sesiones"
                 * )
                 *
                 * @OA\Tag(
                 *     name="Productos",
                 *     description="Gestión de productos del catálogo"
                 * )
                 *
                 * @OA\Tag(
                 *     name="Proveedores",
                 *     description="Gestión de proveedores"
                 * )
                 *
                 * @OA\Tag(
                 *     name="Categorías",
                 *     description="Gestión de categorías de productos"
                 * )
                 *
                 * @OA\Tag(
                 *     name="Marcas",
                 *     description="Gestión de marcas"
                 * )
                 *
                 * @OA\Tag(
                 *     name="Líneas",
                 *     description="Gestión de líneas de productos"
                 * )
                 *
                 * @OA\Tag(
                 *     name="Importación",
                 *     description="Importación masiva de productos"
                 * )
                 *
                 * @OA\Tag(
                 *     name="Requisiciones",
                 *     description="Gestión de requisiciones de materiales"
                 * )
                 */';

    file_put_contents(app_path('Http/Swagger/Main.php'), $content);
    $this->line("  ✓ Archivo principal generado");
  }

  private function generateAllModelSchemas()
  {
    $modelsPath = app_path('Models');
    $models = glob($modelsPath . '/*.php');

    $schemas = [];

    foreach ($models as $modelPath) {
      $modelName = basename($modelPath, '.php');
      $modelClass = "App\\Models\\{$modelName}";

      if (class_exists($modelClass)) {
        $schema = $this->generateModelSchema($modelClass, $modelName);
        if ($schema) {
          $schemas[] = $schema;
          $this->line("  ✓ Schema generado: {$modelName}");
        }
      }
    }

    $content = "<?php\n\n" . implode("\n\n", $schemas);
    file_put_contents(app_path('Http/Swagger/Schemas.php'), $content);
  }

  private function generateModelSchema($modelClass, $modelName)
  {
    try {
      $reflection = new \ReflectionClass($modelClass);

      // Verificar si usa el trait
      if (in_array('App\Traits\AutoSwaggerSchema', $reflection->getTraitNames())) {
        $schemaData = $modelClass::getSwaggerSchema();
        return $this->buildSchemaAnnotation($modelName, $schemaData);
      }

      // Fallback: generar schema básico
      return $this->generateBasicSchema($modelClass, $modelName);
    } catch (\Exception $e) {
      $this->warn("Error generando schema para {$modelName}: {$e->getMessage()}");
      return null;
    }
  }

  private function generateBasicSchema($modelClass, $modelName)
  {
    $instance = new $modelClass();
    $fillable = method_exists($instance, 'getFillable') ? $instance->getFillable() : [];

    $properties = [];
    foreach ($fillable as $field) {
      $properties[] = " *         @OA\\Property(property=\"{$field}\", type=\"string\", example=\"ejemplo\")";
    }

    return "/**
            * @OA\\Schema(
            *     schema=\"{$modelName}\",
            *     type=\"object\",
            *     title=\"{$modelName}\",
            *     description=\"Modelo de {$modelName}\",
            *     @OA\\Property(property=\"id\", type=\"integer\", example=1),
            " . implode(",\n", $properties) . ",
            *     @OA\\Property(property=\"created_at\", type=\"string\", format=\"datetime\", example=\"2024-01-01T00:00:00.000000Z\"),
            *     @OA\\Property(property=\"updated_at\", type=\"string\", format=\"datetime\", example=\"2024-01-01T00:00:00.000000Z\")
            * )
            */";
  }

  private function buildSchemaAnnotation($modelName, $schemaData)
  {
    $annotation = "/**\n * @OA\\Schema(\n";
    $annotation .= " *     schema=\"{$modelName}\",\n";
    $annotation .= " *     type=\"object\",\n";
    $annotation .= " *     title=\"{$modelName}\",\n";
    $annotation .= " *     description=\"Modelo de {$modelName}\",\n";

    foreach ($schemaData['properties'] as $name => $prop) {
      $annotation .= " *     @OA\\Property(\n";
      $annotation .= " *         property=\"{$name}\",\n";
      $annotation .= " *         type=\"{$prop['type']}\",\n";

      if ($prop['format']) {
        $annotation .= " *         format=\"{$prop['format']}\",\n";
      }

      if ($prop['example'] !== null) {
        $example = is_string($prop['example']) ? "\"{$prop['example']}\"" : json_encode($prop['example']);
        $annotation .= " *         example={$example},\n";
      }

      $annotation .= " *         description=\"{$prop['description']}\"\n";
      $annotation .= " *     ),\n";
    }

    $annotation .= " * )\n */";
    return $annotation;
  }

  private function generateAllControllerDocs()
  {
    $controllersPath = app_path('Http/Controllers');
    $controllers = glob($controllersPath . '/*.php');

    foreach ($controllers as $controllerPath) {
      $controllerName = basename($controllerPath, '.php');
      $controllerClass = "App\\Http\\Controllers\\{$controllerName}";

      if (class_exists($controllerClass)) {
        $this->generateControllerDoc($controllerClass, $controllerName);
      }
    }
  }

  private function generateControllerDoc($controllerClass, $controllerName)
  {
    $reflection = new \ReflectionClass($controllerClass);

    // Verificar si usa el trait de documentación automática
    if (in_array('App\Traits\AutoSwaggerDocumentation', $reflection->getTraitNames())) {
      $docData = $controllerClass::getSwaggerDocumentation();
      $content = $this->buildControllerAnnotations($docData, $controllerName);

      file_put_contents(app_path("Http/Swagger/Controllers/{$controllerName}.php"), $content);
      $this->line("  ✓ Documentación generada: {$controllerName}");
    }
  }

  private function buildControllerAnnotations($docData, $controllerName)
  {
    $content = "<?php\n\n/**\n * Documentación automática para {$controllerName}\n */\n\n";

    foreach ($docData['routes'] as $method => $route) {
      $content .= $this->buildRouteAnnotation($route, $docData['tag'], $method);
      $content .= "\n\n";
    }

    return $content;
  }

  private function buildRouteAnnotation($route, $tag, $method)
  {
    $httpMethod = strtoupper($route['method']);

    $annotation = "/**\n";
    $annotation .= " * @OA\\{$httpMethod}(\n";
    $annotation .= " *     path=\"{$route['path']}\",\n";
    $annotation .= " *     summary=\"{$route['summary']}\",\n";
    $annotation .= " *     description=\"{$route['description']}\",\n";
    $annotation .= " *     tags={\"{$tag}\"},\n";
    $annotation .= " *     security={{\"bearerAuth\": {}}},\n";

    // Parámetros
    if (isset($route['parameters'])) {
      foreach ($route['parameters'] as $param) {
        $annotation .= " *     @OA\\Parameter(\n";
        $annotation .= " *         name=\"{$param['name']}\",\n";
        $annotation .= " *         in=\"{$param['in']}\",\n";
        $annotation .= " *         required=" . ($param['required'] ? 'true' : 'false') . ",\n";
        $annotation .= " *         @OA\\Schema(type=\"{$param['type']}\")\n";
        $annotation .= " *     ),\n";
      }
    }

    // Request Body
    if (isset($route['requestBody']) && $route['requestBody']) {
      $annotation .= " *     @OA\\RequestBody(\n";
      $annotation .= " *         required=true,\n";
      $annotation .= " *         @OA\\JsonContent(\n";
      $annotation .= " *             @OA\\Property(property=\"ejemplo\", type=\"string\", example=\"valor\")\n";
      $annotation .= " *         )\n";
      $annotation .= " *     ),\n";
    }

    // Responses
    foreach ($route['responses'] as $code => $description) {
      $annotation .= " *     @OA\\Response(\n";
      $annotation .= " *         response=\"{$code}\",\n";
      $annotation .= " *         description=\"{$description}\"\n";
      $annotation .= " *     ),\n";
    }

    $annotation .= " * )\n */";

    return $annotation;
  }

  private function generateStandardResponses()
  {
    $content = '<?php

            /**
             * @OA\Schema(
             *     schema="ApiResponse",
             *     type="object",
             *     description="Respuesta estándar de la API",
             *     @OA\Property(property="status", type="string", enum={"SUCCESS", "ERROR"}, example="SUCCESS"),
             *     @OA\Property(property="message", type="string", example="Operación exitosa"),
             *     @OA\Property(property="data", type="object", description="Datos de respuesta"),
             *     @OA\Property(property="errors", type="array", @OA\Items(type="string"), description="Lista de errores si los hay")
             * )
             *
             * @OA\Schema(
             *     schema="PaginatedResponse",
             *     allOf={
             *         @OA\Schema(ref="#/components/schemas/ApiResponse"),
             *         @OA\Schema(
             *             @OA\Property(
             *                 property="meta",
             *                 type="object",
             *                 @OA\Property(property="current_page", type="integer", example=1),
             *                 @OA\Property(property="from", type="integer", example=1),
             *                 @OA\Property(property="last_page", type="integer", example=5),
             *                 @OA\Property(property="per_page", type="integer", example=15),
             *                 @OA\Property(property="to", type="integer", example=15),
             *                 @OA\Property(property="total", type="integer", example=70)
             *             )
             *         )
             *     }
             * )
             *
             * @OA\Schema(
             *     schema="ValidationError",
             *     type="object",
             *     @OA\Property(property="message", type="string", example="Los datos proporcionados no son válidos."),
             *     @OA\Property(
             *         property="errors",
             *         type="object",
             *         @OA\Property(
             *             property="email",
             *             type="array",
             *             @OA\Items(type="string", example="El campo email es obligatorio.")
             *         )
             *     )
             * )
             */';

    file_put_contents(app_path('Http/Swagger/Responses.php'), $content);
    $this->line("  ✓ Respuestas estándar generadas");
  }
}
