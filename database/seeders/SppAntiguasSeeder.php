<?php

namespace Database\Seeders;

use App\Enums\EstadoSP;
use App\Models\EmpresaConstrucc;
use App\Models\Proveedor;
use App\Models\SolicitudPago;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder para crear SPP "antiguas" (sin registros de pago)
 * para simular el escenario de SPP creadas antes del módulo de pagos.
 * 
 * Este seeder crea SPP que están marcadas como PAGADO pero no tienen
 * registros en las tablas de pagos (pagos_spp y pago_solicitud_pago).
 * 
 * Uso:
 *   php artisan db:seed --class=SppAntiguasSeeder
 */
class SppAntiguasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Validar que solo se ejecute en local o con debug activado
        if (!config('app.debug')) {
            $this->command->error('❌ Este seeder solo puede ejecutarse con APP_DEBUG=true');
            return;
        }

        $this->command->info('🚀 Iniciando seeder de SPP Antiguas (sin módulo de pagos)...');

        DB::connection('mysql5')->beginTransaction();

        try {
            // Obtener o crear empresa de construcción
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

            // Obtener o crear proveedor
            $proveedor = Proveedor::firstOrCreate(
                ['rfc' => 'PAH850615XY9'],
                [
                    'nombre_comercial' => 'Proveedor Antiguo Histórico',
                    'razon_social' => 'PROVEEDOR ANTIGUO HISTORICO SA DE CV',
                    'tipo_persona' => 'MORAL',
                    'email' => 'contacto@proveedorantiguo.com',
                    'telefono' => '6689999999',
                    'regimen_fiscal_clave' => '601',
                    'regimen_fiscal_nombre' => 'General de Ley Personas Morales',
                    // 'estatus' => 'activo',
                    'is_proveedor_sp' => true,
                    'is_proveedor_catalogo' => false,
                    'tipo_alta' => 2,
                    'estado' => 'Sinaloa',
                    'municipio' => 'Los Mochis',
                    'ciudad' => 'Los Mochis',
                    'codigo_postal' => '81200',
                ]
            );

            $this->command->info('✅ Empresa y proveedor configurados');

            // Crear SPP "antiguas" con diferentes escenarios
            $sppCreadas = [];

            // Escenario 1: SPP completamente pagada (monto_abonado = monto_total)
            $sppCreadas[] = $this->crearSppAntigua($empresa, $proveedor, [
                'descripcion_concepto' => 'SPP Antigua - Pago Completo (antes del módulo)',
                'monto_total' => 50000,
                'monto_abonado' => 50000,
                'saldo_pendiente' => 0,
                'estado_solicitud' => EstadoSP::PAGADO->value,
                'pago_completo' => true,
                'fecha_registro' => now()->subMonths(6),
            ]);

            // Escenario 2: SPP parcialmente pagada
            $sppCreadas[] = $this->crearSppAntigua($empresa, $proveedor, [
                'descripcion_concepto' => 'SPP Antigua - Pago Parcial (antes del módulo)',
                'monto_total' => 75000,
                'monto_abonado' => 45000,
                'saldo_pendiente' => 30000,
                'estado_solicitud' => EstadoSP::AUTORIZADA->value,
                'pago_completo' => false,
                'fecha_registro' => now()->subMonths(5),
            ]);

            // Escenario 3: SPP marcada como pagada pero sin monto_abonado
            $sppCreadas[] = $this->crearSppAntigua($empresa, $proveedor, [
                'descripcion_concepto' => 'SPP Antigua - Marcada como pagada sin abonos',
                'monto_total' => 30000,
                'monto_abonado' => 0,
                'saldo_pendiente' => 0,
                'estado_solicitud' => EstadoSP::PAGADO->value,
                'pago_completo' => true,
                'fecha_registro' => now()->subMonths(4),
            ]);

            // Escenario 4: Múltiples SPP del mismo proveedor
            for ($i = 1; $i <= 3; $i++) {
                $monto = rand(10000, 40000);
                $sppCreadas[] = $this->crearSppAntigua($empresa, $proveedor, [
                    'descripcion_concepto' => "SPP Antigua #{$i} - Serie histórica",
                    'monto_total' => $monto,
                    'monto_abonado' => $monto,
                    'saldo_pendiente' => 0,
                    'estado_solicitud' => EstadoSP::PAGADO->value,
                    'pago_completo' => true,
                    'fecha_registro' => now()->subMonths(3 + $i),
                ]);
            }

            // Escenario 5: SPP con comprobante de pago adjunto
            $sppCreadas[] = $this->crearSppAntigua($empresa, $proveedor, [
                'descripcion_concepto' => 'SPP Antigua - Con comprobante adjunto',
                'monto_total' => 60000,
                'monto_abonado' => 60000,
                'saldo_pendiente' => 0,
                'estado_solicitud' => EstadoSP::PAGADO->value,
                'pago_completo' => true,
                'ruta_archivo_comprobante_pago' => 'comprobantes/historicos/comprobante_antiguo.pdf',
                'fecha_registro' => now()->subMonths(2),
            ]);

            DB::connection('mysql5')->commit();

            $this->command->newLine();
            $this->command->info('✅ ¡Seeder SppAntiguasSeeder ejecutado correctamente!');
            $this->command->newLine();

            $this->command->table(
                ['Concepto', 'Cantidad'],
                [
                    ['SPP Antiguas Creadas', count($sppCreadas)],
                    ['Monto Total', '$' . number_format(collect($sppCreadas)->sum('monto_total'), 2)],
                    ['Monto Abonado', '$' . number_format(collect($sppCreadas)->sum('monto_abonado'), 2)],
                ]
            );

            $this->command->newLine();
            $this->command->info('💡 Para migrar estas SPP al módulo de pagos:');
            $this->command->line('   1. Ejecuta en modo prueba: php artisan spp:migrar-antiguas --dry-run');
            $this->command->line('   2. Si todo está correcto: php artisan spp:migrar-antiguas');
            $this->command->newLine();
        } catch (\Exception $e) {
            DB::connection('mysql5')->rollBack();
            $this->command->error('❌ Error al ejecutar el seeder: ' . $e->getMessage());
            $this->command->error($e->getTraceAsString());
        }
    }

    /**
     * Crear una SPP "antigua" (sin registros en el módulo de pagos)
     */
    private function crearSppAntigua(
        EmpresaConstrucc $empresa,
        Proveedor $proveedor,
        array $datos
    ): SolicitudPago {
        $fechaRegistro = $datos['fecha_registro'] ?? now()->subMonths(3);

        // Inicializar saldo_pendiente si no se proporciona
        $montoTotal = $datos['monto_total'];
        $montoAbonado = $datos['monto_abonado'] ?? 0;
        $saldoPendiente = $datos['saldo_pendiente'] ?? ($montoTotal - $montoAbonado);

        $spp = SolicitudPago::create([
            'proveedor_id' => $proveedor->id,
            'empresa_construcc_id' => $empresa->id,
            'numero_folio_solicitud' => 'HIST-' . strtoupper(\Illuminate\Support\Str::random(8)),
            'folio_factura' => 'F-HIST-' . rand(10000, 99999),
            'descripcion_concepto' => $datos['descripcion_concepto'],
            'monto_total' => $montoTotal,
            'monto_abonado' => $montoAbonado,
            'saldo_pendiente' => $saldoPendiente,
            'pago_completo' => $datos['pago_completo'] ?? ($saldoPendiente <= 0),
            'estado_solicitud' => $datos['estado_solicitud'],
            'tiene_factura' => false, // SPP antiguas típicamente sin factura cargada
            'verificada' => true,
            'usuario_id' => 1,
            'usuario_nombre' => 'Usuario Sistema Antiguo',
            'fecha_registro_pendiente' => $fechaRegistro->format('Y-m-d H:i:s'),
            'fecha_aprobado' => $fechaRegistro->copy()->addDays(1)->format('Y-m-d H:i:s'),
            'fecha_pago' => $montoAbonado > 0
                ? $fechaRegistro->copy()->addDays(5)->format('Y-m-d H:i:s')
                : null,
            'ruta_archivo_comprobante_pago' => $datos['ruta_archivo_comprobante_pago'] ?? null,
            // No incluir rutas de factura para simular SPP sin facturas cargadas
            'ruta_archivo_factura_pdf' => null,
            'ruta_archivo_factura_xml' => null,
            'created_at' => $fechaRegistro,
            'updated_at' => $fechaRegistro,
        ]);

        return $spp;
    }
}
