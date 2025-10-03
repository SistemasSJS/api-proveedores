<?php

namespace App\Http\Controllers;

use App\Http\Requests\Cotizacion\CotizacionRequest;
use App\Http\Requests\Cotizacion\CrearCotizacionRequest;
use App\Http\Resources\CotizacionResource;
use App\Models\Cotizacion;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ProveedorCotizacionController extends Controller
{
  /**
   * Listado paginado por proveedor
   */
  public function index(Request $request, Proveedor $proveedor): JsonResponse
  {
    $filters = $request->only(Cotizacion::getFilters());
    $sortBy  = $request->input('sort_by', 'created_at');
    $order   = $request->input('order', 'desc');
    $perPage = $request->input('per_page', 10);

    $originalPaginator = Cotizacion::query()
      ->with(Cotizacion::eagerLodable())
      ->where('proveedor_id', $proveedor->id)
      ->filter($filters)
      ->orderBy($sortBy, $order)
      ->paginate($perPage);

    $data = CotizacionResource::collection($originalPaginator)->resolve();

    return $this->paginated($originalPaginator->setCollection(collect($data)));
  }

  /**
   * Listado sin paginación
   */
  public function uindex(Request $request, Proveedor $proveedor): JsonResponse
  {
    $filters = $request->only(Cotizacion::getFilters());
    $sortBy  = $request->input('sort_by', 'created_at');
    $order   = $request->input('order', 'desc');

    $items = Cotizacion::query()
      ->with(['proveedor', 'empresaConstrucc'])
      ->where('proveedor_id', $proveedor->id)
      ->filter($filters)
      ->orderBy($sortBy, $order)
      ->get();

    return $this->success(
      CotizacionResource::collection($items)
    );
  }

  /**
   * Crear nueva cotización
   */
  public function store(CotizacionRequest $request, Proveedor $proveedor): JsonResponse
  {
    $archivoPdf = $request->file('archivo_pdf');

    if (!$archivoPdf) {
      return response()->json([
        'success' => false,
        'message' => 'El archivo PDF es obligatorio.'
      ], 422);
    }

    $rutaPdf = $archivoPdf->store('cotizaciones/pdf', 'private');

    // Generar folio
    $lastFolio = Cotizacion::where('proveedor_id', $proveedor->id)
      ->orderBy('id', 'desc')
      ->value('numero_folio_cotizacion');

    $lastNumber = $lastFolio ? (int) substr($lastFolio, 2) : 0;
    $nextNumber = $lastNumber + 1;
    $numeroFolio = 'CT' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

    $solicitud = Cotizacion::create([
      'proveedor_id' => $proveedor->id,
      'numero_folio_cotizacion' => $numeroFolio,
      'descripcion' => $request->descripcion,
      'ruta_archivo_pdf' => $rutaPdf,
      'empresa_construcc_id' => $request->empresa_construcc_id,
      'estado_cotizacion' => 'pendiente',
      'fecha_registro' => now(),
    ]);

    return $this->success(
      new CotizacionResource($solicitud->load(['proveedor', 'empresaConstrucc'])),
      'Cotización creada correctamente.',
      201
    );
  }

  /**
   * Mostrar detalle
   */
  public function show(Proveedor $proveedor, Cotizacion $cotizacion): JsonResponse
  {
    if ($cotizacion->proveedor_id !== $proveedor->id) {
      return $this->error('Cotización no pertenece a este proveedor', 403);
    }

    return $this->success(
      new CotizacionResource($cotizacion->load(['proveedor', 'empresaConstrucc']))
    );
  }

  /**
   * Descargar PDF
   */
  public function descargarPdf(Proveedor $proveedor, Cotizacion $cotizacion)
  {
    if ($cotizacion->proveedor_id !== $proveedor->id) {
      return $this->error('Cotización no pertenece a este proveedor', 403);
    }

    if (!$cotizacion->ruta_archivo_pdf || !Storage::disk('private')->exists($cotizacion->ruta_archivo_pdf)) {
      return $this->error('Archivo PDF no disponible', 404);
    }

    return response()->download(
      Storage::disk('private')->path($cotizacion->ruta_archivo_pdf)
    );
  }

  /**
   * Actualizar cotización
   */
  public function update(CotizacionRequest $request, Proveedor $proveedor, Cotizacion $cotizacion): JsonResponse
  {
    if ($cotizacion->proveedor_id !== $proveedor->id) {
      return $this->error('Cotización no pertenece a este proveedor', 403);
    }

    $cotizacion->update($request->validated());

    return $this->success(
      new CotizacionResource($cotizacion->load(['proveedor', 'empresaConstrucc'])),
      'Cotización actualizada correctamente.'
    );
  }

  /**
   * Eliminar cotización
   */
  public function destroy(Proveedor $proveedor, Cotizacion $cotizacion): JsonResponse
  {
    if ($cotizacion->proveedor_id !== $proveedor->id) {
      return $this->error('Cotización no pertenece a este proveedor', 403);
    }

    $cotizacion->delete();

    return $this->success(null, 'Cotización eliminada correctamente.');
  }
}
