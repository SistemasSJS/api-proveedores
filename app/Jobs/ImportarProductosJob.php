<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use App\Models\Producto;
use App\Models\Marca;
use App\Models\Linea;
use App\Models\Categoria;
use App\Models\UnidadMedida;
use App\Models\ImportAudit;
use App\Models\Proveedor;
use App\Services\FileParserService;
use App\Services\ProductImportValidator;
use Illuminate\Support\Facades\Storage;
use Exception;

class ImportarProductosJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $auditId;
    protected $confirmado;
    private $startTime;
    private $memoryPeakUsage = 0;

    public function __construct($auditId, $confirmado = false)
    {
        $this->auditId = $auditId;
        $this->confirmado = $confirmado;
    }

    public function handle()
    {
        $this->startTime = microtime(true);

        $audit = ImportAudit::find($this->auditId);
        if (!$audit) return;

        $audit->update([
            'estado' => 'procesando',
            'inicio_proceso' => now()
        ]);

        try {
            if (!$this->confirmado) {
                // Phase 1-3: Parse, Validate, Preview
                $this->executePreviewPhases($audit);
            } else {
                // Phase 4: Execution
                $this->executeImportPhase($audit);
            }
        } catch (Exception $e) {
            $this->handleError($audit, $e);
            throw $e;
        }
    }

    /**
     * Execute phases 1-3: Parse, Validate, Preview
     */
    private function executePreviewPhases(ImportAudit $audit): void
    {
        // Phase 1: Parse (0-20%)
        $parsedData = $this->parsePhase($audit);

        // Phase 2: Validate (20-40%)
        $validationResults = $this->validatePhase($audit, $parsedData);

        // Phase 3: Preview (40-60%)
        $this->previewPhase($audit, $parsedData, $validationResults);
    }

    /**
     * Phase 1: Parse file (0-20%)
     */
    private function parsePhase(ImportAudit $audit): array
    {
        $audit->update(['fase' => 'parse', 'progreso' => 0]);
        $audit->refresh()->appendLog('Iniciando fase de parsing');

        try {
            $fileParserService = new FileParserService();
            $filePath = Storage::path($audit->archivo);

            $data = $fileParserService->parseFile($filePath);
            $rowCount = count($data);

            $audit->update([
                'total_registros' => $rowCount,
                'fase' => 'parse',
                'progreso' => 20
            ]);
            $audit->appendLog("Archivo parseado exitosamente. {$rowCount} filas encontradas");

            return $data;
        } catch (Exception $e) {
            $audit->appendLog("Error en parsing: {$e->getMessage()}");
            throw new Exception("Error de formato en archivo: {$e->getMessage()}");
        }
    }

    /**
     * Phase 2: Validate data (20-40%)
     */
    private function validatePhase(ImportAudit $audit, array $data): array
    {
        $audit->update(['fase' => 'validate', 'progreso' => 20]);
        $audit->appendLog('Iniciando fase de validación');

        $validator = new ProductImportValidator($audit->proveedor_id);
        $validationResults = [
            'errors' => [],
            'warnings' => [],
            'headers_validation' => []
        ];

        // Validate headers if data exists
        if (!empty($data)) {
            $headers = array_keys($data[0]);
            $validationResults['headers_validation'] = $validator->validateHeaders($headers);
        }

        // Validate each row
        $totalRows = count($data);
        foreach ($data as $index => $row) {
            $rowValidation = $validator->validateRow($row, $index + 1);

            if (!empty($rowValidation['errors'])) {
                $validationResults['errors'][] = [
                    'row' => $index + 1,
                    'sku' => $row['sku'] ?? 'N/A',
                    'errors' => $rowValidation['errors']
                ];
            }

            if (!empty($rowValidation['warnings'])) {
                $validationResults['warnings'][] = [
                    'row' => $index + 1,
                    'sku' => $row['sku'] ?? 'N/A',
                    'warnings' => $rowValidation['warnings']
                ];
            }

            // Update progress every 100 rows
            if ($index % 100 === 0) {
                $progress = 20 + ($index / $totalRows) * 20; // 20-40%
                $audit->update(['progreso' => $progress]);
            }
        }

        $errorCount = count($validationResults['errors']);
        $warningCount = count($validationResults['warnings']);

        $audit->update([
            'progreso' => 40,
            'errores' => $errorCount
        ]);
        $audit->appendLog("Validación completa. {$errorCount} errores, {$warningCount} advertencias");

        return $validationResults;
    }

    /**
     * Phase 3: Generate preview (40-60%)
     */
    private function previewPhase(ImportAudit $audit, array $data, array $validationResults): void
    {
        $audit->update(['fase' => 'preview', 'progreso' => 40]);
        $audit->appendLog('Generando vista previa');

        // $previewData = $this->buildPreviewData($data, $validationResults, $audit->proveedor_id);
        $previewData = $this->generarPreviewDetallado($data,  $audit->proveedor_id);

        $this->updateMemoryTracking();
        $eta = $this->calculateETA(count($data));

        $audit->update([
            'estado' => 'preview',
            'fase' => 'preview',
            'progreso' => 60,
            'preview_data' => $previewData,
            'errores_detalle' => $validationResults,
            'eta_seconds' => $eta,
            'mem_peak_mb' => $this->memoryPeakUsage
        ]);
        $audit->appendLog('Vista previa generada. Esperando confirmación del usuario.');
    }

    /**
     * Phase 4: Execute import (60-100%)
     */
    private function executeImportPhase(ImportAudit $audit): void
    {
        $audit->update(['fase' => 'execute', 'progreso' => 60]);
        $audit->appendLog('Iniciando ejecución de importación confirmada');

        // Re-parse data from file
        $fileParserService = new FileParserService();
        $filePath = Storage::path($audit->archivo);
        $data = $fileParserService->parseFile($filePath);

        $resultado = $this->processImportWithTransaction($data, $audit);

        $this->updateMemoryTracking();

        $audit->update([
            'estado' => 'completado',
            'fase' => 'execute',
            'progreso' => 100,
            'fin_proceso' => now(),
            'nuevos' => $resultado['nuevos'],
            'actualizados' => $resultado['actualizados'],
            'errores' => $resultado['errores'],
            'errores_detalle' => $resultado['errores_detalle'],
            'mem_peak_mb' => $this->memoryPeakUsage
        ]);
        $audit->appendLog("Importación completada. {$resultado['nuevos']} nuevos, {$resultado['actualizados']} actualizados, {$resultado['errores']} errores");
    }

    /**
     * Process import with database transaction and rollback capability
     */
    private function processImportWithTransaction(array $data, ImportAudit $audit): array
    {
        $nuevos = 0;
        $actualizados = 0;
        $errores = 0;
        $errores_detalle = [];

        try {
            DB::transaction(function () use ($data, $audit, &$nuevos, &$actualizados, &$errores, &$errores_detalle) {
                // Create savepoint for rollback capability
                DB::statement('SAVEPOINT import_savepoint');

                try {
                    $this->processDataInChunks($data, $audit, $nuevos, $actualizados, $errores, $errores_detalle);
                } catch (Exception $e) {
                    // Mark rollback phase and restore savepoint
                    $audit->update(['fase' => 'rollback']);
                    $audit->appendLog("Error durante ejecución: {$e->getMessage()}. Iniciando rollback.");

                    DB::statement('ROLLBACK TO SAVEPOINT import_savepoint');

                    $audit->appendLog('Rollback completado');
                    throw $e;
                }
            });
        } catch (Exception $e) {
            $errores_detalle[] = [
                'error' => 'Error de transacción',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ];
            $errores++;
        }

        return compact('nuevos', 'actualizados', 'errores', 'errores_detalle');
    }

    /**
     * Process data in chunks to control memory usage
     */
    private function processDataInChunks(array $data, ImportAudit $audit, &$nuevos, &$actualizados, &$errores, &$errores_detalle): void
    {
        $totalRows = count($data);
        $chunkSize = 100; // Process 100 rows at a time

        $chunks = array_chunk($data, $chunkSize, true);

        foreach ($chunks as $chunkIndex => $chunk) {
            $this->processChunk($chunk, $audit, $nuevos, $actualizados, $errores, $errores_detalle);

            // Update progress chunk-wise
            $processedRows = ($chunkIndex + 1) * $chunkSize;
            $processedRows = min($processedRows, $totalRows);
            $progress = 60 + ($processedRows / $totalRows) * 40; // 60-100%

            $audit->update(['progreso' => $progress]);

            // Update memory tracking
            $this->updateMemoryTracking();

            // Log progress every 10 chunks
            if ($chunkIndex % 10 === 0) {
                $audit->appendLog("Procesados {$processedRows}/{$totalRows} registros");
            }
        }
    }

    /**
     * Process a chunk of data
     */
    private function processChunk(array $chunk, ImportAudit $audit, &$nuevos, &$actualizados, &$errores, &$errores_detalle): void
    {
        foreach ($chunk as $index => $row) {
            try {
                $resultado = $this->processRow($row, $audit->proveedor_id);

                if ($resultado['isNew']) {
                    $nuevos++;
                } else {
                    $actualizados++;
                }
            } catch (Exception $e) {
                $errores++;
                $errores_detalle[] = [
                    'fila' => $index + 1,
                    'sku' => $row['sku'] ?? 'N/A',
                    'error' => $e->getMessage(),
                    'code' => $e->getCode()
                ];
            }
        }
    }

    /**
     * Process a single row with 3-level category nesting and entity upserts
     */
    private function processRow(array $row, int $proveedorId): array
    {
        // Upsert Marca
        $marca = null;
        $marcaNombre = trim($row['nombre_marca'] ?? '');
        if ($marcaNombre) {
            $marca = Marca::firstOrCreate([
                'nombre' => $marcaNombre,
                'proveedor_id' => $proveedorId
            ]);
        }

        // Upsert Linea
        $linea = null;
        $lineaNombre = trim($row['nombre_linea'] ?? '');
        if ($lineaNombre && $marca) {
            $linea = Linea::firstOrCreate([
                'nombre' => $lineaNombre,
                'marca_id' => $marca->id,
                'proveedor_id' => $proveedorId
            ]);
        }

        // Upsert 3-level category nesting
        $categoriaFinal = $this->upsertCategoriesWithNesting($row, $proveedorId);

        // Upsert UnidadMedida
        $unidadMedida = null;
        $unidadNombre = trim($row['unidad_medida'] ?? '');
        if ($unidadNombre) {
            $unidadMedida = UnidadMedida::firstOrCreate([
                'descripcion' => $unidadNombre
            ]);
        }

        // Record before state
        $existingProduct = Producto::where('sku', $row['sku'])
            ->where('proveedor_id', $proveedorId)
            ->first();

        $beforeState = $existingProduct ? $existingProduct->toArray() : null;
        $isNew = !$existingProduct;

        // Upsert Producto
        $producto = Producto::updateOrCreate(
            [
                'sku' => $row['sku'],
                'proveedor_id' => $proveedorId
            ],
            [
                'modelo' => $row['nombre_modelo'] ?? null,
                'codigo_interno' => $row['codigo_interno'] ?? null,
                'nombre' => $row['nombre_producto'],
                'descripcion' => $row['descripcion_producto'] ?? null,
                'marca_id' => $marca?->id,
                'linea_id' => $linea?->id,
                'categoria_id' => $categoriaFinal?->id,
                'unidad_medida_id' => $unidadMedida?->id,
                'precio_base' => $this->parseDecimal($row['precio_base'] ?? null),
                'precio_de_lista' => $this->parseDecimal($row['precio_de_lista'] ?? null),
                'precio_publico' => $this->parseDecimal($row['precio_publico'] ?? null),
                'precio_mayoreo' => $this->parseDecimal($row['precio_mayoreo'] ?? null),
                'precio_con_IVA' => $this->parseDecimal($row['precio_con_IVA'] ?? null),
                'precio_sin_IVA' => $this->parseDecimal($row['precio_sin_IVA'] ?? null),
                'precio_promocional' => $this->parseDecimal($row['precio_promocional'] ?? null),
                'precio_distribuidor' => $this->parseDecimal($row['precio_distribuidor'] ?? null),
                'precio_especial' => $this->parseDecimal($row['precio_especial'] ?? null)
            ]
        );

        // Record after state for diff
        $afterState = $producto->fresh()->toArray();

        return [
            'isNew' => $isNew,
            'before' => $beforeState,
            'after' => $afterState,
            'diff' => $this->calculateDiff($beforeState, $afterState)
        ];
    }

    /**
     * Upsert categories with 3-level nesting
     */
    private function upsertCategoriesWithNesting(array $row, int $proveedorId): ?Categoria
    {
        $categoria1 = trim($row['nombre_categoria_nivel_1'] ?? '');
        $categoria2 = trim($row['nombre_categoria_nivel_2'] ?? '');
        $categoria3 = trim($row['nombre_categoria_nivel_3'] ?? '');

        if (!$categoria1) {
            return null;
        }

        // Level 1 category
        $cat1 = Categoria::firstOrCreate([
            'nombre' => $categoria1,
            'proveedor_id' => $proveedorId,
            'parent_id' => null
        ]);

        if (!$categoria2) {
            return $cat1;
        }

        // Level 2 category
        $cat2 = Categoria::firstOrCreate([
            'nombre' => $categoria2,
            'proveedor_id' => $proveedorId,
            'parent_id' => $cat1->id
        ]);

        if (!$categoria3) {
            return $cat2;
        }

        // Level 3 category
        $cat3 = Categoria::firstOrCreate([
            'nombre' => $categoria3,
            'proveedor_id' => $proveedorId,
            'parent_id' => $cat2->id
        ]);

        return $cat3;
    }

    private function generarPreviewDetallado($data, $proveedorId)
    {
        $preview = [
            'productos' => [
                'nuevos' => [],
                'actualizados' => [],
                'errores' => []
            ],
            'marcas' => [
                'nuevas' => [],
                'existentes' => []
            ],
            'lineas' => [
                'nuevas' => [],
                'existentes' => []
            ],
            'categorias' => [
                'nuevas' => [],
                'existentes' => []
            ]
        ];

        // Cargar datos existentes
        // $proveedor = Proveedor::with(Proveedor::eagerLodable());
        // $categoriasExistentes = $proveedor->categorias()
        //     ->pluck('nombre', 'id')->toArray();
        // $marcasExistentes = $proveedor->marcas()
        //     ->with('lineas')
        //     ->get()
        //     ->keyBy('nombre');
        // $productosExistentes = $proveedor->productos()
        //     ->with(['marca', 'linea'])
        //     ->get()
        //     ->keyBy('sku');

        // $lineas = $proveedor->lineas();
        // $sucursales = $proveedor->sucursales();

        $marcasExistentes = Marca::with('lineas')
            ->where('proveedor_id', $proveedorId)
            ->get()
            ->keyBy('nombre');
        $categoriasExistentes = Categoria::with('children')
            ->where('proveedor_id', $proveedorId)
            ->get()
            ->keyBy('nombre');
        $productosExistentes = Producto::where('proveedor_id', $proveedorId)
            ->with(['marca', 'linea'])
            ->get()
            ->keyBy('sku');

        foreach ($data as $index => $row) {
            // Procesar marcas y líneas
            $marcaNombre = trim($row['nombre_marca'] ?? '');
            $lineaNombre = trim($row['nombre_linea'] ?? '');

            if ($marcaNombre) {
                if (!$marcasExistentes->has($marcaNombre)) {
                    if (!isset($preview['marcas']['nuevas'][$marcaNombre])) {
                        $preview['marcas']['nuevas'][$marcaNombre] = [
                            'nombre' => $marcaNombre,
                            'lineas' => []
                        ];
                    }
                    if ($lineaNombre) {
                        $preview['marcas']['nuevas'][$marcaNombre]['lineas'][] = $lineaNombre;
                        $preview['lineas']['nuevas'][$lineaNombre] = [
                            'nombre' => $lineaNombre,
                            'marca' => $marcaNombre
                        ];
                    }
                } else {
                    $marca = $marcasExistentes[$marcaNombre];
                    if ($lineaNombre && !$marca->lineas->contains('nombre', $lineaNombre)) {
                        if (!isset($preview['lineas']['nuevas'][$lineaNombre])) {
                            $preview['lineas']['nuevas'][$lineaNombre] = [
                                'nombre' => $lineaNombre,
                                'marca' => $marcaNombre
                            ];
                        }
                    }
                }
            }
            $cat_nivel_1 = trim($row['nombre_categoria_nivel_1'] ?? '');
            $cat_nivel_2 = trim($row['nombre_categoria_nivel_2'] ?? '');
            $cat_nivel_3 = trim($row['nombre_categoria_nivel_3'] ?? '');

            if ($cat_nivel_1) {
                if ($categoriasExistentes->has($cat_nivel_1)) {
                    if (!isset($preview['categorias']['nuevas'][$cat_nivel_1])) {
                        $preview['categorias']['nuevas'][$cat_nivel_1] = [
                            'nombre' => $cat_nivel_1,
                            'subcategorias' => [
                                $cat_nivel_2,
                                $cat_nivel_3
                            ]
                        ];
                    }
                }
            }

            if ($cat_nivel_2) {
                if ($categoriasExistentes->has($cat_nivel_2)) {
                    if (!isset($preview['categorias']['nuevas'][$cat_nivel_2])) {
                        $preview['categorias']['nuevas'][$cat_nivel_2] = [
                            'nombre' => $cat_nivel_2,
                            'subcategorias' => [
                                $cat_nivel_3
                            ]
                        ];
                    }
                }
            }

            if ($cat_nivel_3) {
                if ($categoriasExistentes->has($cat_nivel_3)) {
                    if (!isset($preview['categorias']['nuevas'][$cat_nivel_3])) {
                        $preview['categorias']['nuevas'][$cat_nivel_3] = [
                            'nombre' => $cat_nivel_3
                        ];
                    }
                }
            }

            $productoData = [
                'fila' => $index + 2,
                'sku' => $row['sku'] ?? '',
                'nombre_modelo' => $row['nombre_modelo'] ?? '',
                'codigo_interno' => $row['codigo_interno'] ?? '',
                'nombre_producto' => $row['nombre_producto'] ?? '',
                'descripcion_producto' => $row['descripcion_producto'] ?? '',
                'nombre_marca' => $row['nombre_marca'] ?? '',
                'nombre_linea' => $row['nombre_linea'] ?? '',
                'nombre_categoria_nivel_1' => $row['nombre_categoria_nivel_1'] ?? '',
                'nombre_categoria_nivel_2' => $row['nombre_categoria_nivel_2'] ?? '',
                'nombre_categoria_nivel_3' => $row['nombre_categoria_nivel_3'] ?? '',
                'precio_base' => $row['precio_base'] ?? '',
                'precio_de_lista' => $row['precio_de_lista'] ?? '',
                'precio_publico' => $row['precio_publico'] ?? '',
                'precio_mayoreo' => $row['precio_mayoreo'] ?? '',
                'precio_con_IVA' => $row['precio_con_IVA'] ?? '',
                'precio_sin_IVA' => $row['precio_sin_IVA'] ?? '',
                'precio_promocional' => $row['precio_promocional'] ?? '',
                'precio_distribuidor' => $row['precio_distribuidor'] ?? '',
                'precio_especial' => $row['precio_especial'] ?? '',
            ];
            $sku = $row['sku'];
            if ($productosExistentes->has($sku)) {
                $preview['productos']['actualizados'][] = $productoData;
            } else {
                $preview['productos']['nuevos'][] = $productoData;
            }
        }


        // Convertir arrays asociativos a arrays indexados
        $preview['marcas']['nuevas'] = array_values($preview['marcas']['nuevas']);
        $preview['lineas']['nuevas'] = array_values($preview['lineas']['nuevas']);
        $preview['categorias']['nuevas'] = array_values($preview['categorias']['nuevas']);

        return $preview;
    }

    private function detectarCambios($productoExistente, $productoNuevo)
    {
        $cambios = [];

        if ($productoExistente->nombre != $productoNuevo['nombre']) {
            $cambios['nombre'] = [
                'anterior' => $productoExistente->nombre,
                'nuevo' => $productoNuevo['nombre']
            ];
        }

        if ($productoExistente->precio != $productoNuevo['precio']) {
            $cambios['precio'] = [
                'anterior' => $productoExistente->precio,
                'nuevo' => $productoNuevo['precio']
            ];
        }

        if ($productoExistente->stock != $productoNuevo['stock']) {
            $cambios['stock'] = [
                'anterior' => $productoExistente->stock,
                'nuevo' => $productoNuevo['stock']
            ];
        }

        return $cambios;
    }

    private function procesarImportacion($data, $audit)
    {
        $nuevos = 0;
        $actualizados = 0;
        $errores = 0;
        $errores_detalle = [];

        DB::transaction(function () use ($data, $audit, &$nuevos, &$actualizados, &$errores, &$errores_detalle) {
            $total = count($data);

            // Pre-cargar marcas, líneas y productos existentes
            $marcasExistentes = Marca::where('proveedor_id', $audit->proveedor_id)
                ->get()
                ->keyBy('nombre');

            $lineasExistentes = Linea::where('proveedor_id', $audit->proveedor_id)
                ->get()
                ->groupBy('marca_id');

            $productosExistentes = Producto::where('proveedor_id', $audit->proveedor_id)
                ->get()
                ->keyBy('sku');

            foreach ($data as $index => $row) {
                try {
                    // Validar SKU obligatorio
                    $sku = trim($row['sku'] ?? '');
                    // if (empty($sku)) {
                    //     throw new \Exception('El campo SKU es obligatorio');
                    // }

                    // Actualizar progreso cada 1% de avance
                    if (($index + 1) % max(1, floor($total / 100)) == 0) {
                        $audit->update(['progreso' => ($index / $total) * 100]);
                    }

                    // Procesar marca
                    $marca = null;
                    $nombreMarca = trim($row['nombre_marca'] ?? '');
                    if (!empty($nombreMarca)) {
                        if ($marcasExistentes->has($nombreMarca)) {
                            $marca = $marcasExistentes[$nombreMarca];
                        } else {
                            $marca = Marca::create([
                                'nombre' => $nombreMarca,
                                'proveedor_id' => $audit->proveedor_id
                            ]);
                            $marcasExistentes[$nombreMarca] = $marca;
                        }
                    }

                    // Procesar línea
                    $linea = null;
                    $nombreLinea = trim($row['nombre_linea'] ?? '');
                    if (!empty($nombreLinea) && $marca) {
                        $lineasMarca = $lineasExistentes->get($marca->id, collect());

                        $lineaExistente = $lineasMarca->firstWhere('nombre', $nombreLinea);

                        if ($lineaExistente) {
                            $linea = $lineaExistente;
                        } else {
                            $linea = Linea::create([
                                'nombre' => $nombreLinea,
                                'marca_id' => $marca->id,
                                'proveedor_id' => $audit->proveedor_id
                            ]);
                            // Actualizar cache local
                            $lineasExistentes[$marca->id][] = $linea;
                        }
                    }

                    // Procesar producto
                    if ($productosExistentes->has($sku)) {
                        $producto = $productosExistentes[$sku];
                        $producto->update([
                            'nombre' => $row['nombre_producto'],
                            'descripcion' => $row['descripcion'],
                            'precio' => $row['precio'],
                            'stock' => $row['cantidad_disponible'],
                            'activo' => filter_var($row['activo'], FILTER_VALIDATE_BOOLEAN),
                            'marca_id' => $marca ? $marca->id : null,
                            'linea_id' => $linea ? $linea->id : null
                        ]);
                        $actualizados++;
                    } else {
                        $producto = Producto::create([
                            'sku' => $sku,
                            'nombre' => $row['nombre_producto'],
                            'descripcion' => $row['descripcion'],
                            'precio' => $row['precio'],
                            'stock' => $row['cantidad_disponible'],
                            'activo' => filter_var($row['activo'], FILTER_VALIDATE_BOOLEAN),
                            'marca_id' => $marca ? $marca->id : null,
                            'linea_id' => $linea ? $linea->id : null,
                            'proveedor_id' => $audit->proveedor_id
                        ]);
                        // Actualizar cache local
                        $productosExistentes[$sku] = $producto;
                        $nuevos++;
                    }
                } catch (\Exception $e) {
                    $errores++;
                    $errores_detalle[] = [
                        'fila' => $index + 2, // +2 porque la primera fila es cabecera
                        'sku' => $row['sku'] ?? 'N/A',
                        'error' => $e->getMessage(),
                        'extra' => [
                            'code' => $e->getCode(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                            'trace' => $e->getTraceAsString(),
                        ]
                    ];
                }
            }
        });

        return compact('nuevos', 'actualizados', 'errores', 'errores_detalle');
    }


    /**
     * Build structured preview data with per-field validation
     */
    private function buildPreviewData(array $data, array $validationResults, int $proveedorId): array
    {
        $preview = [
            'summary' => [
                'total_rows' => count($data),
                'errors' => count($validationResults['errors']),
                'warnings' => count($validationResults['warnings']),
                'can_proceed' => count($validationResults['errors']) === 0
            ],
            'validation_results' => $validationResults,
            'sample_data' => []
        ];

        // Show first 10 rows with field-level validation
        // $sampleSize = min(10, count($data));
        $validator = new ProductImportValidator($proveedorId);

        for ($i = 0; $i < count($data); $i++) { // recorriedo los datos por row
            $row = $data[$i];
            // $rowValidation = $validator->validateRow($row, $i + 1);

            $previewRow = [];
            foreach ($row as $field => $value) {
                // $fieldErrors = [];
                // $fieldWarnings = [];

                // // Extract field-specific errors/warnings from row validation
                // foreach ($rowValidation['errors'] as $error) {
                //     if (strpos($error, "'{$field}'") !== false || strpos($error, $field) !== false) {
                //         $fieldErrors[] = $error;
                //     }
                // }

                // foreach ($rowValidation['warnings'] as $warning) {
                //     if (strpos($warning, "'{$field}'") !== false || strpos($warning, $field) !== false) {
                //         $fieldWarnings[] = $warning;
                //     }
                // }
                // $previewRow[$field] = [
                //     'value' => $value,
                //     'errors' => $fieldErrors,
                //     'warnings' => $fieldWarnings
                // ];
                $previewRow[$field] = $value;
            }

            $previewRow['row_number'] = $i + 1;
            $preview['sample_data'][] = $previewRow;
        }

        return $preview;
    }

    /**
     * Parse decimal value safely
     */
    private function parseDecimal($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $cleanValue = preg_replace('/[^\d.,-]/', '', $value);
        $cleanValue = str_replace(',', '.', $cleanValue);

        return is_numeric($cleanValue) ? (float)$cleanValue : null;
    }

    /**
     * Calculate diff between before/after states
     */
    private function calculateDiff(?array $before, array $after): array
    {
        if (!$before) {
            return ['type' => 'created', 'changes' => []];
        }

        $changes = [];

        foreach ($after as $key => $newValue) {
            $oldValue = $before[$key] ?? null;

            if ($oldValue !== $newValue) {
                $changes[$key] = [
                    'from' => $oldValue,
                    'to' => $newValue
                ];
            }
        }

        return [
            'type' => 'updated',
            'changes' => $changes
        ];
    }

    /**
     * Update memory tracking
     */
    private function updateMemoryTracking(): void
    {
        $currentUsage = memory_get_peak_usage(true) / 1024 / 1024; // MB
        $this->memoryPeakUsage = max($this->memoryPeakUsage, $currentUsage);
    }

    /**
     * Calculate ETA based on rows processed and time elapsed
     */
    private function calculateETA(int $totalRows): int
    {
        $elapsedTime = microtime(true) - $this->startTime;

        if ($elapsedTime <= 0) {
            return 0;
        }

        // Estimate based on preview generation time
        $estimatedSecondsPerRow = $elapsedTime / max(1, $totalRows);
        $executionTimeEstimate = $estimatedSecondsPerRow * $totalRows * 2; // 2x factor for execution

        return (int)ceil($executionTimeEstimate);
    }

    /**
     * Handle error state
     */
    private function handleError(ImportAudit $audit, Exception $e): void
    {
        $this->updateMemoryTracking();

        $audit->update([
            'estado' => 'error',
            'fin_proceso' => now(),
            'errores_detalle' => [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString()
            ],
            // 'mem_peak_mb' => $this->memoryPeakUsage
        ]);
        $audit->appendLog("Error fatal: {$e->getMessage()}");
    }
}
