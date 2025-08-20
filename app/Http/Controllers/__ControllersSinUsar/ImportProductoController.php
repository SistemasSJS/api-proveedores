1<?php

    namespace App\Http\Controllers;

    use App\Http\Requests\ProductoImport\ProductoImportUploadRequest;
    use App\Jobs\ImportarProductosJob;
    use App\Models\Producto;
    use App\Models\Marca;
    use App\Models\Linea;
    use App\Models\Catalogo;
    use App\Models\Categoria;
    use App\Models\Proveedor;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Storage;



    class ImportProductoController extends Controller
    {

        public function import(ProductoImportUploadRequest $request, Proveedor $proveedor)
        {
            $request->validated([
                'file' => 'required|file|mimes:csv,xlsx|max:10240'
            ]);

            $path = $request->file('file')->store('imports');

            ImportarProductosJob::dispatch($proveedor->id, $path);

            return response()->json([
                'message' => 'Importación iniciada',
                'job_id' => uniqid('import_')
            ]);

            // Para archivos grandes, usar job
            if ($request->file('file')->getSize() > 1048576) { // >1MB
                ImportarProductosJob::dispatch($proveedor->id, $path);

                return response()->json([
                    'message' => 'Importación iniciada',
                    'job_id' => uniqid('import_')
                ]);
            }

            // Procesamiento directo para archivos pequeños
            $result = $this->procesarArchivo($proveedor->id, $path);
            return $this->success([$result]);
        }

        private function procesarArchivo($proveedorId, $path)
        {
            $data = array_map('str_getcsv', file(storage_path("app/$path")));
            $headers = array_shift($data);

            $errores = [];
            $exitosos = 0;

            DB::transaction(function () use ($data, $headers, $proveedorId, &$errores, &$exitosos) {
                foreach ($data as $index => $row) {
                    try {
                        $producto = array_combine($headers, $row);

                        // Crear marca/línea si no existe
                        $marca = Marca::firstOrCreate([
                            'nombre' => $producto['nombre_marca'],
                            'proveedor_id' => $proveedorId
                        ]);

                        //
                        $linea = Linea::firstOrCreate([
                            'nombre' => $producto['nombre_linea'],
                            'marca_id' => $marca->id,
                            'proveedor_id' => $proveedorId
                        ]);

                        Producto::updateOrCreate(
                            [
                                'sku' => $producto['sku'],
                                'proveedor_id' => $proveedorId
                            ],
                            [
                                'nombre' => $producto['nombre_producto'],
                                'descripcion' => $producto['descripcion'],
                                'precio_base' => $producto['precio_base'],
                                'stock' => $producto['cantidad_disponible'],
                                'activo' => $producto['activo'] === 'true',
                                'marca_id' => $marca->id,
                                'linea_id' => $linea->id
                            ]
                        );

                        $exitosos++;
                    } catch (\Exception $e) {
                        $errores[] = [
                            'fila' => $index + 2,
                            'sku' => $producto['sku'] ?? 'N/A',
                            'error' => $e->getMessage(),
                        ];
                    }
                }
            });

            return [
                'total' => count($data),
                'exitosos' => $exitosos,
                'errores' => $errores
            ];
        }

        public function import_v2(ProductoImportUploadRequest $request, Proveedor $proveedor)
        {
            $file = $request->file('file');
            $handle = fopen($file->getRealPath(), 'r');
            $header = fgetcsv($handle);

            DB::beginTransaction();

            try {
                while (($data = fgetcsv($handle)) !== false) {
                    $row = array_combine($header, $data);

                    $marca = Marca::firstOrCreate(['nombre' => $row['nombre_marca'],  'proveedor_id' => $proveedor->id,]);
                    $linea = Linea::firstOrCreate(['nombre' => $row['nombre_linea'],  'proveedor_id' => $proveedor->id,]);
                    // $proveedor = Proveedor::firstOrCreate(
                    //     ['nombre_comercial' => $row['proveedor_nombre']],
                    //     [
                    //         'nombre_comercial' => $row['proveedor_nombre'],
                    //         'razon_social' => $row['proveedor_nombre'],
                    //         'direccion' => $row['proveedor_direccion'],
                    //         'telefono' => $row['proveedor_telefono'],
                    //         'email' => $row['proveedor_email']
                    //     ]
                    // );
                    // $catalogo = Catalogo::firstOrCreate([
                    //     'nombre' => $row['nombre_catalogo'],
                    //     'proveedor_id' => $proveedor->id,
                    // ]);

                    $producto = Producto::updateOrCreate(
                        ['sku' => $row['sku']],
                        [
                            'nombre' => $row['nombre_producto'],
                            'descripcion' => $row['descripcion'],
                            'precio_base' => $row['precio_base'],
                            'cantidad_disponible' => $row['cantidad_disponible'],
                            'activo' => $row['activo'],
                            'proveedor_id' => $proveedor->id,
                            'marca_id' => $marca->id,
                            'linea_id' => $linea->id,
                        ]
                    );

                    // Relacionar categorías (separadas por "|")
                    $categorias = explode('|', $row['categorias']);
                    $categoriaIds = [];

                    foreach ($categorias as $catNombre) {
                        $categoria = Categoria::firstOrCreate(['nombre' => trim($catNombre),   'proveedor_id' => $proveedor->id,]);
                        $categoriaIds[] = $categoria->id;
                    }

                    $producto->categorias()->sync($categoriaIds);
                }

                DB::commit();
                return response()->json(['message' => 'Productos importados correctamente.']);
            } catch (\Throwable $e) {
                DB::rollBack();
                return response()->json(['error' => 'Error al importar productos', 'details' => $e->getMessage()], 500);
            }
        }
    }
