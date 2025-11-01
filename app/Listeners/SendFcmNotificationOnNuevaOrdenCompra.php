<?php

namespace App\Listeners;

use App\Events\NuevaOrdenCompraEvent;
use App\Models\User;
use App\Services\FcmService;
use Illuminate\Support\Facades\Log;

class SendFcmNotificationOnNuevaOrdenCompra
{
    protected FcmService $fcmService;

    public function __construct(FcmService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    /**
     * Handle the event.
     */
    public function handle(NuevaOrdenCompraEvent $event): void
    {
        // Si no hay userId, no podemos enviar FCM
        if (!$event->userId) {
            Log::warning('FCM Listener: No se puede enviar FCM sin userId', [
                'orden_compra_id' => $event->ordenCompraId,
            ]);
            return;
        }

        try {
            // Buscar el usuario
            $user = User::find($event->userId);
            
            if (!$user) {
                Log::warning('FCM Listener: Usuario no encontrado', [
                    'user_id' => $event->userId,
                ]);
                return;
            }

            // Obtener tokens activos del usuario
            $tokens = $user->fcm_tokens;

            if (empty($tokens)) {
                Log::info('FCM Listener: Usuario sin tokens FCM activos', [
                    'user_id' => $user->id,
                ]);
                return;
            }

            // Preparar notificación
            $notification = [
                'title' => '📦 Nueva Orden de Compra #' . $event->ordenCompraId,
                'body' => 'Tienes una nueva orden de compra',
            ];

            $data = [
                'type' => 'nueva_orden_compra',
                'entityId' => (string) $event->ordenCompraId,
                'proveedorId' => (string) $event->proveedorId,
                'empresaId' => (string) $event->empresaId,
                'estatus' => (string) ($event->estatus ?? 'pendiente'),
                'action' => 'view',
                'timestamp' => now()->toISOString(),
            ];

            // Enviar a FCM
            $success = $this->fcmService->sendToTokens($tokens, $notification, $data);

            if ($success) {
                Log::info('FCM Listener: Notificación enviada exitosamente', [
                    'user_id' => $user->id,
                    'tokens_count' => count($tokens),
                    'orden_compra_id' => $event->ordenCompraId,
                ]);
            } else {
                Log::warning('FCM Listener: Falló el envío de notificación', [
                    'user_id' => $user->id,
                    'tokens_count' => count($tokens),
                ]);
            }

        } catch (\Exception $e) {
            Log::error('FCM Listener: Error enviando notificación FCM', [
                'user_id' => $event->userId,
                'orden_compra_id' => $event->ordenCompraId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
