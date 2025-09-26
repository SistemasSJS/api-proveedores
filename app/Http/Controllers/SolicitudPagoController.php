<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudPago\CrearSolicitudPagoRequest;
use App\Http\Requests\SolicitudPago\ActualizarEstadoPagadoRequest;
use App\Http\Requests\SolicitudPago\ActualizarEstadoProcesandoRequest;
use App\Http\Resources\SolicitudPago\SolicitudPagoResource;
use App\Models\SolicitudPago;
use App\Models\Proveedor;
use App\Models\EmpresaConstrucc;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class SolicitudPagoController extends Controller
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

    $query = SolicitudPago::query()
      ->with(['proveedor', 'empresaConstrucc'])
      ->where('proveedor_id', $proveedor->id)
      ->filter($filters)
      ->orderBy($sortBy, $order);

    $paginator = $query->paginate($perPage);

    $data = SolicitudPagoResource::collection($paginator)->resolve();

    return $this->paginated($paginator->setCollection(collect($data)));
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


  public function store(CrearSolicitudPagoRequest $request, Proveedor $proveedor): JsonResponse
  {
    // Validar que existan los archivos
    $facturaPdf = $request->file('factura_pdf');
    $facturaXml = $request->file('factura_xml');

    if (!$facturaPdf || !$facturaXml) {
      return response()->json([
        'success' => false,
        'message' => 'Los archivos PDF y XML son obligatorios.'
      ], 422);
    }

    // Subir archivos
    $rutaPdf = $facturaPdf->store('facturas/pdf', 'private');
    $rutaXml = $facturaXml->store('facturas/xml', 'private');

    // Generar folio
    $lastFolio = SolicitudPago::where('proveedor_id', $proveedor->id)
      ->orderBy('id', 'desc')
      ->value('numero_folio_solicitud');
    $lastNumber = $lastFolio ? (int) substr($lastFolio, 2) : 0;
    $nextNumber = $lastNumber + 1;
    $numeroFolio = 'SP' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

    // Buscar o crear empresa de construcción si se proporciona
    $empresaConstructId = null;
    if ($request->filled('empresa') && !$request->filled('empresa_construcc_id')) {
      // Buscar empresa existente por nombre
      $empresaExistente = EmpresaConstrucc::where('nombre', $request->empresa)
        ->orWhere('razon_social', $request->empresa)
        ->first();

      if ($empresaExistente) {
        $empresaConstructId = $empresaExistente->id;
      }
    } else if ($request->filled('empresa_construcc_id')) {
      $empresaConstructId = $request->empresa_construcc_id;
    }

    // Crear solicitud
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
      'ruta_archivo_comprobante_pago' => null,
      'sucursal_id' => null,
    ]);

    return $this->success(
      new SolicitudPagoResource($solicitud->load(['proveedor', 'empresaConstrucc'])),
      'Solicitud de pago creada correctamente.',
      201
    );
  }

  /**
   * Mostrar detalle de una solicitud de pago
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
   * Subir comprobante de pago en almacenamiento privado
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
      'estado_solicitud' => 'con_comprobante',
    ]);

    return $this->success(
      new SolicitudPagoResource($solicitudPago->load(['proveedor', 'empresaConstrucc'])),
      'Comprobante de pago subido correctamente.',
      200
    );
  }

  /**
   * Descargar comprobante de pago (solo autorizado)
   */
  public function descargarComprobante(Proveedor $proveedor, SolicitudPago $solicitudPago)
  {
    if ($solicitudPago->proveedor_id !== $proveedor->id) {
      return $this->error('Solicitud no pertenece a este proveedor', 403);
    }

    if (
      !$solicitudPago->ruta_archivo_comprobante_pago ||
      !Storage::disk('private')->exists($solicitudPago->ruta_archivo_comprobante_pago)
    ) {
      return $this->error('Comprobante no disponible', 404);
    }

    return response()->download(
      Storage::disk('private')->path($solicitudPago->ruta_archivo_comprobante_pago)
    );
  }

  /**
   * Confirmar pago (estado pagado)
   */
  public function confirmarPagoSP(ActualizarEstadoPagadoRequest $request, Proveedor $proveedor, SolicitudPago $solicitudPago): JsonResponse
  {
    if ($solicitudPago->proveedor_id !== $proveedor->id) {
      return $this->error('Solicitud no pertenece a este proveedor', 403);
    }

    $solicitudPago->update(['estado_solicitud' => 'pagado']);

    return $this->success(
      new SolicitudPagoResource($solicitudPago->load(['proveedor', 'empresaConstrucc'])),
      'Pago de la solicitud confirmado correctamente.'
    );
  }

  /**
   * Actualizar a procesando
   */
  public function procesando(ActualizarEstadoProcesandoRequest $request, Proveedor $proveedor, SolicitudPago $solicitudPago): JsonResponse
  {
    if ($solicitudPago->proveedor_id !== $proveedor->id) {
      return $this->error('Solicitud no pertenece a este proveedor', 403);
    }

    $solicitudPago->update(['estado_solicitud' => 'procesando']);

    return $this->success(
      new SolicitudPagoResource($solicitudPago->load(['proveedor', 'empresaConstrucc'])),
      'Solicitud de pago actualizada a procesando.'
    );
  }

  /**
   * Actualizar datos de la solicitud
   */
  public function update(CrearSolicitudPagoRequest $request, Proveedor $proveedor, SolicitudPago $solicitudPago): JsonResponse
  {
    if ($solicitudPago->proveedor_id !== $proveedor->id) {
      return $this->error('Solicitud no pertenece a este proveedor', 403);
    }

    $solicitudPago->update($request->validated());

    return $this->success(
      new SolicitudPagoResource($solicitudPago->load(['proveedor', 'empresaConstrucc'])),
      'Solicitud de pago actualizada correctamente.'
    );
  }

  /**
   * Eliminar solicitud
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
   * Obtener empresas de construcción para búsqueda
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
