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
                // Generar preview
                $preview = $this->generarPreview($data, $audit->proveedor_id);
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

    private function generarPreview($data, $proveedorId)
    {
        $preview = [
            'productos' => ['nuevos' => [], 'actualizados' => []],
            'marcas' => ['nuevas' => [], 'existentes' => []],
            'lineas' => ['nuevas' => [], 'existentes' => []]
        ];

        $marcasExistentes = Marca::pluck('nombre', 'id')->toArray();
        $skusExistentes = Producto::where('proveedor_id', $proveedorId)
            ->pluck('id', 'sku')->toArray();

        foreach (array_slice($data, 0, 100) as $row) { // Preview de primeros 100
            // Verificar marca
            if (!in_array($row['nombre_marca'], $marcasExistentes)) {
                $preview['marcas']['nuevas'][] = $row['nombre_marca'];
            }

            // Verificar producto
            if (isset($skusExistentes[$row['sku']])) {
                $preview['productos']['actualizados'][] = [
                    'sku' => $row['sku'],
                    'nombre' => $row['nombre_producto'],
                    'precio' => $row['precio']
                ];
            } else {
                $preview['productos']['nuevos'][] = [
                    'sku' => $row['sku'],
                    'nombre' => $row['nombre_producto'],
                    'precio' => $row['precio']
                ];
            }
        }

        return $preview;
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

                    $marca = Marca::firstOrCreate(['nombre' => $row['nombre_marca']]);
                    $linea = Linea::firstOrCreate([
                        'nombre' => $row['nombre_linea'],
                        'marca_id' => $marca->id
                    ]);

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
                            'linea_id' => $linea->id
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
                            'linea_id' => $linea->id,
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
