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
use App\Models\ImportAudit;
use Illuminate\Support\Facades\Storage;

class ImportarProductosJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $auditId;
    protected $confirmado;

    public function __construct($auditId, $confirmado = false)
    {
        $this->auditId = $auditId;
        $this->confirmado = $confirmado;
    }

    public function handle()
    {
        $audit = ImportAudit::find($this->auditId);

        if (!$audit) return;

        $audit->update([
            'estado' => 'procesando',
            'inicio_proceso' => now()
        ]);

        try {
            $data = $this->parseCSV(Storage::path($audit->archivo));

            if (!$this->confirmado) {
                // Generar preview detallado
                $preview = $this->generarPreviewDetallado($data, $audit->proveedor_id);
                $audit->update([
                    'estado' => 'preview',
                    'preview_data' => $preview,
                    'total_registros' => count($data)
                ]);
                return;
            }

            // Procesar importación confirmada
            $resultado = $this->procesarImportacion($data, $audit);

            $audit->update([
                'estado' => 'completado',
                'fin_proceso' => now(),
                'nuevos' => $resultado['nuevos'],
                'actualizados' => $resultado['actualizados'],
                'errores' => $resultado['errores'],
                'errores_detalle' => $resultado['errores_detalle']
            ]);
        } catch (\Exception $e) {
            $audit->update([
                'estado' => 'error',
                'errores_detalle' => ['message' => $e->getMessage()]
            ]);
            throw $e;
        }
    }

    private function parseCSV($path)
    {
        $data = array_map('str_getcsv', file($path));
        $headers = array_shift($data);

        return array_map(function ($row) use ($headers) {
            return array_combine($headers, $row);
        }, $data);
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
        $marcasExistentes = Marca::with('lineas')->get()->keyBy('nombre');
        $categoriasExistentes = Categoria::pluck('nombre', 'id')->toArray();
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

            // Procesar categorías
            if (isset($row['categorias'])) {
                $categorias = array_map('trim', explode(',', $row['categorias']));
                foreach ($categorias as $cat) {
                    if (!in_array($cat, $categoriasExistentes)) {
                        $preview['categorias']['nuevas'][$cat] = ['nombre' => $cat];
                    }
                }
            }

            // Procesar productos
            $sku = trim($row['sku'] ?? '');
            if (!$sku) {
                $preview['productos']['errores'][] = [
                    'fila' => $index + 2,
                    'error' => 'SKU vacío',
                    'data' => $row
                ];
                continue;
            }

            // Validar marca obligatoria
            if (!$marcaNombre) {
                $preview['productos']['errores'][] = [
                    'fila' => $index + 2,
                    'error' => 'Marca es obligatoria',
                    'sku' => $sku,
                    'data' => $row
                ];
                continue;
            }

            $productoData = [
                'sku' => $sku,
                'nombre' => $row['nombre_producto'] ?? '',
                'descripcion' => $row['descripcion'] ?? '',
                'precio' => floatval($row['precio'] ?? 0),
                'stock' => intval($row['cantidad_disponible'] ?? 0),
                'marca' => $marcaNombre,
                'linea' => $lineaNombre,
                'activo' => filter_var($row['activo'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'fila' => $index + 2
            ];

            if ($productosExistentes->has($sku)) {
                $productoExistente = $productosExistentes[$sku];
                $productoData['cambios'] = $this->detectarCambios($productoExistente, $productoData);
                $productoData['id'] = $productoExistente->id;
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

            foreach ($data as $index => $row) {
                try {
                    // Actualizar progreso cada 10 registros
                    if ($index % 10 == 0) {
                        $audit->update(['progreso' => ($index / $total) * 100]);
                    }

                    // Validar que exista nombre de marca
                    if (empty(trim($row['nombre_marca'] ?? ''))) {
                        throw new \Exception('El campo nombre_marca es obligatorio');
                    }

                    $marca = Marca::firstOrCreate([
                        'nombre' => trim($row['nombre_marca'])
                    ]);

                    // Solo crear línea si existe nombre de línea
                    $linea = null;
                    if (!empty(trim($row['nombre_linea'] ?? ''))) {
                        $linea = Linea::firstOrCreate([
                            'nombre' => trim($row['nombre_linea']),
                            'marca_id' => $marca->id
                        ]);
                    }

                    $producto = Producto::where('sku', $row['sku'])
                        ->where('proveedor_id', $audit->proveedor_id)
                        ->first();

                    if ($producto) {
                        $producto->update([
                            'nombre' => $row['nombre_producto'],
                            'descripcion' => $row['descripcion'],
                            'precio' => $row['precio'],
                            'stock' => $row['cantidad_disponible'],
                            'activo' => filter_var($row['activo'], FILTER_VALIDATE_BOOLEAN),
                            'marca_id' => $marca->id,
                            'linea_id' => $linea ? $linea->id : null
                        ]);
                        $actualizados++;
                    } else {
                        Producto::create([
                            'sku' => $row['sku'],
                            'nombre' => $row['nombre_producto'],
                            'descripcion' => $row['descripcion'],
                            'precio' => $row['precio'],
                            'stock' => $row['cantidad_disponible'],
                            'activo' => filter_var($row['activo'], FILTER_VALIDATE_BOOLEAN),
                            'marca_id' => $marca->id,
                            'linea_id' => $linea ? $linea->id : null,
                            'proveedor_id' => $audit->proveedor_id
                        ]);
                        $nuevos++;
                    }
                } catch (\Exception $e) {
                    $errores++;
                    $errores_detalle[] = [
                        'fila' => $index + 2,
                        'sku' => $row['sku'] ?? 'N/A',
                        'error' => $e->getMessage()
                    ];
                }
            }
        });

        return compact('nuevos', 'actualizados', 'errores', 'errores_detalle');
    }
}
