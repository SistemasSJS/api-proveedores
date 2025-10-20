<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\PushNotification;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    /**
     * Enviar notificación de status a un usuario
     */
    public function notifyUser(Request $request)
    {
        // Validar parámetros
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'title' => 'nullable|string|max:255',
            'message' => 'required|string',
            'type' => 'nullable|string|in:info,success,warning,error,danger',
        ]);

        try {
            // Buscar el usuario
            $user = User::findOrFail($request->user_id);

            // Crear la notificación
            $title = $request->input('title', '📊 Notificación de Status');
            $message = $request->input('message');
            $type = $request->input('type', 'info');

            // Datos adicionales
            $data = [
                'source' => 'status_api',
                'timestamp' => now()->toIsoString(),
                'request_ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ];

            // Si hay datos adicionales en el request
            if ($request->has('data')) {
                $data = array_merge($data, $request->input('data', []));
            }

            // Enviar la notificación
            $user->notify(new PushNotification($title, $message, $type, $data));

            return response()->json([
                'success' => true,
                'message' => 'Notificación enviada exitosamente',
                'data' => [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'notification' => [
                        'title' => $title,
                        'message' => $message,
                        'type' => $type,
                        'timestamp' => now()->toIsoString(),
                    ]
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado',
                'error' => 'El usuario con ID ' . $request->user_id . ' no existe'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar la notificación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Notificar usuario usando query parameters (más simple)
     */
    public function simpleNotify(Request $request)
    {
        // Obtener parámetros de query
        $userId = $request->query('user_id', $request->query('user'));
        $message = $request->query('message', 'Notificación desde Status API');
        $title = $request->query('title', '📊 Status Update');
        $type = $request->query('type', 'info');

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Se requiere user_id como parámetro',
                'example' => '/api/status?user_id=1&message=Hola&title=Test&type=success'
            ], 400);
        }

        try {
            $user = User::findOrFail($userId);

            // Crear notificación simple
            $data = [
                'source' => 'status_simple',
                'method' => $request->method(),
                'timestamp' => now()->toIsoString(),
            ];

            $user->notify(new PushNotification($title, $message, $type, $data));

            return response()->json([
                'success' => true,
                'message' => '✅ Notificación enviada!',
                'user' => $user->name,
                'notification' => compact('title', 'message', 'type'),
                'timestamp' => now()->toIsoString()
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => "❌ Usuario {$userId} no encontrado",
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '❌ Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener lista de usuarios disponibles
     */
    public function getUsers()
    {
        try {
            $users = User::select('id', 'name', 'email', 'created_at')
                ->orderBy('id')
                ->limit(20)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Lista de usuarios disponibles',
                'users' => $users,
                'count' => $users->count(),
                'example_url' => '/api/status?user_id=1&message=Test'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo usuarios',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Status general del sistema y notificar a todos los admins
     */
    public function systemStatus(Request $request)
    {
        try {
            // Obtener información del sistema
            $status = [
                'timestamp' => now()->toIsoString(),
                'server' => [
                    'php_version' => PHP_VERSION,
                    'laravel_version' => app()->version(),
                    'server_time' => now()->format('Y-m-d H:i:s'),
                ],
                'database' => [
                    'connected' => true,
                    'users_count' => User::count(),
                ],
                'notifications' => [
                    'polling_enabled' => true,
                    'last_check' => now()->toIsoString(),
                ]
            ];

            // Si se solicita notificar admins
            if ($request->query('notify_admins', false)) {
                $admins = User::where('role', 'admin')
                    ->orWhere('email', 'like', '%admin%')
                    ->orWhere('id', '<=', 3) // Primeros usuarios suelen ser admins
                    ->get();

                $message = "Sistema funcionando correctamente.\n" .
                          "Usuarios: " . $status['database']['users_count'] . "\n" .
                          "Tiempo: " . $status['server']['server_time'];

                foreach ($admins as $admin) {
                    $admin->notify(new PushNotification(
                        '🖥️ Status del Sistema',
                        $message,
                        'info',
                        ['system_status' => $status, 'source' => 'system_check']
                    ));
                }

                $status['notifications_sent'] = $admins->count();
                $status['notified_admins'] = $admins->pluck('name', 'id');
            }

            return response()->json([
                'success' => true,
                'message' => 'Status del sistema',
                'status' => $status
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}