<?php

namespace App\Http\Controllers;

use App\Models\PagoSPP;
use App\Models\SolicitudPago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use ZipArchive;


class ConstruccReportesController extends Controller
{

  public function __construct() {}

  /**
   * Reporte de contabilidad: Se genera con el listado de los pagos realizados, junto con la informaicon de la factura
   * 
   * GET /api/construcc/reportes/contabilidad
   * 
   * Parametros de entrada para filtrado:
   *  - banco_id : Id de la cuenta bancaria registrado en Construcc 
   *  - fecha_pago_desde : Fecha inicio del rango (default: hoy)
   *  - fecha_pago_hasta : Fecha fin del rango (default: hoy)
   *  
   * Generar salida con las columnas: 
   *  - fecha: fecha_pago
   *  - proveedor: RFC, Razon Social
   *  - factura: Folio (un registro por cada factura asociada a la SPP)
   *  - enlace_descarga: URL para descargar la factura PDF
   *  - importe: monto aplicado del pago
   */
  public function reporteContabilidad(Request $request)
  {
    // Validar parámetros de entrada
    $validated = $request->validate([
      'banco_id' => 'nullable|integer',
      'fecha_pago_desde' => 'nullable|date',
      'fecha_pago_hasta' => 'nullable|date',
    ]);

    // Establecer fechas por defecto (hoy)
    $fechaDesde = $validated['fecha_pago_desde'] ?? now()->format('Y-m-d');
    $fechaHasta = $validated['fecha_pago_hasta'] ?? now()->format('Y-m-d');
    $bancoId = $validated['banco_id'] ?? null;

    // Construir consulta de pagos con relaciones
    $query = PagoSPP::with([
      'proveedor:id,rfc,razon_social,nombre_comercial',
      'solicitudesPago' => function ($q) {
        $q->select(
          'solicitudes_pago.id',
          'solicitudes_pago.folio_factura',
          'solicitudes_pago.ruta_archivo_factura_pdf',
          'solicitudes_pago.ruta_archivo_factura_xml',
          'solicitudes_pago.proveedor_id'
        );
      }
    ])
      ->whereDate('fecha_pago', '>=', $fechaDesde)
      ->whereDate('fecha_pago', '<=', $fechaHasta);

    // Filtrar por banco si se especifica
    if ($bancoId) {
      $query->where('cuenta_bancaria_empresa_construcc_id', $bancoId);
    }

    // Ordenar por fecha de pago
    $pagos = $query->orderBy('fecha_pago', 'asc')->get();

    // Transformar datos para el reporte
    $data = [];

    foreach ($pagos as $pago) {
      $sppDelPago = $pago->solicitudesPago;
      $totalSppEnPago = $sppDelPago->count();

      // Si el pago tiene múltiples SPP, generamos URL de descarga múltiple
      $urlDescargaMultiplePdf = null;
      $urlDescargaMultipleXml = null;

      if ($totalSppEnPago > 1) {
        $sppIds = $sppDelPago->pluck('id')->toArray();
        $sppIdsString = implode(',', $sppIds);

        // Generar URLs usando la ruta pública con parámetros de ruta
        $urlDescargaMultiplePdf = url('/api/construcc/reportes/descargar-facturas-multiple/' . $sppIdsString . '/pdf');
        $urlDescargaMultipleXml = url('/api/construcc/reportes/descargar-facturas-multiple/' . $sppIdsString . '/xml');
      }

      foreach ($sppDelPago as $spp) {
        // Obtener monto aplicado desde la tabla pivot
        $montoAplicado = $spp->pivot->monto_aplicado ?? 0;

        // Generar URL de descarga INDIVIDUAL usando la MISMA ruta pública
        $enlaceDescargaPdf = null;
        $enlaceDescargaXml = null;

        // if ($spp->ruta_archivo_factura_pdf) {
        //   // Usar la ruta pública de descarga múltiple, pero con un solo ID
        //   $enlaceDescargaPdf = url('/api/construcc/reportes/descargar-facturas-multiple/' . $spp->id . '/pdf');
        // }

        // if ($spp->ruta_archivo_factura_xml) {
        //   // Usar la ruta pública de descarga múltiple, pero con un solo ID
        //   $enlaceDescargaXml = url('/api/construcc/reportes/descargar-facturas-multiple/' . $spp->id . '/xml');
        // }

        $data[] = [
          'pago_id' => $pago->id,
          'fecha_pago' => $pago->fecha_pago ? $pago->fecha_pago->format('Y-m-d') : null,
          'proveedor_rfc' => $pago->proveedor->rfc ?? null,
          'proveedor_razon_social' => $pago->proveedor->razon_social ?? $pago->proveedor->nombre_comercial ?? null,
          'folio_factura' => $spp->folio_factura,
          'spp_id' => $spp->id,

          // URLs de descarga usando SOLO la ruta pública
          'enlace_descarga_pdf' => $enlaceDescargaPdf,
          'enlace_descarga_xml' => $enlaceDescargaXml,

          // Si hay múltiples facturas en el pago, incluir URL para descargar todas
          'total_facturas_en_pago' => $totalSppEnPago,
          'enlace_descarga_todas_pdf' => $totalSppEnPago > 1 ? $urlDescargaMultiplePdf : null,
          'enlace_descarga_todas_xml' => $totalSppEnPago > 1 ? $urlDescargaMultipleXml : null,

          'importe' => number_format($montoAplicado, 2, '.', ''),

          // Información adicional útil
          'referencia_pago' => $pago->referencia_pago,
          'clave_rastreo' => $pago->clave_rastreo,
          'banco_destino' => $pago->banco_destino,
          'titular_cuenta_destino' => $pago->titular_cuenta_destino,
        ];
      }
    }

    return $this->success([
      'total_registros' => count($data),
      'fecha_desde' => $fechaDesde,
      'fecha_hasta' => $fechaHasta,
      'banco_id' => $bancoId,
      'registros' => $data,
    ], 'Reporte de contabilidad generado con éxito.');
  }

  /**
   * Descargar facturas de múltiples SPP en un archivo ZIP
   * 
   * GET /api/construcc/reportes/descargar-facturas-multiple/{spp_ids}/{tipo?}
   * 
   * Parámetros:
   *  - spp_ids: IDs de solicitudes de pago separados por comas (ej: 1,2,3,4)
   *  - tipo: 'pdf', 'xml' o 'ambos' (default: 'pdf', opcional)
   * 
   * Ejemplos:
   *  - /api/construcc/reportes/descargar-facturas-multiple/1,2,3
   *  - /api/construcc/reportes/descargar-facturas-multiple/1,2,3/pdf
   *  - /api/construcc/reportes/descargar-facturas-multiple/1,2,3/xml
   *  - /api/construcc/reportes/descargar-facturas-multiple/1,2,3/ambos
   */
  public function descargarFacturasMultiple(Request $request, string $spp_ids, string $tipo = 'pdf')
  {
    // Convertir string de IDs separados por comas a array
    $sppIdsArray = array_filter(array_map('intval', explode(',', $spp_ids)));

    // Validar que hay IDs
    if (empty($sppIdsArray)) {
      return $this->error('Debe proporcionar al menos un ID de solicitud de pago.', 400);
    }

    // Validar tipo
    if (!in_array($tipo, ['pdf', 'xml', 'ambos'])) {
      return $this->error('Tipo inválido. Use: pdf, xml o ambos', 400);
    }

    // Validar que los IDs existen
    $solicitudes = SolicitudPago::whereIn('id', $sppIdsArray)->get();

    if ($solicitudes->isEmpty()) {
      return $this->error('No se encontraron solicitudes de pago.', 404);
    }

    // Verificar que los IDs proporcionados existen todos
    $idsEncontrados = $solicitudes->pluck('id')->toArray();
    $idsNoEncontrados = array_diff($sppIdsArray, $idsEncontrados);

    if (!empty($idsNoEncontrados)) {
      return $this->error('Algunos IDs no existen: ' . implode(', ', $idsNoEncontrados), 404);
    }

    // Crear nombre único para el archivo ZIP
    $zipFileName = 'facturas_' . date('YmdHis') . '_' . uniqid() . '.zip';
    $zipPath = storage_path('app/temp/' . $zipFileName);

    // Asegurar que el directorio existe
    if (!file_exists(storage_path('app/temp'))) {
      mkdir(storage_path('app/temp'), 0755, true);
    }

    // Crear archivo ZIP
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
      return $this->error('No se pudo crear el archivo ZIP.', 500);
    }

    $archivosAgregados = 0;

    foreach ($solicitudes as $spp) {
      $folioSanitizado = preg_replace('/[^a-zA-Z0-9_-]/', '_', $spp->folio_factura ?? 'sin_folio');

      // Agregar PDF si se solicita
      if (($tipo === 'pdf' || $tipo === 'ambos') && $spp->ruta_archivo_factura_pdf) {
        $rutaPdf = storage_path($spp->ruta_archivo_factura_pdf);
        if (file_exists($rutaPdf)) {
          $nombreArchivo = "SPP_{$spp->id}_{$folioSanitizado}.pdf";
          $zip->addFile($rutaPdf, $nombreArchivo);
          $archivosAgregados++;
        }
      }

      // Agregar XML si se solicita
      if (($tipo === 'xml' || $tipo === 'ambos') && $spp->ruta_archivo_factura_xml) {
        $rutaXml = storage_path($spp->ruta_archivo_factura_xml);
        if (file_exists($rutaXml)) {
          $nombreArchivo = "SPP_{$spp->id}_{$folioSanitizado}.xml";
          $zip->addFile($rutaXml, $nombreArchivo);
          $archivosAgregados++;
        }
      }
    }

    $zip->close();

    if ($archivosAgregados === 0) {
      // Eliminar el ZIP vacío
      if (file_exists($zipPath)) {
        unlink($zipPath);
      }
      return $this->error('No se encontraron archivos de facturas para las solicitudes especificadas.', 404);
    }

    // Descargar el archivo y eliminarlo después
    return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
  }
}
