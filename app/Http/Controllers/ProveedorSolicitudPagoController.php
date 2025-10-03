<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudPago\CrearSolicitudPagoRequest;
use App\Http\Resources\SolicitudPago\SolicitudPagoResource;
use App\Models\SolicitudPago;
use App\Models\Proveedor;
use App\Models\EmpresaConstrucc;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ProveedorSolicitudPagoController extends Controller
{
  /**
   * Listado con paginación, filtrando por proveedor
   */
  public function index(Request $request, Proveedor $proveedor): JsonResponse
  {
    $filters = $request->only(SolicitudPago::getFilters());
    $sortBy  = $request->input('sort_by', 'created_at');
    $order   = $request->input('order', 'desc');
    $perPage = $request->input('per_page', 10);

    $originalPaginator = SolicitudPago::query()
      ->with(SolicitudPago::eagerLodable())
      // ->where('proveedor_id', $proveedor->id)
      ->filter($filters)
      ->orderBy($sortBy, $order)
      ->paginate($perPage);

    $data = SolicitudPagoResource::collection($originalPaginator)->resolve();

    return $this->paginated($originalPaginator->setCollection(collect($data)));
  }

  /**
   * Listado sin paginación, filtrando por proveedor
   */
  public function uindex(Request $request, Proveedor $proveedor): JsonResponse
  {
    $filters = $request->only(SolicitudPago::getFilters());
    $sortBy  = $request->input('sort_by', 'created_at');
    $order   = $request->input('order', 'desc');

    $items = SolicitudPago::query()
      ->with(['proveedor', 'empresaConstrucc'])
      ->where('proveedor_id', $proveedor->id)
      ->filter($filters)
      ->orderBy($sortBy, $order)
      ->get();

    return $this->success(
      SolicitudPagoResource::collection($items)
    );
  }

  /**
   * Crear nueva solicitud
   */
  public function store(CrearSolicitudPagoRequest $request, Proveedor $proveedor): JsonResponse
  {
    $facturaPdf = $request->file('factura_pdf');
    $facturaXml = $request->file('factura_xml');

    if (!$facturaPdf || !$facturaXml) {
      return response()->json([
        'success' => false,
        'message' => 'Los archivos PDF y XML son obligatorios.'
      ], 422);
    }

    $rutaPdf = $facturaPdf->store('facturas/pdf', 'private');
    $rutaXml = $facturaXml->store('facturas/xml', 'private');

    // Generar folio
    $lastFolio = SolicitudPago::where('proveedor_id', $proveedor->id)
      ->orderBy('id', 'desc')
      ->value('numero_folio_solicitud');
    $lastNumber = $lastFolio ? (int) substr($lastFolio, 2) : 0;
    $nextNumber = $lastNumber + 1;
    $numeroFolio = 'SP' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

    $empresaConstructId = $request->empresa_construcc_id;

    $solicitud = SolicitudPago::create([
      'proveedor_id' => $proveedor->id,
      'numero_folio_solicitud' => $numeroFolio,
      'descripcion_concepto' => $request->descripcion_concepto,
      'ruta_archivo_factura_pdf' => $rutaPdf,
      'ruta_archivo_factura_xml' => $rutaXml,
      'empresa_construcc_id' => $empresaConstructId,
      'residente' => $request->residente,
      'cotizacion_id' => $request->cotizacion_id,
      'estado_solicitud' => 'pendiente',
      'fecha_registro_pendiente' => now(),
    ]);

    return $this->success(
      new SolicitudPagoResource($solicitud->load(['proveedor', 'empresaConstrucc'])),
      'Solicitud de pago creada correctamente.',
      201
    );
  }

  /**
   * Mostrar detalle
   */
  public function show(Proveedor $proveedor, SolicitudPago $solicitudPago): JsonResponse
  {
    if ($solicitudPago->proveedor_id !== $proveedor->id) {
      return $this->error('Solicitud no pertenece a este proveedor', 403);
    }

    return $this->success(
      new SolicitudPagoResource($solicitudPago->load(['proveedor', 'empresaConstrucc']))
    );
  }

  /**
   * Subir comprobante (solo guarda el archivo, no cambia estado)
   */
  public function subirComprobantePago(Request $request, Proveedor $proveedor, SolicitudPago $solicitudPago): JsonResponse
  {
    if ($solicitudPago->proveedor_id !== $proveedor->id) {
      return $this->error('Solicitud no pertenece a este proveedor', 403);
    }

    $request->validate([
      'comprobante' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
    ]);

    $file = $request->file('comprobante');
    $path = $file->store('comprobantes', 'private');

    $solicitudPago->update([
      'ruta_archivo_comprobante_pago' => $path,
      'fecha_con_comprobante' => now(),
    ]);

    return $this->success(
      new SolicitudPagoResource($solicitudPago->load(['proveedor', 'empresaConstrucc'])),
      'Comprobante de pago subido correctamente.'
    );
  }

  /**
   * Descargar factura PDF
   */
  public function descargarFacturaPdf(Proveedor $proveedor, SolicitudPago $solicitudPago)
  {
    if ($solicitudPago->proveedor_id !== $proveedor->id) {
      return $this->error('Solicitud no pertenece a este proveedor', 403);
    }

    if (!$solicitudPago->ruta_archivo_factura_pdf || !Storage::disk('private')->exists($solicitudPago->ruta_archivo_factura_pdf)) {
      return $this->error('Factura PDF no disponible', 404);
    }

    return response()->download(
      Storage::disk('private')->path($solicitudPago->ruta_archivo_factura_pdf)
    );
  }

  /**
   * Descargar factura XML
   */
  public function descargarFacturaXml(Proveedor $proveedor, SolicitudPago $solicitudPago)
  {
    if ($solicitudPago->proveedor_id !== $proveedor->id) {
      return $this->error('Solicitud no pertenece a este proveedor', 403);
    }

    if (!$solicitudPago->ruta_archivo_factura_xml || !Storage::disk('private')->exists($solicitudPago->ruta_archivo_factura_xml)) {
      return $this->error('Factura XML no disponible', 404);
    }

    return response()->download(
      Storage::disk('private')->path($solicitudPago->ruta_archivo_factura_xml)
    );
  }

  /**
   * Descargar comprobante de pago
   */
  public function descargarComprobantePago(Proveedor $proveedor, SolicitudPago $solicitudPago)
  {
    if ($solicitudPago->proveedor_id !== $proveedor->id) {
      return $this->error('Solicitud no pertenece a este proveedor', 403);
    }

    if (!$solicitudPago->ruta_archivo_comprobante_pago || !Storage::disk('private')->exists($solicitudPago->ruta_archivo_comprobante_pago)) {
      return $this->error('Comprobante no disponible', 404);
    }

    return response()->download(
      Storage::disk('private')->path($solicitudPago->ruta_archivo_comprobante_pago)
    );
  }


  /**
   * Autorizar
   */
  public function autorizar(Request $request, Proveedor $proveedor, SolicitudPago $solicitudPago): JsonResponse
  {
    if ($solicitudPago->proveedor_id !== $proveedor->id) {
      return $this->error('Solicitud no pertenece a este proveedor', 403);
    }

    $solicitudPago->update([
      'estado_solicitud' => 'autorizada',
      'fecha_aprobado' => now(),
    ]);

    return $this->success(
      new SolicitudPagoResource($solicitudPago->load(['proveedor', 'empresaConstrucc'])),
      'Solicitud autorizada correctamente.'
    );
  }

  /**
   * Rechazar
   */
  public function rechazar(Request $request, Proveedor $proveedor, SolicitudPago $solicitudPago): JsonResponse
  {
    if ($solicitudPago->proveedor_id !== $proveedor->id) {
      return $this->error('Solicitud no pertenece a este proveedor', 403);
    }

    $request->validate([
      'motivo_rechazo' => 'required|string|max:500',
    ]);

    $solicitudPago->update([
      'estado_solicitud' => 'rechazada',
      'fecha_rechazado' => now(),
      'motivo_rechazo' => $request->motivo_rechazo,
    ]);

    return $this->success(
      new SolicitudPagoResource($solicitudPago->load(['proveedor', 'empresaConstrucc'])),
      'Solicitud rechazada correctamente.'
    );
  }

  /**
   * Confirmar pago
   */
  public function confirmarPago(Request $request, Proveedor $proveedor, SolicitudPago $solicitudPago): JsonResponse
  {
    if ($solicitudPago->proveedor_id !== $proveedor->id) {
      return $this->error('Solicitud no pertenece a este proveedor', 403);
    }

    $solicitudPago->update([
      'estado_solicitud' => 'pagada',
      'fecha_confirmacion_pago' => now(),
    ]);

    return $this->success(
      new SolicitudPagoResource($solicitudPago->load(['proveedor', 'empresaConstrucc'])),
      'Pago confirmado correctamente.'
    );
  }

  /**
   * Eliminar
   */
  public function destroy(Proveedor $proveedor, SolicitudPago $solicitudPago): JsonResponse
  {
    if ($solicitudPago->proveedor_id !== $proveedor->id) {
      return $this->error('Solicitud no pertenece a este proveedor', 403);
    }

    $solicitudPago->delete();

    return $this->success(null, 'Solicitud de pago eliminada correctamente.');
  }

  /**
   * Empresas de construcción
   */
  public function empresasConstructoras(Request $request): JsonResponse
  {
    $search = $request->input('search', '');
    $limit = $request->input('limit', 20);

    $query = EmpresaConstrucc::activo();

    if ($search) {
      $query->where(function ($q) use ($search) {
        $q->where('nombre', 'LIKE', "%{$search}%")
          ->orWhere('razon_social', 'LIKE', "%{$search}%")
          ->orWhere('rfc', 'LIKE', "%{$search}%");
      });
    }

    $empresas = $query->limit($limit)->get();

    return $this->success(
      $empresas->map(function ($empresa) {
        return [
          'id' => $empresa->id,
          'nombre' => $empresa->nombre,
          'rfc' => $empresa->rfc,
          'razon_social' => $empresa->razon_social,
          'representante_legal' => $empresa->representante_legal,
        ];
      })
    );
  }
}
