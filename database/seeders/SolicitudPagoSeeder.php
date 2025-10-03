<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SolicitudPago;
use App\Models\Cotizacion;
use App\Models\EmpresaConstrucc;
use App\Models\Sucursal;
use App\Enums\EstadoSP;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SolicitudPagoSeeder extends Seeder
{
    /**
     * Seeder para generar solicitudes de pago relacionadas con cotizaciones SP
     * Configurado para Los Mochis, Sinaloa, México
     */
    public function run(): void
    {
        DB::transaction(function () {
            // Obtener cotizaciones aceptadas de proveedores SP
            $cotizacionesAceptadas = Cotizacion::where('estatus', 'aceptada')
                ->whereHas('proveedor', fn($q) => $q->where('is_proveedor_sp', true))
                ->with('proveedor')
                ->get();

            if ($cotizacionesAceptadas->isEmpty()) {
                echo "⚠️ No se encontraron cotizaciones aceptadas de proveedores SP.\n";
                return;
            }

            // Empresas constructoras y sucursales
            $empresasConstrucc = EmpresaConstrucc::where('activo', true)->pluck('id')->toArray();
            $sucursales = Sucursal::pluck('id')->toArray();

            if (empty($empresasConstrucc)) {
                echo "⚠️ No se encontraron empresas constructoras.\n";
                return;
            }

            $now = Carbon::now('America/Mazatlan');
            $solicitudes = [];
            $totalSolicitudes = 0;

            // Seleccionar 70-80% de cotizaciones
            $numCotizaciones = round($cotizacionesAceptadas->count() * (rand(70, 80) / 100));
            $cotizacionesParaSolicitud = $cotizacionesAceptadas->shuffle()->take($numCotizaciones);

            foreach ($cotizacionesParaSolicitud as $cotizacion) {
                $fechaSolicitud = $cotizacion->fecha_cotizacion->copy()->addDays(rand(1, 15));
                $estadoSolicitud = $this->generarEstadoSolicitudAleatorio();

                $solicitud = [
                    'numero_folio_solicitud' => $this->generarFolioSolicitud($totalSolicitudes + 1),
                    'descripcion_concepto' => $this->generarDescripcionConcepto($cotizacion),
                    'ruta_archivo_factura_xml' => $this->generarRutaArchivo('xml', $totalSolicitudes + 1),
                    'ruta_archivo_factura_pdf' => $this->generarRutaArchivo('pdf', $totalSolicitudes + 1),
                    'estado_solicitud' => $estadoSolicitud,
                    'ruta_archivo_comprobante_pago' => $this->debeGenerarComprobante($estadoSolicitud)
                        ? $this->generarRutaArchivo('comprobante', $totalSolicitudes + 1)
                        : null,
                    'proveedor_id' => $cotizacion->proveedor_id,
                    'empresa_construcc_id' => $empresasConstrucc[array_rand($empresasConstrucc)],
                    'residente' => $this->generarNombreResidente(),
                    'cotizacion_id' => $cotizacion->id,
                    'sucursal_id' => !empty($sucursales) ? $sucursales[array_rand($sucursales)] : null,
                    'motivo_rechazo' => $estadoSolicitud === EstadoSP::RECHAZADA->value
                        ? $this->generarMotivoRechazo()
                        : null,

                    // Campos de estados específicos
                    'dg' => $this->generarEstadoDepartamento($estadoSolicitud, 'dg'),
                    'dg_fecha' => $this->generarFechaEstado($fechaSolicitud, 'dg', $estadoSolicitud),
                    'dt' => $this->generarEstadoDepartamento($estadoSolicitud, 'dt'),
                    'dt_fecha' => $this->generarFechaEstado($fechaSolicitud, 'dt', $estadoSolicitud),
                    'pc' => $this->generarEstadoDepartamento($estadoSolicitud, 'pc'),
                    'pc_fecha' => $this->generarFechaEstado($fechaSolicitud, 'pc', $estadoSolicitud),
                    'si' => $this->generarEstadoDepartamento($estadoSolicitud, 'si'),
                    'si_fecha' => $this->generarFechaEstado($fechaSolicitud, 'si', $estadoSolicitud),
                    'ro' => $this->generarEstadoDepartamento($estadoSolicitud, 'ro'),
                    'ro_fecha' => $this->generarFechaEstado($fechaSolicitud, 'ro', $estadoSolicitud),

                    'created_at' => $fechaSolicitud,
                    'updated_at' => $fechaSolicitud->copy()->addDays(rand(0, 10)),
                ];

                $solicitudes[] = $solicitud;
                $totalSolicitudes++;
            }

            // Insertar en lotes
            if (!empty($solicitudes)) {
                foreach (array_chunk($solicitudes, 50) as $chunk) {
                    DB::table('solicitudes_pago')->insert($chunk);
                }
            }

            echo "✅ Seeder SolicitudPagoSeeder ejecutado correctamente.\n";
            echo "📊 Se generaron {$totalSolicitudes} solicitudes de pago.\n";
            echo "🏗️  Basadas en " . $cotizacionesParaSolicitud->count() . " cotizaciones aceptadas.\n";
            echo "📍 Configurado para Los Mochis, Sinaloa, México.\n";
        });
    }

    // --- Métodos auxiliares ---

    private function generarEstadoSolicitudAleatorio(): string
    {
        $valores = EstadoSP::values(); // ['pendiente','rechazada','autorizada','pagado']
        return $valores[array_rand($valores)];
    }

    private function generarFolioSolicitud(int $numero): string
    {
        $año = date('Y');
        return "SP-{$año}-" . str_pad($numero, 6, '0', STR_PAD_LEFT);
    }

    private function generarDescripcionConcepto($cotizacion): string
    {
        $conceptos = [
            "Pago por suministro de materiales - {$cotizacion->proveedor->nombre_comercial}",
            "Solicitud de pago por servicios de construcción",
            "Pago de facturación - Proyecto Los Mochis",
            "Liquidación de servicios especializados",
            "Pago por entrega de materiales según cotización #{$cotizacion->id}",
        ];

        return $conceptos[array_rand($conceptos)];
    }

    private function generarRutaArchivo(string $tipo, int $numero): string
    {
        $año = date('Y');
        $mes = date('m');

        return match ($tipo) {
            'xml' => "uploads/facturas/xml/{$año}/{$mes}/factura_{$numero}.xml",
            'pdf' => "uploads/facturas/pdf/{$año}/{$mes}/factura_{$numero}.pdf",
            'comprobante' => "uploads/comprobantes/{$año}/{$mes}/comprobante_pago_{$numero}.pdf",
            default => "uploads/{$tipo}/{$año}/{$mes}/archivo_{$numero}.pdf",
        };
    }

    private function debeGenerarComprobante(string $estado): bool
    {
        return in_array($estado, [EstadoSP::PAGADO->value, EstadoSP::AUTORIZADA->value]) && rand(1, 100) <= 80;
    }

    private function generarNombreResidente(): string
    {
        $nombres = [
            'Ing. Carlos Alberto Mendoza',
            'Arq. María Elena Rodríguez',
            'Ing. José Luis Félix Castro',
            'Ing. Patricia Alejandra Ruiz',
            'Arq. Fernando Javier López',
        ];

        return $nombres[array_rand($nombres)];
    }

    private function generarMotivoRechazo(): string
    {
        $motivos = [
            'Documentación incompleta',
            'Factura no cumple con requisitos',
            'Presupuesto excede monto autorizado',
            'Requiere autorización adicional',
        ];

        return $motivos[array_rand($motivos)];
    }

    private function generarEstadoDepartamento(string $estadoSolicitud, string $departamento): ?int
    {
        if ($estadoSolicitud === EstadoSP::PENDIENTE->value) {
            return 0;
        }

        $probabilidades = [
            'dg' => 90,
            'dt' => 85,
            'pc' => 80,
            'si' => 75,
            'ro' => 70,
        ];

        $probabilidad = $probabilidades[$departamento] ?? 70;
        return rand(1, 100) <= $probabilidad ? rand(1, 3) : 0;
    }

    private function generarFechaEstado(Carbon $fechaBase, string $departamento, string $estadoSolicitud): ?Carbon
    {
        if ($estadoSolicitud === EstadoSP::PENDIENTE->value) {
            return null;
        }

        return rand(1, 100) <= 80 ? $fechaBase->copy()->addDays(rand(1, 30)) : null;
    }
}
