<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SolicitudPago;
use App\Models\Proveedor;
use App\Models\EmpresaConstrucc;
use App\Models\Sucursal;
use App\Models\Cotizacion;
use App\Enums\EstadoSP;
use App\Enums\EstadoSolicitud;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Seeder para generar SPPs (Solicitudes de Pago de Proveedor) con estado AUTORIZADA
 * listas para ser pagadas.
 * 
 * Uso:
 *   php artisan db:seed --class=SPPAutorizadaSeeder
 */
class SPPAutorizadaSeeder extends Seeder
{
    /**
     * Número de SPPs autorizadas a generar
     */
    private const CANTIDAD_SPPS = 5;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            echo "   🚀 GENERANDO SPPs AUTORIZADAS\n";
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

            // Obtener proveedores activos (verificados)
            $proveedores = Proveedor::where('estatus', 'verificado')->get();
            
            if ($proveedores->isEmpty()) {
                echo "⚠️ No hay proveedores activos. Creando proveedor de prueba...\n";
                $proveedores = collect([
                    Proveedor::create([
                        'nombre_comercial' => 'Proveedor Test SPP',
                        'razon_social' => 'Proveedor de Prueba SA de CV',
                        'rfc' => 'PPT' . rand(100000, 999999) . 'A01',
                        'estatus' => 'verificado',
                        'is_proveedor_sp' => true,
                    ])
                ]);
            }

            // Obtener empresa constructora
            $empresa = EmpresaConstrucc::where('activo', true)->first();
            
            if (!$empresa) {
                echo "⚠️ No hay empresas constructoras activas. Creando empresa de prueba...\n";
                $empresa = EmpresaConstrucc::create([
                    'nombre' => 'Constructora Test',
                    'razon_social' => 'Constructora de Prueba SA de CV',
                    'rfc' => 'CPR' . rand(100000, 999999) . 'A01',
                    'activo' => true,
                ]);
            }

            // Obtener sucursal
            $sucursal = Sucursal::first();

            $sppsCreadas = [];
            $now = Carbon::now('America/Mazatlan');

            for ($i = 0; $i < self::CANTIDAD_SPPS; $i++) {
                $proveedor = $proveedores->random();
                
                // Buscar cotización existente o null
                $cotizacion = Cotizacion::where('proveedor_id', $proveedor->id)
                    ->where('estatus', 'aceptada')
                    ->inRandomOrder()
                    ->first();

                $montoTotal = $this->generarMontoAleatorio();
                $numeroFolio = SolicitudPago::generarNumeroFolio($proveedor);
                $fechaInicio = $now->copy()->subDays(rand(5, 20));

                $spp = SolicitudPago::create([
                    // Información básica
                    'numero_folio_solicitud' => $numeroFolio,
                    'descripcion_concepto' => $this->generarConcepto($proveedor),
                    
                    // Estado AUTORIZADA
                    'estado_solicitud' => EstadoSP::AUTORIZADA->value,
                    
                    // Relaciones
                    'proveedor_id' => $proveedor->id,
                    'empresa_construcc_id' => $empresa->id,
                    'sucursal_id' => $sucursal?->id,
                    'cotizacion_id' => $cotizacion?->id,
                    
                    // Montos
                    'monto_total' => $montoTotal,
                    'monto_abonado' => 0,
                    'saldo_pendiente' => $montoTotal,
                    'pago_completo' => false,
                    
                    // Factura
                    'ruta_archivo_factura_xml' => $this->generarRutaArchivo('xml', $numeroFolio),
                    'ruta_archivo_factura_pdf' => $this->generarRutaArchivo('pdf', $numeroFolio),
                    'tiene_factura' => true,
                    'folio_factura' => $this->generarFolioFactura(),
                    
                    // Datos XML simulados
                    'datos_factura_xml' => $this->generarDatosFacturaXml($montoTotal, $empresa, $proveedor),
                    
                    // Usuario
                    'usuario_id' => 1,
                    'usuario_nombre' => 'Sistema Automático',
                    
                    // Tipo
                    'tipo' => 'DIRECTA',
                    'tipo_id' => 1,
                    
                    // Autorizaciones (todos los departamentos autorizaron)
                    'dg' => EstadoSolicitud::AUTORIZADA->value,
                    'dg_fecha' => $fechaInicio->copy()->addDays(1),
                    
                    'dt' => EstadoSolicitud::AUTORIZADA->value,
                    'dt_fecha' => $fechaInicio->copy()->addDays(2),
                    
                    'pc' => EstadoSolicitud::AUTORIZADA->value,
                    'pc_fecha' => $fechaInicio->copy()->addDays(3),
                    
                    'si' => EstadoSolicitud::AUTORIZADA->value,
                    'si_fecha' => $fechaInicio->copy()->addDays(4),
                    
                    'da' => EstadoSolicitud::AUTORIZADA->value,
                    'da_fecha' => $fechaInicio->copy()->addDays(5),
                    
                    'ro' => EstadoSolicitud::AUTORIZADA->value,
                    'ro_fecha' => $fechaInicio->copy()->addDays(6),
                    
                    // Verificación
                    'verificada' => true,
                    
                    // Notas
                    'observaciones' => 'SPP autorizada lista para pago - Generada automáticamente',
                    'notas' => 'Todos los departamentos han aprobado la solicitud',
                    
                    // Fechas del proceso
                    'fecha_registro_pendiente' => $fechaInicio,
                    'fecha_inicio_procesamiento' => $fechaInicio->copy()->addDays(1),
                    'fecha_aprobado' => $fechaInicio->copy()->addDays(6),
                    
                    // Timestamps
                    'created_at' => $fechaInicio,
                    'updated_at' => $now,
                ]);

                $sppsCreadas[] = [
                    'id' => $spp->id,
                    'folio' => $spp->numero_folio_solicitud,
                    'proveedor' => $proveedor->nombre_comercial,
                    'monto' => $spp->monto_total,
                ];
            }

            echo "✅ Se generaron " . count($sppsCreadas) . " SPPs autorizadas\n\n";
            echo "📋 RESUMEN:\n";
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            
            foreach ($sppsCreadas as $spp) {
                echo sprintf(
                    "  ID: %-5d | Folio: %-10s | Proveedor: %-30s | Monto: $%s\n",
                    $spp['id'],
                    $spp['folio'],
                    substr($spp['proveedor'], 0, 30),
                    number_format($spp['monto'], 2)
                );
            }
            
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            echo "\n💡 Las SPPs están AUTORIZADAS y listas para registrar pagos.\n";
            echo "   Estado: AUTORIZADA\n";
            echo "   Saldo pendiente: 100% del monto total\n";
            echo "   Todas las autorizaciones departamentales: AUTORIZADAS\n\n";
        });
    }

    /**
     * Genera un monto aleatorio realista para una SPP
     */
    private function generarMontoAleatorio(): float
    {
        $rangos = [
            [1000, 5000],     // Compras pequeñas
            [5000, 15000],    // Compras medianas
            [15000, 50000],   // Compras grandes
            [50000, 150000],  // Compras muy grandes
        ];

        $rango = $rangos[array_rand($rangos)];
        $monto = rand($rango[0], $rango[1]);
        
        // Agregar decimales
        return $monto + (rand(0, 99) / 100);
    }

    /**
     * Genera un concepto descriptivo para la SPP
     */
    private function generarConcepto(Proveedor $proveedor): string
    {
        $conceptos = [
            "Pago por suministro de materiales de construcción - {$proveedor->nombre_comercial}",
            "Liquidación de servicios especializados - {$proveedor->nombre_comercial}",
            "Pago por entrega de equipos y herramientas",
            "Servicios de mantenimiento y reparación",
            "Suministro de materiales según cotización aprobada",
            "Pago por servicios profesionales de construcción",
            "Adquisición de insumos para proyecto",
        ];

        return $conceptos[array_rand($conceptos)];
    }

    /**
     * Genera la ruta de archivo simulada
     */
    private function generarRutaArchivo(string $tipo, string $folio): string
    {
        $año = date('Y');
        $mes = date('m');

        return match ($tipo) {
            'xml' => "uploads/facturas/xml/{$año}/{$mes}/factura_{$folio}.xml",
            'pdf' => "uploads/facturas/pdf/{$año}/{$mes}/factura_{$folio}.pdf",
            default => "uploads/{$tipo}/{$año}/{$mes}/archivo_{$folio}.pdf",
        };
    }

    /**
     * Genera un folio de factura aleatorio
     */
    private function generarFolioFactura(): string
    {
        $serie = chr(rand(65, 90)); // Letra aleatoria A-Z
        $numero = rand(1000, 9999);
        return "{$serie}-{$numero}";
    }

    /**
     * Genera datos simulados de factura XML
     */
    private function generarDatosFacturaXml(float $monto, EmpresaConstrucc $empresa, Proveedor $proveedor): array
    {
        $subtotal = $monto / 1.16; // Considerando IVA del 16%
        $iva = $monto - $subtotal;

        return [
            'version' => '4.0',
            'folio' => $this->generarFolioFactura(),
            'fecha' => Carbon::now()->format('Y-m-d\TH:i:s'),
            'subtotal' => round($subtotal, 2),
            'total' => $monto,
            'moneda' => 'MXN',
            'tipo_comprobante' => 'I',
            'metodo_pago' => 'PPD',
            'forma_pago' => '99',
            'uso_cfdi' => 'G03',
            'rfc_emisor' => $proveedor->rfc,
            'nombre_emisor' => $proveedor->razon_social,
            'rfc_receptor' => $empresa->rfc,
            'nombre_receptor' => $empresa->razon_social,
            'regimen_fiscal_receptor' => '601',
            'codigo_postal_receptor' => '81200',
            'impuestos_trasladados' => [
                [
                    'base' => round($subtotal, 2),
                    'impuesto' => '002',
                    'tipo_factor' => 'Tasa',
                    'tasa' => '0.160000',
                    'importe' => round($iva, 2),
                ]
            ],
        ];
    }
}
