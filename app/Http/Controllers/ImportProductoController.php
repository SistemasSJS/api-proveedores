<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportProducto\ImportProductoRequest;
use App\Models\Producto;
use App\Models\Marca;
use App\Models\Linea;
use App\Models\Catalogo;
use App\Models\Categoria;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;



/**
 * @OA\Tag(
 *     name="ProductosImport",
 *     description="Operaciones Import sobre Productos"
 * )
 */
class ImportProductoController extends Controller
{

    /**
     * @OA\Post(
     *     path="/api/import",
     *     tags={"ProductosImport"},
     *     summary="Importar productos desde un archivo CSV",
     *     description="Este endpoint permite importar productos desde un archivo CSV. El archivo debe contener información sobre marcas, líneas, catálogos, proveedores y categorías.",
     *     operationId="importarProductos",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"file"},
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary",
     *                     description="Archivo CSV con los datos de productos"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Productos importados correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Productos importados correctamente.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error al importar productos",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Error al importar productos"),
     *             @OA\Property(property="details", type="string", example="Descripción del error interno")
     *         )
     *     )
     * )
     */
    public function import(ImportProductoRequest $request, Proveedor $proveedor)
    {
        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);

        DB::beginTransaction();

        try {
            while (($data = fgetcsv($handle)) !== false) {
                $row = array_combine($header, $data);

                $marca = Marca::firstOrCreate(['nombre' => $row['nombre_marca']]);
                $linea = Linea::firstOrCreate(['nombre' => $row['nombre_linea']]);
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
                $catalogo = Catalogo::firstOrCreate([
                    'nombre' => $row['nombre_catalogo'],
                    'proveedor_id' => $proveedor->id,
                ]);

                $producto = Producto::updateOrCreate(
                    ['sku' => $row['sku']],
                    [
                        'nombre' => $row['nombre_producto'],
                        'descripcion' => $row['descripcion'],
                        'precio' => $row['precio'],
                        'cantidad_disponible' => $row['cantidad_disponible'],
                        'activo' => $row['activo'],
                        'catalogo_id' => $catalogo->id,
                        'marca_id' => $marca->id,
                        'linea_id' => $linea->id,
                    ]
                );

                // Relacionar categorías (separadas por "|")
                $categorias = explode('|', $row['categorias']);
                $categoriaIds = [];

                foreach ($categorias as $catNombre) {
                    $categoria = Categoria::firstOrCreate(['nombre' => trim($catNombre)]);
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
