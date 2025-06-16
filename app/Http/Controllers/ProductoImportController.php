<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ImportAudit;
use App\Jobs\ImportarProductosJob;
use App\Models\Proveedor;
use Illuminate\Support\Str;

class ProductoImportController extends Controller
{
  public function upload(Request $request, $proveedorId)
  {
    $request->validate([
      'file' => 'required|file|mimes:csv,txt|max:10240',
      'tipo' => 'required|in:productos,marcas,lineas,categorias'
    ]);

    // Validar proveedor
    $proveedor = Proveedor::findOrFail($proveedorId);
    try {
      // $file = $request->file('file');
      // $filename = "productos_csv_{$proveedor->id}_" . time() . '.' . $file->getClientOriginalExtension();
      // $path = $file->store('imports', $filename, 'private');


      $file = $request->file('file');
      $filename = "productos_csv_{$proveedor->id}_" . time() . '.' . $file->getClientOriginalExtension();
      $path = $file->storeAs('imports', $filename, 'local');

      $jobId = Str::uuid()->toString();

      $audit = ImportAudit::create([
        'job_id' => $jobId,
        'proveedor_id' => $proveedorId,
        'tipo' => $request->tipo,
        'archivo' => $path,
        'estado' => 'pendiente'
      ]);

      ImportarProductosJob::dispatch($audit->id, false);

      return response()->json([
        'message' => 'Archivo cargado correctamente',
        'audit_id' => $audit->id,
        'job_id' => $jobId,
        'estado' => 'pendiente'
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'message' => 'Error al cargar el archivo',
        'error' => $e->getMessage()
      ], 500);
    }
  }

  public function status($proveedorId, $auditId)
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
  }

  public function downloadTemplate($tipo)
  {
    $templates = [
      'productos' => [
        'headers' => [
          'sku',
          'nombre_producto',
          'descripcion',
          'precio',
          'cantidad_disponible',
          'activo',
          'nombre_marca',
          'nombre_linea',
          'categorias'
        ],
        'example' => [
          'PRD001',
          'Producto Ejemplo',
          'Descripción del producto',
          '99.99',
          '100',
          'true',
          'Marca Ejemplo',
          'Línea Ejemplo',
          'Categoría1,Categoría2'
        ]
      ]
    ];

    if (!isset($templates[$tipo])) {
      return response()->json(['error' => 'Tipo no válido'], 400);
    }

    $content = implode(',', $templates[$tipo]['headers']) . "\n";
    $content .= implode(',', $templates[$tipo]['example']);

    return response($content)
      ->header('Content-Type', 'text/csv')
      ->header('Content-Disposition', "attachment; filename=template_{$tipo}.csv");
  }
}
