<?php

namespace App\Http\Controllers;

use App\Enums\EstadoSP;
use App\Http\Resources\SolicitudPago\SolicitudPagoResource;
use App\Models\SolicitudPago;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SPConstruccController extends Controller
{
    /**
     * Listado paginado filtrado por empresa de construcción
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(SolicitudPago::getFilters());
        $sortBy  = $request->input('sort_by', 'created_at');
        $order   = $request->input('order', 'desc');
        $perPage = $request->input('per_page', 10);

        $query = SolicitudPago::query()
            ->with(['proveedor', 'empresaConstrucc'])
            ->filter($filters)
            ->orderBy($sortBy, $order);

        // Aquí debería limitar por la empresa del usuario ConstruccApp
        if ($request->user()->empresa_construcc_id) {
            $query->where('empresa_construcc_id', $request->user()->empresa_construcc_id);
        }

        $paginator = $query->paginate($perPage);

        return $this->paginated(
            $paginator->setCollection(
                SolicitudPagoResource::collection($paginator)->collection
            )
        );
    }

    /**
     * Mostrar detalle de una solicitud
     */
    public function show(SolicitudPago $solicitudPago): JsonResponse
    {
        return $this->success(
            new SolicitudPagoResource($solicitudPago->load(['proveedor', 'empresaConstrucc']))
        );
    }

    /**
     * Autorizar una solicitud
     */
    public function autorizar(Request $request, SolicitudPago $solicitudPago): JsonResponse
    {
        $solicitudPago->update([
            'estado_solicitud' => EstadoSP::AUTORIZADA->value,
            'fecha_aprobado'   => now(),
        ]);

        return $this->success(
            new SolicitudPagoResource($solicitudPago->fresh()->load(['proveedor', 'empresaConstrucc'])),
            'Solicitud autorizada correctamente.'
        );
    }

    /**
     * Rechazar una solicitud
     */
    public function rechazar(Request $request, SolicitudPago $solicitudPago): JsonResponse
    {
        $request->validate([
            'motivo_rechazo' => 'required|string|max:255',
        ]);

        $solicitudPago->update([
            'estado_solicitud' => EstadoSP::RECHAZADA->value,
            'motivo_rechazo'   => $request->motivo_rechazo,
            'fecha_rechazado'  => now(),
        ]);

        return $this->success(
            new SolicitudPagoResource($solicitudPago->fresh()->load(['proveedor', 'empresaConstrucc'])),
            'Solicitud rechazada correctamente.'
        );
    }

    /**
     * Confirmar pago
     */
    public function confirmarPago(SolicitudPago $solicitudPago): JsonResponse
    {
        $solicitudPago->update([
            'estado_solicitud'        => EstadoSP::PAGADO->value,
            'fecha_confirmacion_pago' => now(),
        ]);

        return $this->success(
            new SolicitudPagoResource($solicitudPago->fresh()->load(['proveedor', 'empresaConstrucc'])),
            'Pago confirmado correctamente.'
        );
    }

    /**
     * Subir comprobante de pago
     */
    public function subirComprobantePago(Request $request, SolicitudPago $solicitudPago): JsonResponse
    {
        $request->validate([
            'comprobante' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $path = $request->file('comprobante')->store('comprobantes', 'private');

        $solicitudPago->update([
            'ruta_archivo_comprobante_pago' => $path,
            'estado_solicitud'              => EstadoSP::PAGADO->value,
            'fecha_confirmacion_pago'         => now(),
        ]);

        return $this->success(
            new SolicitudPagoResource($solicitudPago->fresh()->load(['proveedor', 'empresaConstrucc'])),
            'Comprobante de pago subido correctamente.'
        );
    }

    /**
     * Descargar comprobante
     */
    public function descargarComprobante(SolicitudPago $solicitudPago)
    {
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
     * Descargar factura PDF
     */
    public function descargarFacturaPdf(SolicitudPago $solicitudPago)
    {
        if (
            !$solicitudPago->ruta_archivo_factura_pdf ||
            !Storage::disk('private')->exists($solicitudPago->ruta_archivo_factura_pdf)
        ) {
            return $this->error('Factura PDF no disponible', 404);
        }

        return response()->download(
            Storage::disk('private')->path($solicitudPago->ruta_archivo_factura_pdf)
        );
    }

    /**
     * Descargar factura XML
     */
    public function descargarFacturaXml(SolicitudPago $solicitudPago)
    {
        if (
            !$solicitudPago->ruta_archivo_factura_xml ||
            !Storage::disk('private')->exists($solicitudPago->ruta_archivo_factura_xml)
        ) {
            return $this->error('Factura XML no disponible', 404);
        }

        return response()->download(
            Storage::disk('private')->path($solicitudPago->ruta_archivo_factura_xml)
        );
    }

    /**
    //  * Empresas constructoras activas
    //  */
    // public function empresasConstructoras(Request $request): JsonResponse
    // {
    //     // Lógica similar al ProveedorSolicitudPagoController
    // }

    // /**
    //  * Estadísticas (pendiente de definir qué métricas)
    //  */
    // public function estadisticas(Request $request): JsonResponse
    // {
    //     // Lógica agregada de conteos, sumatorias, etc.
    // }
}
