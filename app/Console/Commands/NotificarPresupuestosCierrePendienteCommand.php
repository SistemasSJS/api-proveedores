<?php

namespace App\Console\Commands;

use App\Models\Presupuesto;
use App\Notifications\Presupuesto\PresupuestoCierrePendienteNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Presupuestos en estado enviado cuya vigencia vence mañana: recordatorio al equipo emisor.
 */
class NotificarPresupuestosCierrePendienteCommand extends Command
{
    protected $signature = 'presupuestos:notificar-cierre-pendiente';

    protected $description = 'Notificar cierre pendiente de presupuestos enviados (vencen al día siguiente)';

    public function handle(): int
    {
        $manana = Carbon::tomorrow()->startOfDay();

        $presupuestos = Presupuesto::query()
            ->where('estado', Presupuesto::ESTADO_ENVIADO)
            ->whereNotNull('fecha_vencimiento')
            ->whereDate('fecha_vencimiento', $manana)
            ->with('proveedor')
            ->get();

        if ($presupuestos->isEmpty()) {
            $this->info('No hay presupuestos con vencimiento mañana en estado enviado.');

            return self::SUCCESS;
        }

        foreach ($presupuestos as $presupuesto) {
            $proveedor = $presupuesto->proveedor;
            if (! $proveedor) {
                continue;
            }

            try {
                $usuarios = $proveedor->usuariosActivos()->get();
                foreach ($usuarios as $user) {
                    $user->notify(new PresupuestoCierrePendienteNotification($presupuesto));
                }
                $this->line("Notificado equipo del presupuesto #{$presupuesto->numero_presupuesto} (id {$presupuesto->id})");
            } catch (\Throwable $e) {
                Log::warning('Error al notificar cierre pendiente de presupuesto', [
                    'presupuesto_id' => $presupuesto->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("Fallo presupuesto {$presupuesto->id}: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
