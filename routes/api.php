<?php

// use Illuminate\Support\Facades\Route;
// use Illuminate\Http\Request;
// use App\Models\Producto;
// use App\Models\Marca;
// use App\Models\Categoria;
// use App\Models\UnidadMedida;
// use Illuminate\Support\Facades\Validator;

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\Producto;
use App\Models\Marca;
use App\Models\Categoria;
use App\Models\UnidadMedida;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

Route::post('importar-productos', function (Request $request) {
  // Validar que el archivo CSV esté presente y sea del tipo adecuado
  $validator = Validator::make($request->all(), [
    'csv_file' => 'required|mimes:csv,txt',
  ]);

  // Si hay errores en la validación, devolver la respuesta con el error
  if ($validator->fails()) {
    return response()->json(['error' => $validator->errors()], 400);
  }

  // Leer el archivo CSV
  $file = $request->file('csv_file');
  $csvData = array_map('str_getcsv', file($file));

  // Eliminar la cabecera (primera fila)
  array_shift($csvData);

  DB::beginTransaction();
  try {
    // Procesar cada fila del CSV
    foreach ($csvData as $row) {
      // 1. Validar y registrar la marca si no existe
      $marca = Marca::firstOrCreate(['nombre' => $row[3]]);

      // 2. Validar y registrar la categoría principal (si no existe)
      $categoria = Categoria::firstOrCreate([
        'nombre' => $row[4],
        'parent_id' => null,
      ]);

      // 3. Validar y registrar la subcategoría si existe (si no está vacía)
      $subcategoria = null;
      if (!empty($row[5])) {
        $subcategoria = Categoria::firstOrCreate([
          'nombre' => $row[5],
          'parent_id' => $categoria->id,
        ]);
      }

      // 4. Validar y registrar la unidad de medida si no existe
      $unidadMedida = UnidadMedida::firstOrCreate(['nombre' => $row[6]]);

      // 5. Crear el producto e insertarlo en la base de datos
      Producto::create([
        'codigo' => $row[0],
        'producto' => $row[1],
        'descripcion' => $row[2],
        'proveedor_id' => 1,
        'marca_id' => $marca->id,
        'categoria_id' => $categoria->id,
        'subcategoria_id' => $subcategoria ? $subcategoria->id : null,
        'unidad_medida_id' => $unidadMedida->id,
        'precio' => $row[7],
        'precio_mayoreo' => $row[8],
        'precio_menudeo' => $row[9],
      ]);
    }

    DB::commit();
    return response()->json(['success' => 'Productos importados correctamente'], 200);
  } catch (\Exception $e) {
    DB::rollBack();
    return response()->json(['error' => 'Error al procesar el archivo: ' . $e->getMessage()], 500);
  }
});


require __DIR__ . '/segmented/routes-segmented.php';
