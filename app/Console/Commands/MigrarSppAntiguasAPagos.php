<?php

namespace App\Console\Commands;

use App\Models\EmpresaConstrucc;
use App\Models\PagoSolicitudPago;
use App\Models\PagoSPP;
use App\Models\SolicitudPago;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Comando para migrar SPP antiguas al sistema de pagos.
 * 
 * Este comando identifica las SPP que tienen saldo_pendiente = 0 pero no tienen
 * registros de pago en el nuevo módulo de pagos, y les crea registros correspondientes
 * para mantener la compatibilidad con el sistema actual.
 * 
 * Uso:
 *   php artisan spp:migrar-antiguas [--dry-run] [--chunk=100]
 */
class MigrarSppAntiguasAPagos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spp:migrar-antiguas 
                            {--dry-run : Ejecutar en modo prueba sin hacer cambios}
                            {--chunk=100 : Cantidad de SPP a procesar por lote}
                            {--force : Forzar ejecución sin confirmación}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migra SPP antiguas (sin registros de pago) al nuevo sistema de pagos para mantener compatibilidad';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Iniciando migración de SPP antiguas al sistema de pagos...');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        $chunk = (int) $this->option('chunk');
        $force = $this->option('force');

        if ($dryRun) {
            $this->warn('⚠️  MODO DRY-RUN: No se realizarán cambios en la base de datos');
            $this->newLine();
        }

        // Buscar SPP candidatas para migración
        $this->info('🔍 Buscando SPP sin registros de pago...');

        $sppCandidatas = $this->obtenerSppCandidatas();

        if ($sppCandidatas->isEmpty()) {
            $this->info('✅ No se encontraron SPP que requieran migración.');
            return Command::SUCCESS;
        }

        $total = $sppCandidatas->count();
        $this->info("📊 Se encontraron {$total} SPP candidatas para migración");
        $this->newLine();

        // Mostrar estadísticas
        $this->mostrarEstadisticas($sppCandidatas);
        $this->newLine();

        // Solicitar confirmación si no se usa --force
        if (!$force && !$dryRun) {
            if (!$this->confirm('¿Deseas continuar con la migración?', true)) {
                $this->warn('❌ Migración cancelada por el usuario.');
                return self::FAILURE;
            }
            $this->newLine();
        }

        // Procesar SPP en lotes
        $this->info('⚙️  Procesando SPP...');
        $this->newLine();

        $resultados = $this->procesarSpp($sppCandidatas, $dryRun, $chunk);

        // Mostrar resultados finales
        $this->newLine();
        $this->mostrarResultados($resultados, $dryRun);

        return Command::SUCCESS;
    }

    /**
     * Obtener SPP candidatas para migración
     */
    private function obtenerSppCandidatas()
    {
        return SolicitudPago::query()
            ->where('estado_solicitud', '=', 'PAGADO')  // SPP que están marcadas como pagadas o con monto_abonado > 0
            ->whereDoesntHave('pagos')                  // Que no tengan registros en el nuevo sistema de pagos
            ->where('monto_total', '>', 0)              // Que tengan monto total
            ->orderBy('created_at', 'asc')              // Ordenar por fecha de registro
            ->with(['proveedor', 'empresaConstrucc'])
            ->get();
    }

    /**
     * Mostrar estadísticas de las SPP encontradas
     */
    private function mostrarEstadisticas($sppCandidatas): void
    {
        $totalMonto = $sppCandidatas->sum('monto_total');

        $this->table(
            ['Concepto', 'Valor'],
            [
                ['Total SPP', number_format($sppCandidatas->count())],
                ['Monto total', '$' . number_format($totalMonto, 2)],
            ]
        );
    }

    /**
     * Procesar las SPP candidatas
     */
    private function procesarSpp($sppCandidatas, bool $dryRun, int $chunk): array
    {
        $resultados = [
            'exitosas' => 0,
            'errores' => 0,
            'omitidas' => 0,
            'detalles' => [],
        ];

        $bar = $this->output->createProgressBar($sppCandidatas->count());
        $bar->start();

        foreach ($sppCandidatas->chunk($chunk) as $lote) {
            foreach ($lote as $spp) {
                try {
                    if ($dryRun) {
                        // En modo dry-run solo validamos
                        $this->validarSpp($spp);
                        $resultados['exitosas']++;
                    } else {
                        // Procesar la migración real
                        $this->migrarSpp($spp);
                        $resultados['exitosas']++;
                    }
                } catch (\Exception $e) {
                    $resultados['errores']++;
                    $resultados['detalles'][] = [
                        'spp_id' => $spp->id,
                        'folio' => $spp->numero_folio_solicitud,
                        'error' => $e->getMessage(),
                    ];

                    Log::error('Error al migrar SPP', [
                        'spp_id' => $spp->id,
                        'folio' => $spp->numero_folio_solicitud,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }

                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();

        return $resultados;
    }

    /**
     * Validar que una SPP puede ser migrada (modo dry-run)
     */
    private function validarSpp(SolicitudPago $spp): void
    {
        // Validar que tenga proveedor
        if (!$spp->proveedor_id || !$spp->proveedor) {
            throw new \Exception("SPP sin proveedor asociado");
        }

        // Validar que tenga empresa constructora
        if (!$spp->empresa_construcc_id || !$spp->empresaConstrucc) {
            throw new \Exception("SPP sin empresa constructora asociada");
        }

        // Validar montos
        if ($spp->monto_total <= 0) {
            throw new \Exception("SPP con monto total inválido");
        }
    }

    /**
     * Migrar una SPP al nuevo sistema de pagos
     */
    private function migrarSpp(SolicitudPago $spp): void
    {
        DB::connection('mysql5')->transaction(function () use ($spp) {
            // Validaciones previas
            $this->validarSpp($spp);

            // Determinar el monto a registrar

            //COMPROBANTE DATA
            $montoRegistrado =  $spp->monto_total;
            $bancoPago =  $spp->banco_pago;
            $claveRastreoPago = $spp->clave_rastreo_pago;

            // $fechaPago = $spp->fecha_pago
            //     ? (is_string($spp->fecha_pago) ? strtotime($spp->fecha_pago) : $spp->fecha_pago)
            //     : $spp->created_at->timestamp;

            // $fechaComprobante = $spp->fecha_comprobante_pago
            //     ? (is_string($spp->fecha_comprobante_pago) ? strtotime($spp->fecha_comprobante_pago) : $spp->fecha_comprobante_pago)
            //     : $spp->created_at->timestamp;

            // Generar folio único para el pago
            $folioPago = $this->generarFolioPagoHistorico($spp);

            // Crear el registro de pago
            $pago = PagoSPP::create([
                'empresa_construcc_id' => $spp->empresa_construcc_id,
                'proveedor_id' => $spp->proveedor_id,
                'folio_pago_spp_consecutivo' => $folioPago,
                // datos copmprobante 
                'comprobante_pago' => $spp->ruta_archivo_comprobante_pago ?? '',
                'fecha_pago' => $spp->fecha_pago ?? $spp->created_at,
                'fecha_registro' => $spp->fecha_comprobante_pago ?? $spp->fecha_pago ?? $spp->created_at,
                'referencia_pago' => $claveRastreoPago ?? '',
                'clave_rastreo' => $claveRastreoPago,
                'monto_total' => $montoRegistrado,
                'banco_pago' => $bancoPago,
                'banco_destino' => 'HISTORICO',
                'titular_cuenta_destino' => $spp->proveedor->razon_social ?? 'PROVEEDOR HISTORICO',
                'usuario_registro_id' => $spp->usuario_id ?? 1,
                'usuario_registro_nombre' => $spp->usuario_nombre ?? 'Sistema de Migración',
                'cuenta_bancaria_empresa_construcc_id' => $spp->cuenta_bancaria_empresa_construcc_id,
                'observaciones' => 'Pago migrado automáticamente desde SPP antigua. Fecha original: ' . $spp->created_at->format('Y-m-d'),
            ]);

            // Crear la relación en la tabla pivot
            PagoSolicitudPago::create([
                'pago_spp_id' => $pago->id,
                'solicitud_pago_id' => $spp->id,
                'monto_aplicado' => $montoRegistrado,
                'saldo_inicial' => $spp->monto_total,
                'estado_pago' => $montoRegistrado >= $spp->monto_total
                    ? PagoSolicitudPago::ESTADO_COMPLETADO
                    : PagoSolicitudPago::ESTADO_PARCIAL,
                'fecha_aplicacion' => $spp->fecha_pago ?? $spp->created_at,
                'notas' => 'Registro migrado automáticamente. SPP pagada antes del módulo de pagos.',
            ]);

            // Actualizar los campos deprecados de la SPP para consistencia
            $nuevoSaldo = $spp->monto_total - $montoRegistrado;
            $spp->update([
                'monto_abonado' => $montoRegistrado,
                'saldo_pendiente' => $nuevoSaldo,
                'pago_completo' => $nuevoSaldo <= 0,
                'estado_solicitud' => $nuevoSaldo <= 0 ? 'PAGADO' : $spp->estado_solicitud,
            ]);
        });
    }

    /**
     * Generar folio para pago histórico
     */
    private function generarFolioPagoHistorico(SolicitudPago $spp): string
    {
        /** @var EmpresaConstrucc $empresa */
        $empresa = $spp->empresaConstrucc;

        if (!$empresa) {
            return 'HIST-' . $spp->numero_folio_solicitud . '-' . now()->format('ymd');
        }

        // Intentar obtener el folio siguiente de la empresa
        try {
            return $empresa->obtenerFolioSiguientePagoSPP();
        } catch (\Exception $e) {
            // Si falla, usar un folio histórico
            return 'HIST-' . $spp->numero_folio_solicitud . '-' . now()->format('ymd');
        }
    }

    /**
     * Generar ruta de comprobante histórico
     */
    private function generarRutaComprobanteHistorico(SolicitudPago $spp): string
    {
        $this->line('Generando ruta de comprobante histórico para SPP: ' . $spp->id);
        $this->line('Ruta de comprobante: ' . $spp->ruta_archivo_comprobante_pago);
        $this->line('--------------------------------');
        $this->ask('Presiona Enter para continuar');

        // Si la SPP tiene comprobante, usarlo
        if ($spp->ruta_archivo_comprobante_pago) {
            return $spp->ruta_archivo_comprobante_pago;
        }

        // Si no, generar una ruta placeholder
        return 'comprobantes/historicos/spp_' . $spp->id . '_migrado.pdf';
    }

    /**
     * Mostrar resultados finales
     */
    private function mostrarResultados(array $resultados, bool $dryRun): void
    {
        $modoTexto = $dryRun ? '(MODO PRUEBA)' : '(CAMBIOS APLICADOS)';

        $this->info("✅ Migración completada {$modoTexto}");
        $this->newLine();

        $this->table(
            ['Concepto', 'Cantidad'],
            [
                ['✓ SPP migradas exitosamente', $resultados['exitosas']],
                ['✗ SPP con errores', $resultados['errores']],
                ['○ SPP omitidas', $resultados['omitidas']],
            ]
        );

        // Mostrar errores si los hay
        if ($resultados['errores'] > 0 && !empty($resultados['detalles'])) {
            $this->newLine();
            $this->error('⚠️  Errores encontrados:');
            $this->newLine();

            foreach ($resultados['detalles'] as $detalle) {
                $this->line("   SPP #{$detalle['spp_id']} (Folio: {$detalle['folio']}): {$detalle['error']}");
            }
        }

        // Recomendaciones finales
        if (!$dryRun && $resultados['exitosas'] > 0) {
            $this->newLine();
            $this->info('💡 Recomendaciones:');
            $this->line('   • Verifica los registros migrados en el módulo de pagos');
            $this->line('   • Revisa el log de errores si hubo fallos: storage/logs/laravel.log');
            $this->line('   • Puedes consultar las SPP migradas con: SolicitudPago::has(\'pagos\')->get()');
        }

        if ($dryRun) {
            $this->newLine();
            $this->info('💡 Para aplicar los cambios, ejecuta el comando sin --dry-run:');
            $this->line('   php artisan spp:migrar-antiguas');
        }
    }
}
