<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use App\Models\ImportAudit;
use App\Models\Producto;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Services\CSVProcessorService;
use App\Services\ProductImportValidator;
use App\Http\Responses\CsvUploadResponse;
use App\Http\Responses\CsvConfirmResponse;
use App\Http\Responses\CsvValidateProductResponse;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\UnidadMedida;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Exception;

class CsvImportController extends Controller
{
    use ApiResponse;

    protected CSVProcessorService $csvProcessor;

    public function __construct(CSVProcessorService $csvProcessor)
    {
        $this->csvProcessor = $csvProcessor;
    }

    /**
     * POST /api/proveedores/{id}/csv-import
     * Subida del archivo y análisis
     */
    public function upload(Request $request, $id)
    {
        try {
            // Validar el proveedor
            $proveedor = Proveedor::findOrFail($id);

            // Validar el archivo
            $request->validate([
                'file' => 'required|file|mimes:csv,txt|max:10240',
                'delimiter' => 'nullable|string',
                'encoding' => 'nullable|string|in:UTF-8,ISO-8859-1,Windows-1252',
                'has_header' => 'nullable|boolean',
                'preview_rows' => 'nullable|integer|min:-1|max:500',
            ], [
                'file.required' => 'El archivo es obligatorio.',
                'file.file' => 'Debe ser un archivo válido.',
                'file.mimes' => 'El archivo debe ser de tipo CSV o TXT.',
                'file.max' => 'El archivo no debe exceder los 10MB.',
            ]);

            $file = $request->file('file');
            $originalExtension = $file->getClientOriginalExtension();
            $mimeType = $file->getMimeType();

            // Configurar opciones de procesamiento
            $options = [
                'delimiter' => $this->getDelimiter($request->get('delimiter', 'comma')),
                'encoding' => $request->get('encoding', 'UTF-8'),
                'has_header' => $request->get('has_header', true),
                'preview_rows' => $request->get('preview_rows', -1),
                'strict_validation' => false,
                'auto_create_relations' => true
            ];

            // Procesar archivo CSV y generar preview
            $processingResult = $this->csvProcessor->processCSVPreview($file, $proveedor->id, $options);

            if (!$processingResult['success']) {
                return $this->error($processingResult['error'], 422);
            }

            // Guardar el archivo
            $filename = "csv_import_{$proveedor->id}_" . time() . '.' . $originalExtension;
            $path = $file->storeAs('imports', $filename, 'local');

            // Crear registro de auditoría
            $jobId = Str::uuid()->toString();
            $audit = ImportAudit::create([
                'job_id' => $jobId,
                'proveedor_id' => $proveedor->id,
                'tipo' => 'productos',
                'archivo' => $path,
                'formato' => 'csv',
                'estado' => 'preview',
                // 'fase' => 'analisis_completado',
                'preview_data' => [
                    'file_info' => $processingResult['file_info'],
                    'headers' => $processingResult['headers'],
                    'preview_data' => $processingResult['preview_data'],
                    'validation_summary' => $processingResult['validation_summary'],
                    'quality_metrics' => $processingResult['quality_metrics'],
                    'preview_token' => $processingResult['preview_token']
                ],
                'total_registros' => $processingResult['file_info']['total_rows'],
                'progreso' => 100
            ]);

            $audit->appendLog('Archivo CSV cargado y analizado correctamente', [
                'filename' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'total_rows' => $processingResult['file_info']['total_rows']
            ]);

            $response = CsvUploadResponse::fromProcessorResult($audit->id, $jobId, $processingResult);

            return $this->success($response->toArray(), 'Archivo cargado y analizado correctamente');
        } catch (Exception $e) {
            Log::error('Error en upload CSV', [
                'proveedor_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->error('Error al procesar el archivo: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/proveedores/{id}/csv-import/confirm
     * Confirmar la importación
     */
    public function confirm(Request $request, $id)
    {
        try {
            // Validar el proveedor
            $proveedor = Proveedor::findOrFail($id);

            // Validar datos de entrada
            $request->validate([
                'audit_id' => 'required|integer|exists:import_audits,id',
                'preview_token' => 'required|string',
                'import_options' => 'nullable|array',
                'import_options.skip_duplicates' => 'nullable|boolean',
                'import_options.update_existing' => 'nullable|boolean',
                'import_options.create_missing_relations' => 'nullable|boolean',
            ]);

            // Extraer valores en variables
            $auditId = $request->input('audit_id');
            $previewToken = $request->input('preview_token');
            $skipDuplicates = $request->input('import_options.skip_duplicates', false);
            $updateExisting = $request->input('import_options.update_existing', false);
            $createMissingRelations = $request->input('import_options.create_missing_relations', false);

            // Buscar el audit
            $audit = ImportAudit::where('id', $request->audit_id)
                ->where('proveedor_id', $proveedor->id)
                ->where('estado', 'preview')
                ->first();

            if (!$audit) {
                return $this->error('No se encontró la importación o ya fue procesada', 404);
            }

            // Verificar que tenemos el token de preview
            $previewData = $audit->preview_data;
            if (!$previewData || $previewData['preview_token'] !== $request->preview_token) {
                return $this->error('Token de preview inválido', 400);
            }

            // Configurar opciones de importación
            $importOptions = array_merge([
                'skip_duplicates' => $skipDuplicates,
                'update_existing' => $updateExisting,
                'create_missing_relations' => $createMissingRelations,
            ], $request->get('import_options', []));

            // Iniciar la importación
            $audit->update([
                'estado' => 'procesando',
                'inicio_proceso' => now()
            ]);

            $audit->appendLog('Importación confirmada e iniciada', [
                'options' => $importOptions,
                'preview_token' => $request->preview_token
            ]);

            // Ejecutar la importación de forma síncrona para este endpoint
            $importResult = $this->executeImport($audit, $importOptions);

            // Actualizar audit con resultados
            $audit->update([
                'estado' => $importResult['success'] ? 'completado' : 'error',
                'fin_proceso' => now(),
                'nuevos' => $importResult['stats']['created'] ?? 0,
                'actualizados' => $importResult['stats']['updated'] ?? 0,
                'errores' => $importResult['stats']['errors'] ?? 0,
                'errores_detalle' => $importResult['error_details'] ?? []
            ]);

            if ($importResult['success']) {
                $audit->appendLog('Importación completada exitosamente', $importResult['stats']);

                $response = CsvConfirmResponse::success($audit->id, $importResult['stats']);
                return $this->success($response->toArray(), 'Importación completada exitosamente');
            } else {
                $audit->appendLog('Importación falló', [
                    'error' => $importResult['error'],
                    'stats' => $importResult['stats']
                ], 'error');

                $response = CsvConfirmResponse::error($audit->id, $importResult['stats'], $importResult['error_details']);
                return $this->error(
                    'Error durante la importación: ' . $importResult['error'],
                    $response->toArray(),
                    500
                );
            }
        } catch (Exception $e) {
            Log::error('Error en confirm CSV import', [
                'proveedor_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->error('Error al confirmar la importación: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/proveedores/{id}/csv-import/validate-producto
     * Validar un producto específico
     */
    public function validateProducto(Request $request, $id)
    {
        try {
            // Validar el proveedor
            $proveedor = Proveedor::findOrFail($id);

            // Validar datos de entrada
            $request->validate([
                'producto'                  => 'required|array',
                'producto.codigo'           => 'required|string|max:255',
                'producto.producto'         => 'required|string|max:255',
                'producto.descripcion'      => 'nullable|string|max:1000',
                'producto.modelo'           => 'nullable|string|max:255',
                'producto.marca'            => 'nullable|string|max:255',
                'producto.categoria'        => 'nullable|string|max:255',
                'producto.subcategoria'     => 'nullable|string|max:255',
                'producto.unidad_medida'    => 'nullable|string|max:100',
                'producto.precio'           => 'nullable|numeric|min:0',
                'producto.precio_mayoreo'   => 'nullable|numeric|min:0',
                'producto.precio_menudeo'   => 'nullable|numeric|min:0',
                'strict_validation'         => 'nullable|boolean'
            ]);

            $productoData = $request->get('producto');
            $strictValidation = $request->get('strict_validation', false);

            // Crear validator para este proveedor
            $validator = new ProductImportValidator($proveedor->id);

            // Validar el producto usando el servicio de validación
            $validationResult = $validator->validateRow($productoData, 1);

            // Verificar si ya existe el producto
            $existingProduct = Producto::where('codigo_interno', $productoData['codigo'])
                ->where('proveedor_id', $proveedor->id)
                ->first();

            $isValid = empty($validationResult['errors']);

            $existingProductData = $existingProduct ? [
                'id' => $existingProduct->id,
                'nombre' => $existingProduct->nombre,
                'precio' => $existingProduct->precio,
                'updated_at' => $existingProduct->updated_at
            ] : null;

            $recommendedActions = $this->getRecommendedActions(['is_valid' => $isValid, 'errors' => $validationResult['errors']], $existingProduct);

            $response = CsvValidateProductResponse::fromValidation(
                $validationResult,
                !is_null($existingProduct),
                $existingProductData,
                $recommendedActions
            );

            if ($isValid) {
                return $this->success($response->toArray(), 'Producto validado correctamente');
            } else {
                return $this->success($response->toArray(), 'Producto no válido');
            }
        } catch (Exception $e) {
            Log::error('Error en validateProducto', [
                'proveedor_id' => $id,
                'error' => $e->getMessage(),
                'producto_data' => $request->get('producto', []),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->error('Error al validar el producto: ' . $e->getMessage(), 500);
        }
    }
    /**
     * Ejecutar la importación de productos desde el CSV
     */
    private function executeImport(ImportAudit $audit, array $options): array
    {
        $startTime = microtime(true);
        $stats = [
            'total_processed' => 0,
            'created' => 0,
            'updated' => 0,
            'errors' => 0,
            'skipped' => 0,
            'success_rate' => 0
        ];
        $errorDetails = [];

        try {
            // Obtener datos del cache usando el preview_token
            $previewToken = $audit->preview_data['preview_token'] ?? null;
            $cachedData = $this->csvProcessor->getCachedPreviewData($previewToken);

            if (!$cachedData) {
                throw new \Exception('Datos de preview expirados. Por favor, suba el archivo nuevamente.');
            }

            $productsData = $cachedData['full_data'];
            $proveedorId = $audit->proveedor_id;

            // --- 1. Extraer y registrar catálogos ---
            $catalogos = [
                'marcas' => collect($productsData)->pluck('marca')->filter()->unique()->values()->toArray(),
                'unidades' => collect($productsData)->pluck('unidad_medida')->filter()->unique()->values()->toArray(),
                'categorias' => []
            ];

            foreach ($productsData as $p) {
                if (!empty($p['categoria']) && !empty($p['subcategoria'])) {
                    $catalogos['categorias'][$p['categoria']][] = $p['subcategoria'];
                }
            }

            foreach ($catalogos['categorias'] as $cat => $subs) {
                $catalogos['categorias'][$cat] = array_values(array_unique($subs));
            }

            $this->registrarCatalogos($catalogos, $proveedorId);

            // --- 2. Preparar referencias en memoria ---
            $marcasMap = Marca::where('proveedor_id', $proveedorId)->pluck('id', 'nombre')->toArray();
            $unidadesMap = UnidadMedida::where('proveedor_id', $proveedorId)->pluck('id', 'nombre')->toArray();

            $categoriasMap = Categoria::where('proveedor_id', $proveedorId)
                ->get()
                ->groupBy('nivel')
                ->map(fn($items) => $items->keyBy('nombre'));

            foreach ($productsData as &$p) {
                $p['marca_id'] = $marcasMap[$p['marca']] ?? null;
                $p['unidad_medida_id'] = $unidadesMap[$p['unidad_medida']] ?? null;

                if (!empty($p['categoria'])) {
                    $cat = $categoriasMap[1][$p['categoria']] ?? null;
                    $sub = null;
                    if ($cat && !empty($p['subcategoria'])) {
                        $sub = $categoriasMap[2]->where('parent_id', $cat->id)->firstWhere('nombre', $p['subcategoria']);
                    }
                    $p['categoria_id'] = $cat->id ?? null;
                    $p['subcategoria_id'] = $sub->id ?? null;
                }
            }
            unset($p); // romper referencia

            // --- 3. Procesar productos por chunks ---
            $chunkSize = 100;
            $chunks = array_chunk($productsData, $chunkSize);

            foreach ($chunks as $chunkIndex => $chunk) {
                DB::beginTransaction();
                try {
                    $upsertData = [];
                    foreach ($chunk as $productData) {
                        $stats['total_processed']++;

                        // Validación
                        $validator = new ProductImportValidator($proveedorId);
                        $validationResult = $validator->validateRow($productData, $stats['total_processed']);
                        $isValid = empty($validationResult['errors']);

                        if (!$isValid && empty($options['skip_duplicates'])) {
                            $stats['errors']++;
                            $errorDetails[] = [
                                'producto' => $productData,
                                'errores' => $validationResult['errors'],
                                'tipo_error' => 'validacion'
                            ];
                            continue;
                        }

                        // Preparar datos para upsert
                        $upsertData[] = [
                            'codigo_interno' => $productData['codigo'],
                            'nombre' => $productData['producto'],
                            'descripcion' => $productData['descripcion'] ?? null,
                            'modelo' => $productData['modelo'] ?? null,
                            'marca_id' => $productData['marca_id'],
                            'categoria_id' => $productData['categoria_id'],
                            'subcategoria_id' => $productData['subcategoria_id'],
                            'unidad_medida_id' => $productData['unidad_medida_id'],
                            'precio_base' => $productData['precio'] ?? 0,
                            'precio_mayoreo' => $productData['precio_mayoreo'] ?? 0,
                            'precio_publico' => $productData['precio_menudeo'] ?? 0,
                            'proveedor_id' => $proveedorId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    if (!empty($upsertData)) {
                        Producto::upsert(
                            $upsertData,
                            ['codigo_interno', 'proveedor_id'],
                            [
                                'nombre',
                                'descripcion',
                                'modelo',
                                'marca_id',
                                'categoria_id',
                                'subcategoria_id',
                                'unidad_medida_id',
                                'precio_base',
                                'precio_mayoreo',
                                'precio_publico',
                                'updated_at'
                            ]
                        );

                        $stats['created'] += count($upsertData); // aproximado
                    }

                    DB::commit();

                    // Actualizar progreso
                    $progress = min(100, (($chunkIndex + 1) / count($chunks)) * 100);
                    $audit->update(['progreso' => $progress]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    $stats['errors'] += count($chunk);
                    $errorDetails[] = [
                        'error' => $e->getMessage(),
                        'tipo_error' => 'chunk'
                    ];
                }
            }

            // Calcular tasa de éxito
            if ($stats['total_processed'] > 0) {
                $stats['success_rate'] = round((($stats['created'] + $stats['updated']) / $stats['total_processed']) * 100, 2);
            }

            $processingTime = round(microtime(true) - $startTime, 2);

            return [
                'success' => true,
                'stats' => $stats,
                'processing_time' => $processingTime,
                'error_details' => $errorDetails
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'stats' => $stats,
                'error_details' => $errorDetails
            ];
        }
    }


    /**
     * Registra los catálogos de marcas, unidades y categorías/subcategorías en la base de datos.
     *
     * @param array $catalogos
     *     Estructura esperada:
     *     [
     *         'marcas' => ['Marca1', 'Marca2', ...],
     *         'unidades' => ['kg', 'm', 'l', ...],
     *         'categorias' => [
     *             'Categoria1' => ['Subcategoria1', 'Subcategoria2', ...],
     *             'Categoria2' => ['SubcategoriaA', 'SubcategoriaB', ...],
     *         ],
     *     ]
     * @param int $proveedorId ID del proveedor al que se asociarán los registros
     *
     * @return void
     */
    public function registrarCatalogos(array $catalogos, int $proveedorId): void
    {
        // --- Registrar marcas ---
        foreach ($catalogos['marcas'] as $nombreMarca) {
            Marca::updateOrCreate(
                ['nombre' => $nombreMarca, 'proveedor_id' => $proveedorId],
                ['activo' => true]
            );
        }

        // --- Registrar unidades de medida ---
        foreach ($catalogos['unidades'] as $nombreUnidad) {
            UnidadMedida::updateOrCreate(
                ['nombre' => $nombreUnidad, 'proveedor_id' => $proveedorId],
                ['estatus' => 'activo']
            );
        }

        // --- Registrar categorías y subcategorías ---
        foreach ($catalogos['categorias'] as $nombreCategoria => $subcategorias) {
            // Categoria principal
            $categoria = Categoria::updateOrCreate(
                ['nombre' => $nombreCategoria, 'proveedor_id' => $proveedorId, 'nivel' => 1],
                ['activo' => true]
            );

            // Subcategorías
            foreach ($subcategorias as $nombreSubcategoria) {
                Categoria::updateOrCreate(
                    ['nombre' => $nombreSubcategoria, 'parent_id' => $categoria->id, 'proveedor_id' => $proveedorId, 'nivel' => 2],
                    ['activo' => true]
                );
            }
        }
    }


    /**
     * Crear un nuevo producto
     */
    private function createProduct(array $productData, int $proveedorId, bool $createRelations = true): Producto
    {
        // Implementar lógica de creación de producto
        // Similar al ProveedorImportProductController pero adaptado para CSV

        $producto = Producto::create([
            'codigo_interno' => $productData['codigo'] ?? '',
            'nombre' => $productData['nombre'] ?? '',
            'descripcion' => $productData['descripcion'] ?? null,
            'modelo' => $productData['modelo'] ?? null,
            'precio' => $productData['precio'] ?? 0,
            'proveedor_id' => $proveedorId,
            // Agregar más campos según sea necesario
        ]);

        return $producto;
    }

    /**
     * Actualizar producto existente
     */
    private function updateProduct(Producto $producto, array $productData, int $proveedorId): void
    {
        $updateData = [
            'nombre' => $productData['nombre'] ?? $producto->nombre,
            'descripcion' => $productData['descripcion'] ?? $producto->descripcion,
            'modelo' => $productData['modelo'] ?? $producto->modelo,
            'precio' => $productData['precio'] ?? $producto->precio,
        ];

        $producto->update(array_filter($updateData));
    }

    /**
     * Obtener acciones recomendadas
     */
    private function getRecommendedActions(array $validationResult, $existingProduct = null): array
    {
        $actions = [];

        if (!$validationResult['is_valid']) {
            $actions[] = [
                'accion' => 'corregir_errores',
                'descripcion' => 'Corregir los errores de validación antes de proceder',
                'prioridad' => 'alta'
            ];
        }

        if ($existingProduct) {
            $actions[] = [
                'accion' => 'actualizar_existente',
                'descripcion' => 'Actualizar el producto existente con los nuevos datos',
                'prioridad' => 'media'
            ];
        }

        if (empty($actions)) {
            $actions[] = [
                'accion' => 'crear_producto',
                'descripcion' => 'El producto puede ser creado sin problemas',
                'prioridad' => 'baja'
            ];
        }

        return $actions;
    }

    /**
     * Convertir string delimiter a carácter
     * Maneja tanto valores string como caracteres directos
     */
    private function getDelimiter(string $delimiter): string
    {
        // Normalizar el valor de entrada
        $delimiter = trim($delimiter);

        // Mapeo de strings a caracteres
        $delimiterMap = [
            'comma' => ',',
            'semicolon' => ';',
            'tab' => "\t",
            'pipe' => '|',
            'colon' => ':'
        ];

        // Si es un string conocido, retornar el carácter
        if (array_key_exists($delimiter, $delimiterMap)) {
            return $delimiterMap[$delimiter];
        }

        // Si ya es un carácter directo, validarlo
        switch ($delimiter) {
            case ',':
            case ';':
            case "\t":
            case '|':
            case ':':
                return $delimiter;
            case 'tab': // fallback por si acaso
                return "\t";
            default:
                // Si no es reconocido, usar coma como default
                Log::warning('Delimitador no reconocido, usando coma como default', [
                    'delimiter_received' => $delimiter
                ]);
                return ',';
        }
    }
}
