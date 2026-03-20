<?php

namespace Database\Seeders;

use App\Enums\EstadoSP;
use App\Models\EmpresaConstrucc;
use App\Models\Proveedor;
use App\Models\SolicitudPago;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SppEstadoContadoresSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $proveedor = Proveedor::query()->first();
            $empresa = EmpresaConstrucc::query()->where('activo', true)->first();

            if (! $proveedor || ! $empresa) {
                $this->command->error('No hay proveedor o empresa constructora activa para generar SPP de prueba.');
                return;
            }

            $inicioMes = Carbon::now()->startOfMonth()->addDays(2)->setTime(10, 0, 0);

            $escenarios = [
                // Pendientes (verificada=true + estado=pendiente)
                ['estado' => EstadoSP::PENDIENTE->value, 'verificada' => true, 'cantidad' => 4, 'sin_factura' => 1],
                // Autorizadas (para validar rol_id=3 como "pendientes")
                ['estado' => EstadoSP::AUTORIZADA->value, 'verificada' => true, 'cantidad' => 6, 'sin_factura' => 1],
                // Rechazadas
                ['estado' => EstadoSP::RECHAZADA->value, 'verificada' => true, 'cantidad' => 3, 'sin_factura' => 0],
                // Pagadas
                ['estado' => EstadoSP::PAGADO->value, 'verificada' => true, 'cantidad' => 5, 'sin_factura' => 0],
                // Por validar (verificada=false)
                ['estado' => EstadoSP::PENDIENTE->value, 'verificada' => false, 'cantidad' => 4, 'sin_factura' => 0],
            ];

            $creadas = 0;
            $indiceGlobal = 1;

            foreach ($escenarios as $escenario) {
                for ($i = 1; $i <= $escenario['cantidad']; $i++) {
                    $createdAt = $inicioMes->copy()->addDays($indiceGlobal);
                    $sinFactura = $i <= $escenario['sin_factura'];

                    $folio = sprintf(
                        'SPP-TEST-%s-%03d',
                        Carbon::now()->format('Ym'),
                        $indiceGlobal
                    );

                    SolicitudPago::create([
                        'numero_folio_solicitud' => $folio,
                        'descripcion_concepto' => "SPP de prueba para contadores ({$escenario['estado']})",
                        'estado_solicitud' => $escenario['estado'],
                        'proveedor_id' => $proveedor->id,
                        'empresa_construcc_id' => $empresa->id,
                        'usuario_id' => 1,
                        'usuario_nombre' => 'Seeder Contadores',
                        'verificada' => $escenario['verificada'],
                        'monto_total' => 1000 + ($indiceGlobal * 100),
                        'monto_abonado' => $escenario['estado'] === EstadoSP::PAGADO->value ? (1000 + ($indiceGlobal * 100)) : 0,
                        'saldo_pendiente' => $escenario['estado'] === EstadoSP::PAGADO->value ? 0 : (1000 + ($indiceGlobal * 100)),
                        'pago_completo' => $escenario['estado'] === EstadoSP::PAGADO->value,
                        'folio_factura' => $sinFactura ? null : 'FAC-' . Carbon::now()->format('ym') . '-' . $indiceGlobal,
                        'ruta_archivo_factura_xml' => 'facturas/xml/test_' . $folio . '.xml',
                        'ruta_archivo_factura_pdf' => 'facturas/pdf/test_' . $folio . '.pdf',
                        'fecha_registro_pendiente' => $createdAt,
                        'fecha_aprobado' => $escenario['estado'] === EstadoSP::AUTORIZADA->value ? $createdAt->copy()->addDay() : null,
                        'fecha_rechazo' => $escenario['estado'] === EstadoSP::RECHAZADA->value ? $createdAt->copy()->addDay() : null,
                        'fecha_pago' => $escenario['estado'] === EstadoSP::PAGADO->value ? $createdAt->copy()->addDays(2) : null,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);

                    $creadas++;
                    $indiceGlobal++;
                }
            }

            $this->command->info("SPP de prueba creadas (este mes): {$creadas}");
            $this->command->line('Esperado en conteo (periodo=mes):');
            $this->command->line('- por_validar: 4');
            $this->command->line('- pendiente (normal): 4');
            $this->command->line('- pendiente (rol_id=3): 6');
            $this->command->line('- autorizadas: 6');
            $this->command->line('- rechazadas: 3');
            $this->command->line('- pagadas: 5');
            $this->command->line('- sin_factura: 2');
        });
    }
}

