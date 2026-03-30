<?php

namespace App\Http\Controllers;

use App\Http\Resources\Presupuesto\PresupuestoPublicResource;
use App\Models\Presupuesto;
use App\Notifications\Presupuesto\PresupuestoAceptadoNotification;
use App\Notifications\Presupuesto\PresupuestoEnviadoNotification;
use App\Notifications\Presupuesto\PresupuestoRechazadoNotification;
use App\Support\PresupuestoPdf;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PresupuestoPublicController extends Controller
{
    use ApiResponse;

    /**
     * Obtiene el presupuesto por token público (sin autenticación).
     */
    public function show(string $token): JsonResponse
    {
        Presupuesto::actualizarVencidos();

        $presupuesto = Presupuesto::query()
            ->where('token_publico', $token)
            ->with(Presupuesto::eagerLodable())
            ->first();

        if (! $presupuesto) {
            return $this->error('Presupuesto no encontrado o enlace inválido.', null, 404);
        }

        $presupuesto->asegurarTokenPublico();

        return $this->success(new PresupuestoPublicResource($presupuesto));
    }

    /**
     * Descarga el PDF del presupuesto por token.
     */
    public function descargarPdf(string $token): JsonResponse|\Symfony\Component\HttpFoundation\Response
    {
        $presupuesto = Presupuesto::query()
            ->where('token_publico', $token)
            ->with(Presupuesto::eagerLodable())
            ->first();

        if (! $presupuesto) {
            return $this->error('Presupuesto no encontrado o enlace inválido.', null, 404);
        }

        try {
            return PresupuestoPdf::generarPdf($presupuesto);
        } catch (\Throwable $e) {
            return $this->error('No fue posible generar el PDF.', [$e->getMessage()], 500);
        }
    }

    /**
     * Acepta el presupuesto (solo si está en estado enviado).
     */
    public function aceptar(string $token): JsonResponse
    {
        $presupuesto = Presupuesto::query()
            ->with('proveedor')
            ->where('token_publico', $token)
            ->first();

        if (! $presupuesto) {
            return $this->error('Presupuesto no encontrado o enlace inválido.', null, 404);
        }

        if ($presupuesto->estado !== Presupuesto::ESTADO_ENVIADO) {
            return $this->error(
                'Solo se puede aceptar un presupuesto que esté en estado enviado.',
                ['estado_actual' => $presupuesto->estado],
                422
            );
        }

        $presupuesto->estado = Presupuesto::ESTADO_ACEPTADO;
        $presupuesto->save();

        $proveedor = $presupuesto->proveedor;
        if ($proveedor) {
            // $usuarios = $proveedor->usuariosActivos()->get();
            // foreach ($usuarios as $user) {
            //     $user->notify(new PresupuestoAceptadoNotification($presupuesto));
            // }
            // $primeraNotif = $usuarios->isNotEmpty()
            //     ? $usuarios->first()->notifications()
            //         ->where('type', PresupuestoAceptadoNotification::class)
            //         ->latest()
            //         ->first()
            //     : null;
            // $presupuesto->addNotification($primeraNotif?->id);
            $usuarioPrincipal = $proveedor->usuarioPrincipal();

            $primeraNotif = $usuarioPrincipal
                ? $usuarioPrincipal->notifications()
                ->where('type', PresupuestoEnviadoNotification::class)
                ->latest()
                ->first()
                : null;

            $presupuesto->addNotification($primeraNotif?->id);
        }

        return $this->success(
            new PresupuestoPublicResource($presupuesto->fresh(Presupuesto::eagerLodable())),
            'Presupuesto aceptado correctamente.'
        );
    }

    /**
     * Rechaza el presupuesto (solo si está en estado enviado).
     */
    public function rechazar(Request $request, string $token): JsonResponse
    {
        $presupuesto = Presupuesto::query()
            ->with('proveedor')
            ->where('token_publico', $token)
            ->first();

        if (! $presupuesto) {
            return $this->error('Presupuesto no encontrado o enlace inválido.', null, 404);
        }

        if ($presupuesto->estado !== Presupuesto::ESTADO_ENVIADO) {
            return $this->error(
                'Solo se puede rechazar un presupuesto que esté en estado enviado.',
                ['estado_actual' => $presupuesto->estado],
                422
            );
        }

        $validator = Validator::make($request->all(), [
            'motivo' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return $this->error('Datos inválidos.', $validator->errors()->all(), 422);
        }

        $motivo = $request->input('motivo');
        $presupuesto->estado = Presupuesto::ESTADO_RECHAZADO;
        if ($motivo) {
            $presupuesto->motivo_rechazo = trim($motivo);
        }
        $presupuesto->save();

        $proveedor = $presupuesto->proveedor;
        if ($proveedor) {
            $usuarios = $proveedor->usuariosActivos()->get();
            foreach ($usuarios as $user) {
                $user->notify(new PresupuestoRechazadoNotification($presupuesto, $motivo));
            }
            $primeraNotif = $usuarios->isNotEmpty()
                ? $usuarios->first()->notifications()
                ->where('type', PresupuestoRechazadoNotification::class)
                ->latest()
                ->first()
                : null;
            $presupuesto->addNotification($primeraNotif?->id);
        }

        return $this->success(
            new PresupuestoPublicResource($presupuesto->fresh(Presupuesto::eagerLodable())),
            'Presupuesto rechazado.'
        );
    }
}
