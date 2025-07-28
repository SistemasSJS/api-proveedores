<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductoImport\ProductoImportUploadRequest;
use Illuminate\Http\Request;
use App\Models\ImportAudit;
use App\Jobs\ImportarProductosJob;
use App\Models\Proveedor;
use Illuminate\Support\Str;

class ProductoImportController extends Controller
{
  public function upload(ProductoImportUploadRequest $request, $proveedorId)
  {

    // Validar proveedor
    $proveedor = Proveedor::findOrFail($proveedorId);
    try {
      $file = $request->file('file');
      $originalExtension = $file->getClientOriginalExtension();
      $mimeType = $file->getMimeType();

      // Detect format based on extension and MIME type
      $formato = $this->detectFileFormat($originalExtension, $mimeType);

      $filename = "productos_import_{$proveedor->id}_" . time() . '.' . $originalExtension;
      $path = $file->storeAs('imports', $filename, 'local');

      $jobId = Str::uuid()->toString();

        $audit = ImportAudit::create([
        'job_id' => $jobId,
        'proveedor_id' => $proveedorId,
        'tipo' => 'productos',
        'archivo' => $path,
        'formato' => $formato,
        'estado' => 'pendiente'
      ]);
      ImportarProductosJob::dispatch($audit->id, false);

      return response()->json([
        'message' => 'Archivo cargado correctamente',
        'audit_id' => $audit->id,
        'job_id' => $jobId,
        'formato' => $formato,
        'estado' => 'pendiente'
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'message' => 'Error al cargar el archivo',
        'error' => $e->getMessage()
      ], 500);
    }
  }

  public function status(Request $request, $proveedorId, $auditId)
  {
    $audit = ImportAudit::where('id', $auditId)
      ->where('proveedor_id', $proveedorId)
      ->first();

    if (!$audit) {
      return response()->json(['error' => 'Importación no encontrada'], 404);
    }

    return response()->json([
      'id' => $audit->id,
      'job_id' => $audit->job_id,
      'tipo' => $audit->tipo,
      'formato' => $audit->formato,
      'estado' => $audit->estado,
      'progreso' => $audit->progreso,
      'total_registros' => $audit->total_registros,
      'nuevos' => $audit->nuevos,
      'actualizados' => $audit->actualizados,
      'eliminados' => $audit->eliminados,
      'errores' => $audit->errores,
      'preview_data' => $audit->preview_data,
      'errores_detalle' => $audit->errores_detalle,
      'inicio_proceso' => $audit->inicio_proceso,
      'fin_proceso' => $audit->fin_proceso
    ]);
  }

  public function confirm($proveedorId, $auditId)
  {
    $audit = ImportAudit::where('id', $auditId)
      ->where('proveedor_id', $proveedorId)
      ->where('estado', 'preview')
      ->first();

    if (!$audit) {
      return response()->json(['error' => 'No se puede confirmar esta importación'], 400);
    }

    ImportarProductosJob::dispatch($audit->id, true);

    $audit->update(['estado' => 'confirmado']);

    return response()->json([
      'message' => 'Importación confirmada',
      'audit_id' => $audit->id
    ]);
  }

  public function list($proveedorId)
  {
    $imports = ImportAudit::where('proveedor_id', $proveedorId)
      ->orderBy('created_at', 'desc')
      ->paginate(10);

    return response()->json($imports);
    // return $this->paginated()
  }

  public function downloadTemplate()
  {
    $templates = [
      'productos' => [
        'headers' => [
          'sku',
          'nombre_modelo',
          'codigo_interno',
          'nombre_producto',
          'descripcion_producto',
          'nombre_marca',
          'nombre_linea',
          'nombre_categoria_nivel_1',
          'nombre_categoria_nivel_2',
          'nombre_categoria_nivel_3',
          'precio_base',
          'precio_de_lista',
          'precio_publico',
          'precio_mayoreo',
          'precio_con_IVA',
          'precio_sin_IVA',
          'precio_promocional',
          'precio_distribuidor',
          'precio_especial',
        ],
      ]
    ];

    // if (!isset($templates[$tipo])) {
    //   return response()->json(['error' => 'Tipo no válido'], 400);
    // }

    $content = implode(',', $templates['productos']['headers']) . "\n";
    // Agregar fila de ejemplo con datos de ejemplo
    $exampleRow = array_fill(0, count($templates['productos']['headers']), 'ejemplo');
    $content .= implode(',', $exampleRow);

    return response($content)
      ->header('Content-Type', 'text/csv')
      ->header('Content-Disposition', "attachment; filename=template_productos.csv");
  }

  /**
   * Detect file format based on extension and MIME type
   */
  private function detectFileFormat($extension, $mimeType)
  {
    $extension = strtolower($extension);

    switch ($extension) {
      case 'csv':
        return 'csv';
      case 'txt':
        return 'txt';
      case 'json':
        return 'json';
      case 'xlsx':
        return 'xlsx';
      case 'xls':
        return 'xls';
      default:
        // Fallback to MIME type detection
        if (strpos($mimeType, 'text/csv') !== false) {
          return 'csv';
        } elseif (strpos($mimeType, 'text/plain') !== false) {
          return 'txt';
        } elseif (strpos($mimeType, 'application/json') !== false) {
          return 'json';
        } elseif (strpos($mimeType, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') !== false) {
          return 'xlsx';
        } elseif (strpos($mimeType, 'application/vnd.ms-excel') !== false) {
          return 'xls';
        }
        return 'unknown';
    }
  }
}

// GET    /imports/products/template           - Download template
// POST   /proveedores/{id}/imports/products   - Upload file
// GET    /proveedores/{id}/imports/products   - List history (paginated)
// GET    /proveedores/{id}/imports/{auditId}  - Get status with phases/logs
// GET    /proveedores/{id}/imports/{auditId}/logs - Get detailed logs
// POST   /proveedores/{id}/imports/{auditId}/confirm - Confirm import