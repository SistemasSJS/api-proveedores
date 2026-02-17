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

                    // Rutas relativas al storage (como las guarda Laravel)
                    $rutaPdf = "facturas/pdf/0CYh9ArCRB9RaP4Arvas0bzicrAbisb5fxl6E9SB.pdf";
                    $rutaXml = "facturas/xml/0x5a0kZFaXq5h0cizg39jzhTS4CctY51USUSfLkJ.xml";

                    // Crear archivos PDF y XML de prueba en disco 'private'
                    // $this->crearFacturaPdf($rutaPdf, $folioFactura, $proveedor->razon_social, $montoTotal);
                    // $this->crearFacturaXml($rutaXml, $folioFactura, $proveedor->rfc, $montoTotal);
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
            for ($i = 0; $i < 10; $i++) {
                $fechaPago = now()->subDays(rand(1, 20));
                $proveedorAleatorio = $proveedoresCreados[array_rand($proveedoresCreados)];

                $pago = PagoSPP::create([
                    'empresa_construcc_id' => $empresa->id,
                    'proveedor_id' => $proveedorAleatorio->id,
                    'folio_pago_spp_consecutivo' => sprintf('PAGO-%04d', $i + 1),
                    'comprobante_pago' => "comprobantes/pago_" . ($i + 1) . "_" . time() . ".pdf",
                    'fecha_pago' => $fechaPago,
                    'fecha_registro' => $fechaPago,
                    'referencia_pago' => $this->generarReferenciaAleatoria(),
                    'clave_rastreo' => $this->generarClaveRastreo(),
                    'monto_total' => 0, // Se calculará después
                    'banco_pago' => 'BBVA Bancomer',
                    'banco_destino' => $this->getBancoAleatorio(),
                    'titular_cuenta_destino' => $proveedorAleatorio->razon_social,
                    'usuario_registro_id' => 1,
                    'usuario_registro_nombre' => 'Usuario Construcción Test',
                    'cuenta_bancaria_empresa_construcc_id' => 1,
                ]);

                // Decidir cuántas SPP se pagarán con este pago (1-4)
                $numSppAPagar = rand(1, 4);
                $sppDelProveedor = SolicitudPago::where('proveedor_id', $proveedorAleatorio->id)
                    ->where('saldo_pendiente', '>', 0)
                    ->inRandomOrder()
                    ->limit($numSppAPagar)
                    ->get();

                $montoTotalPago = 0;

                foreach ($sppDelProveedor as $spp) {
                    // Pagar total o parcial
                    $pagoCompleto = rand(0, 1) == 1;
                    $montoAplicado = $pagoCompleto
                        ? $spp->saldo_pendiente
                        : rand(1000, min(5000, $spp->saldo_pendiente));

                    $montoTotalPago += $montoAplicado;

                    // Crear relación en tabla pivot
                    PagoSolicitudPago::create([
                        'pago_spp_id' => $pago->id,
                        'solicitud_pago_id' => $spp->id,
                        'monto_aplicado' => $montoAplicado,
                        'saldo_inicial' => $spp->saldo_pendiente,
                        'estado_pago' => PagoSolicitudPago::ESTADO_APLICADO,
                        'fecha_aplicacion' => $fechaPago,
                        'notas' => $pagoCompleto ? 'Pago completo' : 'Pago parcial',
                    ]);

                    // Actualizar saldos de la SPP
                    $spp->update([
                        'monto_abonado' => $spp->monto_abonado + $montoAplicado,
                        'saldo_pendiente' => $spp->saldo_pendiente - $montoAplicado,
                        'estado_solicitud' => ($spp->saldo_pendiente - $montoAplicado) <= 0
                            ? EstadoSP::PAGADO->value
                            : EstadoSP::AUTORIZADA->value,
                    ]);

                    $relacionesCreadas++;
                }

                // Actualizar monto total del pago
                $pago->update(['monto_total' => $montoTotalPago]);
                $pagosCreados++;
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
}
