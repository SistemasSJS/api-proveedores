<?php

namespace Database\Seeders;

use App\Enums\EstadoSP;
use App\Models\CuentaBancaria;
use App\Models\EmpresaConstrucc;
use App\Models\PagoSolicitudPago;
use App\Models\PagoSPP;
use App\Models\Proveedor;
use App\Models\SolicitudPago;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PagosSPPTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * IMPORTANTE: Este seeder solo se ejecuta en ambiente LOCAL o DEBUG
     * Genera datos de prueba realistas para SPP, Pagos y sus relaciones
     */
    public function run(): void
    {
        // Validar que solo se ejecute en local o con debug activado
        if (!config('app.debug')) {
            $this->command->error('❌ Este seeder solo puede ejecutarse con APP_DEBUG=true');
            return;
        }

        $this->command->info('🚀 Iniciando seeder de Pagos SPP para pruebas...');

        DB::connection('mysql5')->beginTransaction();

        try {
            // Obtener o crear empresa de construcción de prueba
            $empresa = EmpresaConstrucc::firstOrCreate(
                ['rfc' => 'CTE010101AAA'],
                [
                    'nombre' => 'Constructora Test SA de CV',
                    'direccion' => 'Av. Principal 123, Los Mochis, Sinaloa',
                    'telefono' => '6681234567',
                    'email' => 'contacto@constructoratest.com',
                    'activo' => true,
                ]
            );

            // Verificar si ya existen proveedores SP
            $proveedoresExistentes = Proveedor::where('is_proveedor_sp', true)->get();

            $proveedoresCreados = [];

            if ($proveedoresExistentes->count() >= 3) {
                $this->command->info('ℹ️ Usando proveedores SP existentes...');
                $proveedoresCreados = $proveedoresExistentes->take(5)->all();
            } else {
                $this->command->info('ℹ️ Creando nuevos proveedores de prueba...');

                // Proveedores de prueba con datos realistas
                $proveedoresData = [
                    [
                        'nombre_comercial' => 'Materiales de Construcción del Norte',
                        'razon_social' => 'MATERIALES DEL NORTE SA DE CV',
                        'rfc' => 'MDN850615XY2',
                        'tipo_persona' => 'MORAL',
                        'email' => 'ventas@materialesnorte.com',
                        'telefono' => '6681111111',
                        'regimen_fiscal_clave' => '601',
                        'regimen_fiscal_nombre' => 'General de Ley Personas Morales',
                    ],
                    [
                        'nombre_comercial' => 'Ferretería y Acabados Premium',
                        'razon_social' => 'FERRETERIA PREMIUM SA DE CV',
                        'rfc' => 'FPR920820AB3',
                        'tipo_persona' => 'MORAL',
                        'email' => 'contacto@ferreteriapremiun.com',
                        'telefono' => '6682222222',
                        'regimen_fiscal_clave' => '601',
                        'regimen_fiscal_nombre' => 'General de Ley Personas Morales',
                    ],
                    [
                        'nombre_comercial' => 'Suministros Eléctricos Industriales',
                        'razon_social' => 'SUMINISTROS ELECTRICOS IND SA DE CV',
                        'rfc' => 'SEI880305CD4',
                        'tipo_persona' => 'MORAL',
                        'email' => 'info@suministroselectricos.com',
                        'telefono' => '6683333333',
                        'regimen_fiscal_clave' => '601',
                        'regimen_fiscal_nombre' => 'General de Ley Personas Morales',
                    ],
                    [
                        'nombre_comercial' => 'Plomería y Sanitarios del Pacífico',
                        'razon_social' => 'PLOMERIA PACIFICO SA DE CV',
                        'rfc' => 'PPP910710EF5',
                        'tipo_persona' => 'MORAL',
                        'email' => 'ventas@plomeriapacifico.com',
                        'telefono' => '6684444444',
                        'regimen_fiscal_clave' => '601',
                        'regimen_fiscal_nombre' => 'General de Ley Personas Morales',
                    ],
                    [
                        'nombre_comercial' => 'Pinturas y Recubrimientos Modernos',
                        'razon_social' => 'PINTURAS MODERNAS SA DE CV',
                        'rfc' => 'PMO950525GH6',
                        'tipo_persona' => 'MORAL',
                        'email' => 'contacto@pinturasmodernas.com',
                        'telefono' => '6685555555',
                        'regimen_fiscal_clave' => '601',
                        'regimen_fiscal_nombre' => 'General de Ley Personas Morales',
                    ],
                ];

                foreach ($proveedoresData as $proveedorData) {
                    $proveedor = Proveedor::firstOrCreate(
                        ['rfc' => $proveedorData['rfc']],
                        array_merge($proveedorData, [
                            'estatus' => 'ACTIVO',
                            'is_proveedor_sp' => true,
                            'is_proveedor_catalogo' => false,
                            'tipo_alta' => 2, // Creado por usuario construcción
                            'estado' => 'Sinaloa',
                            'municipio' => 'Los Mochis',
                            'ciudad' => 'Los Mochis',
                            'codigo_postal' => '81200',
                        ])
                    );
                    $proveedoresCreados[] = $proveedor;

                    // Crear cuenta bancaria para cada proveedor si no existe
                    CuentaBancaria::firstOrCreate(
                        [
                            'proveedor_id' => $proveedor->id,
                            'banco_clave' => '012'
                        ],
                        [
                            'alias' => 'Cuenta Principal',
                            'banco_nombre' => 'BBVA Bancomer',
                            'tipo_cuenta' => 'CUENTA_CORRIENTE',
                            'campo_dependiente' => '012345678901234567',
                            'titular_cuenta' => $proveedor->razon_social,
                            'preferida' => true,
                            'estatus' => 'ACTIVA',
                        ]
                    );
                }

                $this->command->info('✅ Proveedores creados: ' . count($proveedoresCreados));
            }

            // Folios de facturas realistas
            $foliosFacturas = [
                'A-12345',
                'B-67890',
                'C-24680',
                'D-13579',
                'E-98765',
                'F-11111',
                'G-22222',
                'H-33333',
                'I-44444',
                'J-55555',
                'K-66666',
                'L-77777',
                'M-88888',
                'N-99999',
                'O-10101',
                'P-20202',
                'Q-30303',
                'R-40404',
                'S-50505',
                'T-60606',
            ];

            // Crear solicitudes de pago con facturas
            $solicitudesCreadas = [];
            $folioIndex = 0;
            $archivosCreados = 0;

            // Crear directorios para las facturas en disco 'private'
            $this->crearDirectoriosFacturas();

            foreach ($proveedoresCreados as $proveedor) {
                // Crear entre 3 y 5 SPP por proveedor
                $numSpp = rand(3, 5);

                for ($i = 0; $i < $numSpp; $i++) {
                    $montoTotal = rand(5000, 50000);
                    $folioFactura = $foliosFacturas[$folioIndex % count($foliosFacturas)];
                    $folioIndex++;

                    // Generar nombres únicos para los archivos (similar a como Laravel los genera)
                    $nombrePdf = \Illuminate\Support\Str::random(40) . '.pdf';
                    $nombreXml = \Illuminate\Support\Str::random(40) . '.xml';
                    
                    // Rutas relativas al storage (como las guarda Laravel)
                    $rutaPdf = "facturas/pdf/{$nombrePdf}";
                    $rutaXml = "facturas/xml/{$nombreXml}";

                    // Crear archivos PDF y XML de prueba en disco 'private'
                    $this->crearFacturaPdf($rutaPdf, $folioFactura, $proveedor->razon_social, $montoTotal);
                    $this->crearFacturaXml($rutaXml, $folioFactura, $proveedor->rfc, $montoTotal);
                    $archivosCreados += 2;

                    $spp = SolicitudPago::create([
                        'proveedor_id' => $proveedor->id,
                        'empresa_construcc_id' => $empresa->id,
                        'numero_folio_solicitud' => sprintf('%04d', $folioIndex),
                        'folio_factura' => $folioFactura,
                        'descripcion_concepto' => $this->getConceptoAleatorio(),
                        'monto_total' => $montoTotal,
                        'monto_abonado' => 0,
                        'saldo_pendiente' => $montoTotal,
                        'estado_solicitud' => EstadoSP::AUTORIZADA->value,
                        'tiene_factura' => true,
                        'verificada' => true,
                        'usuario_id' => 1,
                        'usuario_nombre' => 'Usuario Construcción Test',
                        'fecha_registro_pendiente' => now()->subDays(rand(10, 30)),
                        'fecha_aprobado' => now()->subDays(rand(5, 15)),
                        // Rutas relativas como las guarda Laravel Storage en disco 'private'
                        'ruta_archivo_factura_pdf' => $rutaPdf,
                        'ruta_archivo_factura_xml' => $rutaXml,
                    ]);

                    $solicitudesCreadas[] = $spp;
                }
            }

            $this->command->info('✅ Solicitudes de pago creadas: ' . count($solicitudesCreadas));
            $this->command->info('✅ Archivos de facturas creados: ' . $archivosCreados);

            // Crear pagos (algunos pagos aplicarán a múltiples SPP)
            $pagosCreados = 0;
            $relacionesCreadas = 0;

            // Crear 10 pagos con diferentes configuraciones
            // IMPORTANTE: Solo usar las SPP creadas en este seeder (que tienen archivos)
            $sppDisponibles = collect($solicitudesCreadas);

            for ($i = 0; $i < 10; $i++) {
                $fechaPago = now()->subDays(rand(1, 20));
                
                // Decidir cuántas SPP se pagarán con este pago (1-4)
                $numSppAPagar = rand(1, min(4, $sppDisponibles->count()));
                
                // Refrescar los datos de las SPP desde la BD para tener saldos actualizados
                $sppDisponibles = SolicitudPago::whereIn('id', collect($solicitudesCreadas)->pluck('id'))
                    ->where('saldo_pendiente', '>', 0)
                    ->get();
                
                if ($sppDisponibles->isEmpty()) {
                    $this->command->info('ℹ️ No hay más SPP con saldo pendiente. Finalizando creación de pagos.');
                    break; // No hay más SPP disponibles
                }
                
                // Obtener SPP aleatorias con saldo pendiente
                $sppDelPago = $sppDisponibles
                    ->shuffle()
                    ->take($numSppAPagar);

                if ($sppDelPago->isEmpty()) {
                    continue; // No hay SPP disponibles para este pago
                }

                $proveedorDelPago = $sppDelPago->first()->proveedor;

                // Calcular primero los montos que se aplicarán a cada SPP
                $montosAplicar = [];
                $montoTotalPago = 0;

                foreach ($sppDelPago as $spp) {
                    // Pagar total o parcial (70% probabilidad de pago completo)
                    $pagoCompleto = rand(1, 10) <= 7;
                    
                    if ($pagoCompleto) {
                        $montoAplicado = $spp->saldo_pendiente;
                    } else {
                        // Pago parcial: entre 30% y 80% del saldo pendiente
                        $porcentaje = rand(30, 80) / 100;
                        $montoAplicado = round($spp->saldo_pendiente * $porcentaje, 2);
                        // Asegurar que el monto sea al menos 1000 y no exceda el saldo
                        $montoAplicado = max(1000, min($montoAplicado, $spp->saldo_pendiente));
                    }

                    $montosAplicar[$spp->id] = [
                        'monto' => $montoAplicado,
                        'saldo_inicial' => $spp->saldo_pendiente,
                        'pago_completo' => $pagoCompleto,
                    ];

                    $montoTotalPago += $montoAplicado;
                }

                // Crear el pago con el monto total correcto desde el inicio
                $pago = PagoSPP::create([
                    'empresa_construcc_id' => $empresa->id,
                    'proveedor_id' => $proveedorDelPago->id,
                    'folio_pago_spp_consecutivo' => sprintf('PAGO-%04d', $i + 1),
                    'comprobante_pago' => "comprobantes/pago_" . ($i + 1) . "_" . time() . ".pdf",
                    'fecha_pago' => $fechaPago,
                    'fecha_registro' => $fechaPago,
                    'referencia_pago' => $this->generarReferenciaAleatoria(),
                    'clave_rastreo' => $this->generarClaveRastreo(),
                    'monto_total' => $montoTotalPago, // ✅ Monto correcto desde el inicio
                    'banco_pago' => 'BBVA Bancomer',
                    'banco_destino' => $this->getBancoAleatorio(),
                    'titular_cuenta_destino' => $proveedorDelPago->razon_social,
                    'usuario_registro_id' => 1,
                    'usuario_registro_nombre' => 'Usuario Construcción Test',
                    'cuenta_bancaria_empresa_construcc_id' => 1,
                ]);

                // Aplicar los montos a cada SPP
                foreach ($sppDelPago as $spp) {
                    $datosAplicacion = $montosAplicar[$spp->id];
                    $montoAplicado = $datosAplicacion['monto'];
                    $saldoInicial = $datosAplicacion['saldo_inicial'];
                    $pagoCompleto = $datosAplicacion['pago_completo'];

                    // Crear relación en tabla pivot
                    PagoSolicitudPago::create([
                        'pago_spp_id' => $pago->id,
                        'solicitud_pago_id' => $spp->id,
                        'monto_aplicado' => $montoAplicado,
                        'saldo_inicial' => $saldoInicial,
                        'estado_pago' => PagoSolicitudPago::ESTADO_APLICADO,
                        'fecha_aplicacion' => $fechaPago,
                        'notas' => $pagoCompleto ? 'Pago completo' : 'Pago parcial',
                    ]);

                    // Calcular nuevo saldo
                    $nuevoSaldoPendiente = $saldoInicial - $montoAplicado;
                    $nuevoMontoAbonado = $spp->monto_abonado + $montoAplicado;

                    // Actualizar saldos de la SPP (campos deprecados pero aún en uso)
                    $spp->update([
                        'monto_abonado' => $nuevoMontoAbonado,
                        'saldo_pendiente' => $nuevoSaldoPendiente,
                        'estado_solicitud' => $nuevoSaldoPendiente <= 0
                            ? EstadoSP::PAGADO->value
                            : EstadoSP::AUTORIZADA->value,
                    ]);

                    $relacionesCreadas++;
                }

                $pagosCreados++;
                
                // Mostrar información del pago creado
                $this->command->info(sprintf(
                    '  💰 Pago #%d: $%s aplicado a %d SPP(s)',
                    $i + 1,
                    number_format($montoTotalPago, 2),
                    count($sppDelPago)
                ));
            }

            $this->command->info('✅ Pagos creados: ' . $pagosCreados);
            $this->command->info('✅ Relaciones Pago-SPP creadas: ' . $relacionesCreadas);

            DB::connection('mysql5')->commit();

            $this->command->newLine();
            $this->command->info('✅ ¡Seeder PagosSPPTestSeeder ejecutado correctamente!');
            $this->command->newLine();
            $this->command->table(
                ['Concepto', 'Cantidad'],
                [
                    ['Proveedores', count($proveedoresCreados)],
                    ['Solicitudes de Pago', count($solicitudesCreadas)],
                    ['Pagos', $pagosCreados],
                    ['Relaciones Pago-SPP', $relacionesCreadas],
                ]
            );
            $this->command->newLine();
            $this->command->info('💡 Puedes probar el reporte con:');
            $this->command->info('   GET /api/construcc/reportes/contabilidad');
            $this->command->newLine();
        } catch (\Exception $e) {
            DB::connection('mysql5')->rollBack();
            $this->command->error('❌ Error al ejecutar el seeder: ' . $e->getMessage());
            $this->command->error($e->getTraceAsString());
        }
    }

    /**
     * Generar concepto aleatorio
     */
    private function getConceptoAleatorio(): string
    {
        $conceptos = [
            'Compra de cemento y agregados para obra',
            'Material eléctrico para instalaciones',
            'Tubería y accesorios de plomería',
            'Pintura y materiales de acabados',
            'Herrería y estructuras metálicas',
            'Material de ferretería diverso',
            'Impermeabilizantes y selladores',
            'Madera y materiales de carpintería',
            'Vidrios y cancelería',
            'Pisos y azulejos',
            'Material eléctrico para subestación',
            'Equipos y herramientas especializadas',
            'Material para cimentación',
            'Acero de refuerzo y alambrón',
            'Material para instalaciones hidráulicas',
        ];

        return $conceptos[array_rand($conceptos)];
    }

    /**
     * Generar referencia de pago aleatoria
     */
    private function generarReferenciaAleatoria(): string
    {
        return 'REF' . date('Ymd') . rand(1000, 9999);
    }

    /**
     * Generar clave de rastreo
     */
    private function generarClaveRastreo(): string
    {
        $letras = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $clave = '';
        for ($i = 0; $i < 18; $i++) {
            if ($i % 4 == 0 && $i > 0) {
                $clave .= rand(0, 9);
            } else {
                $clave .= $letras[rand(0, 25)];
            }
        }
        return $clave;
    }

    /**
     * Obtener banco aleatorio
     */
    private function getBancoAleatorio(): string
    {
        $bancos = [
            'BBVA Bancomer',
            'Banamex',
            'Santander',
            'Scotiabank',
            'Banorte',
            'HSBC',
        ];

        return $bancos[array_rand($bancos)];
    }

    /**
     * Crear directorios para almacenar las facturas en disco 'private'
     */
    private function crearDirectoriosFacturas(): void
    {
        Storage::disk('private')->makeDirectory('facturas/pdf');
        Storage::disk('private')->makeDirectory('facturas/xml');
    }

    /**
     * Crear archivo PDF de prueba en disco 'private'
     */
    private function crearFacturaPdf(string $ruta, string $folio, string $razonSocial, float $monto): void
    {
        $contenidoPdf = "%PDF-1.4
1 0 obj
<<
/Type /Catalog
/Pages 2 0 R
>>
endobj
2 0 obj
<<
/Type /Pages
/Kids [3 0 R]
/Count 1
>>
endobj
3 0 obj
<<
/Type /Page
/Parent 2 0 R
/Resources <<
/Font <<
/F1 4 0 R
>>
>>
/MediaBox [0 0 612 792]
/Contents 5 0 R
>>
endobj
4 0 obj
<<
/Type /Font
/Subtype /Type1
/BaseFont /Helvetica
>>
endobj
5 0 obj
<<
/Length 350
>>
stream
BT
/F1 24 Tf
50 700 Td
(FACTURA DE PRUEBA) Tj
0 -40 Td
/F1 12 Tf
(Folio: {$folio}) Tj
0 -30 Td
(Razon Social: {$razonSocial}) Tj
0 -25 Td
(Monto Total: $" . number_format($monto, 2) . ") Tj
0 -25 Td
(Fecha: " . now()->format('Y-m-d') . ") Tj
0 -40 Td
(Este es un archivo PDF de prueba generado) Tj
0 -20 Td
(por el seeder PagosSPPTestSeeder.) Tj
0 -30 Td
(RFC: TEST010101AAA) Tj
ET
endstream
endobj
xref
0 6
0000000000 65535 f
0000000009 00000 n
0000000058 00000 n
0000000115 00000 n
0000000274 00000 n
0000000361 00000 n
trailer
<<
/Size 6
/Root 1 0 R
>>
startxref
762
%%EOF";

        Storage::disk('private')->put($ruta, $contenidoPdf);
    }

    /**
     * Crear archivo XML de prueba en disco 'private'
     */
    private function crearFacturaXml(string $ruta, string $folio, string $rfc, float $monto): void
    {
        $contenidoXml = '<?xml version="1.0" encoding="UTF-8"?>
<cfdi:Comprobante
    xmlns:cfdi="http://www.sat.gob.mx/cfd/4"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://www.sat.gob.mx/cfd/4 http://www.sat.gob.mx/sitio_internet/cfd/4/cfdv40.xsd"
    Version="4.0"
    Serie="A"
    Folio="' . $folio . '"
    Fecha="' . now()->format('Y-m-d\TH:i:s') . '"
    Sello="TEST_SELLO_DIGITAL"
    FormaPago="03"
    NoCertificado="00001000000123456789"
    Certificado="TEST_CERTIFICADO"
    SubTotal="' . number_format($monto / 1.16, 2, '.', '') . '"
    Moneda="MXN"
    Total="' . number_format($monto, 2, '.', '') . '"
    TipoDeComprobante="I"
    Exportacion="01"
    MetodoPago="PUE"
    LugarExpedicion="81200">
    <cfdi:Emisor Rfc="' . $rfc . '" Nombre="Proveedor Test" RegimenFiscal="601"/>
    <cfdi:Receptor Rfc="CTE010101AAA" Nombre="Constructora Test SA de CV" UsoCFDI="G03" RegimenFiscalReceptor="601" DomicilioFiscalReceptor="81200"/>
    <cfdi:Conceptos>
        <cfdi:Concepto
            ClaveProdServ="25101500"
            Cantidad="1"
            ClaveUnidad="ACT"
            Descripcion="Materiales de construcción"
            ValorUnitario="' . number_format($monto / 1.16, 2, '.', '') . '"
            Importe="' . number_format($monto / 1.16, 2, '.', '') . '"
            ObjetoImp="02">
            <cfdi:Impuestos>
                <cfdi:Traslados>
                    <cfdi:Traslado Base="' . number_format($monto / 1.16, 2, '.', '') . '" Impuesto="002" TipoFactor="Tasa" TasaOCuota="0.160000" Importe="' . number_format($monto - ($monto / 1.16), 2, '.', '') . '"/>
                </cfdi:Traslados>
            </cfdi:Impuestos>
        </cfdi:Concepto>
    </cfdi:Conceptos>
    <cfdi:Impuestos TotalImpuestosTrasladados="' . number_format($monto - ($monto / 1.16), 2, '.', '') . '">
        <cfdi:Traslados>
            <cfdi:Traslado Base="' . number_format($monto / 1.16, 2, '.', '') . '" Impuesto="002" TipoFactor="Tasa" TasaOCuota="0.160000" Importe="' . number_format($monto - ($monto / 1.16), 2, '.', '') . '"/>
        </cfdi:Traslados>
    </cfdi:Impuestos>
</cfdi:Comprobante>';

        Storage::disk('private')->put($ruta, $contenidoXml);
    }
}
