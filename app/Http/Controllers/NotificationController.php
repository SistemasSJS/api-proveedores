<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\SendNotificationRequest;
use App\Http\Resources\Notification\UserResource;
use App\Http\Resources\Notification\NotificationResource;
use App\Http\Resources\Notification\NotificationGroupResource;
use App\Http\Resources\Notification\NotificationItemResource;
use App\Models\User;
use App\Models\TipoNotificacion;
use App\Notifications\PushNotification;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    use ApiResponse;

    /**
     * Enviar una notificación a un usuario específico
     */
    public function sendToUser(SendNotificationRequest $request, $userId)
    {
        $user = User::findOrFail($userId);

        try {
            $user->notify(new PushNotification(
                $request->input('title'),
                $request->input('message'),
                $request->input('type', 'info'),
                $request->input('data', [])
            ));

            return $this->success(
                new UserResource($user),
                'Notificación enviada exitosamente'
            );
        } catch (\Exception $e) {
            return $this->error(
                'Error al enviar la notificación',
                $e->getMessage(),
                500
            );
        }
    }

    /**
     * Enviar una notificación al usuario autenticado
     */
    public function sendToCurrentUser(SendNotificationRequest $request)
    {
        $user = Auth::user();

        try {
            $user->notify(new PushNotification(
                $request->input('title'),
                $request->input('message'),
                $request->input('type', 'info'),
                $request->input('data', [])
            ));

            return $this->success(
                new UserResource($user),
                'Notificación enviada exitosamente'
            );
        } catch (\Exception $e) {
            return $this->error(
                'Error al enviar la notificación',
                $e->getMessage(),
                500
            );
        }
    }

    /**
     * Enviar notificación de prueba
     */
    public function sendTest()
    {
        $user = Auth::user();

        try {
            $user->notify(new PushNotification(
                '🔔 Notificación de Prueba',
                'Esta es una notificación de prueba desde la API. ¡El sistema de WebSocket está funcionando correctamente!',
                'success',
                [
                    'timestamp' => now()->toIsoString(),
                    'source' => 'api_test',
                    'test' => true
                ]
            ));

            return $this->success(
                new UserResource($user),
                'Notificación de prueba enviada'
            );
        } catch (\Exception $e) {
            return $this->error(
                'Error al enviar la notificación de prueba',
                $e->getMessage(),
                500
            );
        }
    }

    /**
     * Obtener las notificaciones del usuario autenticado (versión simple)
     */
    public function getNotifications(Request $request)
    {
        $user = Auth::user();

        $notifications = $user->notifications()
            ->latest()
            ->paginate($request->input('per_page', 15));

        return $this->paginated(
            NotificationResource::collection($notifications)->resource,
            'Lista de notificaciones'
        );
    }

    /**
     * Obtener notificaciones agrupadas por tipo
     * 
     * Retorna las notificaciones del usuario autenticado agrupadas por tipo,
     * incluyendo solo tipos activos que tengan notificaciones existentes.
     */
    public function getGroupedByType(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $perPage = $request->get('per_page', 10);
        $tipoId = $request->get('tipo_id');
        $soloNoLeidas = $request->boolean('solo_no_leidas', false);

        // Query base para obtener notificaciones agrupadas por tipo
        $query = TipoNotificacion::query()
            ->select([
                'tipos_notificacion.*',
                DB::raw('COUNT(notifications.id) as total_notificaciones'),
                DB::raw('COUNT(CASE WHEN notifications.read_at IS NULL THEN 1 END) as no_leidas'),
                DB::raw('COUNT(CASE WHEN notifications.created_at >= NOW() - INTERVAL 24 HOUR THEN 1 END) as recientes')
            ])
            ->join('notifications', function ($join) use ($user) {
                $join->on('tipos_notificacion.id', '=', DB::raw('JSON_EXTRACT(notifications.data, "$.tipo_notificacion_id")'))
                     ->where('notifications.notifiable_type', get_class($user))
                     ->where('notifications.notifiable_id', $user->id);
            })
            ->where('tipos_notificacion.estatus', true)
            ->groupBy('tipos_notificacion.id')
            ->having('total_notificaciones', '>', 0)
            ->ordenadosPorImportancia();

        // Filtrar por tipo específico si se proporciona
        if ($tipoId) {
            $query->where('tipos_notificacion.id', $tipoId);
        }

        $tiposConNotificaciones = $query->get();

        // Para cada tipo, obtener las notificaciones correspondientes
        $resultados = $tiposConNotificaciones->map(function ($tipo) use ($user, $perPage, $soloNoLeidas) {
            $notificationsQuery = $user->notifications()
                ->whereRaw('JSON_EXTRACT(data, "$.tipo_notificacion_id") = ?', [$tipo->id])
                ->latest();

            if ($soloNoLeidas) {
                $notificationsQuery->whereNull('read_at');
            }

            $notificaciones = $notificationsQuery->limit($perPage)->get();
            
            // Agregar las notificaciones al tipo
            $tipo->notificaciones = $notificaciones;
            $tipo->total_notificaciones = (int) $tipo->total_notificaciones;
            $tipo->no_leidas = (int) $tipo->no_leidas;
            $tipo->recientes = (int) $tipo->recientes;

            return $tipo;
        });

        return NotificationGroupResource::collection($resultados);
    }

    /**
     * Obtener notificaciones de un tipo específico paginadas
     */
    public function getByTipo(Request $request, int $tipoId): JsonResponse
    {
        $user = $request->user();
        $perPage = $request->get('per_page', 15);
        $soloNoLeidas = $request->boolean('solo_no_leidas', false);

        // Verificar que el tipo existe y está activo
        $tipo = TipoNotificacion::where('id', $tipoId)
            ->where('estatus', true)
            ->first();

        if (!$tipo) {
            return response()->json([
                'message' => 'Tipo de notificación no encontrado o inactivo',
            ], 404);
        }

        // Obtener notificaciones paginadas
        $query = $user->notifications()
            ->whereRaw('JSON_EXTRACT(data, "$.tipo_notificacion_id") = ?', [$tipoId])
            ->latest();

        if ($soloNoLeidas) {
            $query->whereNull('read_at');
        }

        $notificaciones = $query->paginate($perPage);

        return response()->json([
            'tipo' => $tipo->toFrontendArray(),
            'notificaciones' => NotificationItemResource::collection($notificaciones),
            'meta' => [
                'current_page' => $notificaciones->currentPage(),
                'last_page' => $notificaciones->lastPage(),
                'per_page' => $notificaciones->perPage(),
                'total' => $notificaciones->total(),
                'has_more_pages' => $notificaciones->hasMorePages(),
            ],
        ]);
    }

    /**
     * Marcar una notificación como leída
     */
    public function markAsRead($notificationId)
    {
        $user = Auth::user();

        $notification = $user->notifications()->find($notificationId);

        if (!$notification) {
            return $this->error('Notificación no encontrada', null, 404);
        }

        $notification->markAsRead();

        return $this->success(null, 'Notificación marcada como leída');
    }

    /**
     * Marcar todas las notificaciones como leídas
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        $user->unreadNotifications->markAsRead();

        return $this->success(null, 'Todas las notificaciones marcadas como leídas');
    }

    /**
     * Marcar notificaciones de un tipo como leídas
     */
    public function markTipoAsRead(Request $request, int $tipoId): JsonResponse
    {
        $user = $request->user();
        
        // Verificar que el tipo existe
        $tipo = TipoNotificacion::find($tipoId);
        if (!$tipo) {
            return response()->json([
                'message' => 'Tipo de notificación no encontrado',
            ], 404);
        }

        $count = $user->unreadNotifications()
            ->whereRaw('JSON_EXTRACT(data, "$.tipo_notificacion_id") = ?', [$tipoId])
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => "Se marcaron {$count} notificaciones del tipo '{$tipo->nombre}' como leídas",
            'count' => $count,
            'tipo' => $tipo->toFrontendArray(),
        ]);
    }

    /**
     * Eliminar notificación
     */
    public function destroy(Request $request, string $notificationId): JsonResponse
    {
        $user = $request->user();
        
        $notification = $user->notifications()->find($notificationId);
        
        if (!$notification) {
            return response()->json([
                'message' => 'Notificación no encontrada',
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'message' => 'Notificación eliminada correctamente',
        ]);
    }

    /**
     * Obtener resumen de notificaciones (contadores)
     */
    public function resumen(Request $request): JsonResponse
    {
        $user = $request->user();

        // Contadores generales
        $totalNoLeidas = $user->unreadNotifications()->count();
        $totalNotificaciones = $user->notifications()->count();
        $recientes24h = $user->notifications()
            ->where('created_at', '>=', now()->subDay())
            ->count();

        // Contadores por tipo (solo tipos con notificaciones)
        $tiposConContadores = TipoNotificacion::select([
                'tipos_notificacion.id',
                'tipos_notificacion.codigo',
                'tipos_notificacion.nombre',
                'tipos_notificacion.icono',
                'tipos_notificacion.color',
                'tipos_notificacion.orden_importancia',
                DB::raw('COUNT(notifications.id) as total'),
                DB::raw('COUNT(CASE WHEN notifications.read_at IS NULL THEN 1 END) as no_leidas'),
                DB::raw('COUNT(CASE WHEN notifications.created_at >= NOW() - INTERVAL 24 HOUR THEN 1 END) as recientes')
            ])
            ->join('notifications', function ($join) use ($user) {
                $join->on('tipos_notificacion.id', '=', DB::raw('JSON_EXTRACT(notifications.data, "$.tipo_notificacion_id")'))
                     ->where('notifications.notifiable_type', get_class($user))
                     ->where('notifications.notifiable_id', $user->id);
            })
            ->where('tipos_notificacion.estatus', true)
            ->groupBy('tipos_notificacion.id')
            ->having('total', '>', 0)
            ->ordenadosPorImportancia()
            ->get();

        return response()->json([
            'resumen_general' => [
                'total_notificaciones' => $totalNotificaciones,
                'no_leidas' => $totalNoLeidas,
                'recientes_24h' => $recientes24h,
            ],
            'por_tipo' => $tiposConContadores->map(function ($tipo) {
                return [
                    'tipo' => [
                        'id' => $tipo->id,
                        'codigo' => $tipo->codigo,
                        'nombre' => $tipo->nombre,
                        'icono' => $tipo->icono ?: 'notifications-outline',
                        'color' => $tipo->color,
                        'orden_importancia' => $tipo->orden_importancia,
                    ],
                    'contadores' => [
                        'total' => (int) $tipo->total,
                        'no_leidas' => (int) $tipo->no_leidas,
                        'recientes' => (int) $tipo->recientes,
                    ],
                ];
            }),
        ]);
    }
}
