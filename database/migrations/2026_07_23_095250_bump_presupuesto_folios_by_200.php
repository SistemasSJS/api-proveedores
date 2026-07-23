<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const OFFSET = 200;

    /**
     * Suma 200 al consecutivo de numero_presupuesto (PRES-XXXX) y alinea
     * proveedores.consecutivo_presupuesto_siguiente para evitar colisiones.
     */
    public function up(): void
    {
        if (! Schema::hasTable('presupuestos') || ! Schema::hasColumn('presupuestos', 'numero_presupuesto')) {
            return;
        }

        $updated = 0;
        $skipped = 0;

        DB::table('presupuestos')
            ->select(['id', 'proveedor_id', 'numero_presupuesto'])
            ->orderBy('id')
            ->chunkById(200, function ($rows) use (&$updated, &$skipped) {
                foreach ($rows as $row) {
                    $numero = trim((string) ($row->numero_presupuesto ?? ''));
                    if (! preg_match('/^(PRES-)(\d+)$/i', $numero, $m)) {
                        $skipped++;
                        continue;
                    }

                    $prefix = strtoupper($m[1]);
                    $consecutivo = (int) $m[2] + self::OFFSET;
                    $nuevo = $prefix . str_pad((string) $consecutivo, max(4, strlen($m[2])), '0', STR_PAD_LEFT);

                    DB::table('presupuestos')
                        ->where('id', $row->id)
                        ->update(['numero_presupuesto' => $nuevo]);

                    $updated++;
                }
            });

        $proveedoresAlineados = 0;
        if (Schema::hasTable('proveedores') && Schema::hasColumn('proveedores', 'consecutivo_presupuesto_siguiente')) {
            $proveedorIds = DB::table('presupuestos')
                ->whereNotNull('proveedor_id')
                ->distinct()
                ->pluck('proveedor_id');

            foreach ($proveedorIds as $proveedorId) {
                $maxConsecutivo = 0;
                $folios = DB::table('presupuestos')
                    ->where('proveedor_id', $proveedorId)
                    ->pluck('numero_presupuesto');

                foreach ($folios as $folio) {
                    if (preg_match('/^PRES-(\d+)$/i', (string) $folio, $m)) {
                        $maxConsecutivo = max($maxConsecutivo, (int) $m[1]);
                    }
                }

                $actual = (int) (DB::table('proveedores')
                    ->where('id', $proveedorId)
                    ->value('consecutivo_presupuesto_siguiente') ?? 1);

                $siguiente = max($actual, $maxConsecutivo + 1);
                if ($siguiente !== $actual) {
                    DB::table('proveedores')
                        ->where('id', $proveedorId)
                        ->update(['consecutivo_presupuesto_siguiente' => $siguiente]);
                    $proveedoresAlineados++;
                }
            }
        }

        $this->logMigrations('info', 'bump_presupuesto_folios_by_200', [
            'offset' => self::OFFSET,
            'presupuestos_actualizados' => $updated,
            'presupuestos_omitidos' => $skipped,
            'proveedores_alineados' => $proveedoresAlineados,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('presupuestos') || ! Schema::hasColumn('presupuestos', 'numero_presupuesto')) {
            return;
        }

        $updated = 0;
        $skipped = 0;

        DB::table('presupuestos')
            ->select(['id', 'numero_presupuesto'])
            ->orderBy('id')
            ->chunkById(200, function ($rows) use (&$updated, &$skipped) {
                foreach ($rows as $row) {
                    $numero = trim((string) ($row->numero_presupuesto ?? ''));
                    if (! preg_match('/^(PRES-)(\d+)$/i', $numero, $m)) {
                        $skipped++;
                        continue;
                    }

                    $consecutivo = (int) $m[2] - self::OFFSET;
                    if ($consecutivo < 1) {
                        $skipped++;
                        continue;
                    }

                    $prefix = strtoupper($m[1]);
                    $nuevo = $prefix . str_pad((string) $consecutivo, max(4, strlen($m[2])), '0', STR_PAD_LEFT);

                    DB::table('presupuestos')
                        ->where('id', $row->id)
                        ->update(['numero_presupuesto' => $nuevo]);

                    $updated++;
                }
            });

        $this->logMigrations('info', 'bump_presupuesto_folios_by_200_down', [
            'offset' => self::OFFSET,
            'presupuestos_actualizados' => $updated,
            'presupuestos_omitidos' => $skipped,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function logMigrations(string $level, string $message, array $payload = []): void
    {
        try {
            Log::channel('migrations')->{$level}($message, $payload);
        } catch (\Throwable $e) {
            Log::{$level}($message, $payload);
        }
    }
};
