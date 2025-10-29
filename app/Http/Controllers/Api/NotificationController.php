<?php

namespace App\Http\Controllers\Api;

use App\Events\NuevaOrdenCompraEvent;
use App\Http\Controllers\Controller;
use App\Models\Proveedor;
use App\Models\User;
use App\Notifications\PushNotification;
use App\Notifications\NuevaOrdenCompra;
use App\Services\FcmService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Enviar una notificación a un usuario específico
     */
    public function sendToUser(Request $request, $userId)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'nullable|string|in:info,success,warning,error,danger',
            'data' => 'nullable|array',
        ]);

        $user = User::findOrFail($userId);

        try {
            $user->notify(new PushNotification(
                $request->input('title'),
                $request->input('message'),
                $request->input('type', 'info'),
                $request->input('data', [])
            ));

            return response()->json([
                'success' => true,
                'message' => 'Notificación enviada exitosamente',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar la notificación',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Enviar una notificación al usuario autenticado
     */
    public function sendToCurrentUser(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'nullable|string|in:info,success,warning,error,danger',
            'data' => 'nullable|array',
        ]);

        $user = Auth::user();

        try {
            $user->notify(new PushNotification(
                $request->input('title'),
                $request->input('message'),
                $request->input('type', 'info'),
                $request->input('data', [])
            ));

            return response()->json([
                'success' => true,
                'message' => 'Notificación enviada exitosamente',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar la notificación',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Enviar notificación de prueba
     */
    public function sendTest(Request $request)
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
                    'test' => true,
                ]
            ));

            return response()->json([
                'success' => true,
                'message' => 'Notificación de prueba enviada',
                'info' => 'Revisa la aplicación, deberías ver la notificación en tiempo real',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar la notificación de prueba',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener las notificaciones del usuario autenticado
     */
    public function getNotifications(Request $request)
    {
        $user = Auth::user();

        $notifications = $user->notifications()
            ->latest()
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'notifications' => $notifications,
        ]);
    }

    /**
     * Marcar una notificación como leída
     */
    public function markAsRead($notificationId)
    {
        $user = Auth::user();

        $notification = $user->notifications()->find($notificationId);

        if (! $notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notificación no encontrada',
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notificación marcada como leída',
        ]);
    }

    /**
     * Marcar todas las notificaciones como leídas
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        $user->unreadNotifications->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Todas las notificaciones marcadas como leídas',
        ]);
    }

    /**
     * Polling endpoint para notificaciones
     * Usado por el servicio SSE/Polling del frontend
     */
    public function poll(Request $request)
    {
        $user = Auth::user();
        $lastTimestamp = $request->query('last_timestamp');

        $query = $user->notifications()->latest();

        if ($lastTimestamp) {
            $query->where('created_at', '>', $lastTimestamp);
        }

        $notifications = $query->take(50)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'has_changes' => $notifications->count() > 0,
                'notifications' => $notifications,
                'timestamp' => now()->toIsoString(),
                'unread_count' => $user->unreadNotifications->count()
            ]
        ]);
    }


    //---------------
    /**
     * Recibir notificación de nueva orden de compra desde API Construcciones
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function nuevaOrden(Request $request)
    {
        Log::info('🔵 ========== INICIO nuevaOrden() ==========');

        try {
            // Reconectar bases de datos
            DB::purge('mysql');
            DB::reconnect('mysql');
            DB::purge('mysql5');
            DB::reconnect('mysql5');
            Log::info('🔄 Conexiones de BD reconectadas');

            Log::info('📦 Request recibido:', [
                'headers' => $request->headers->all(),
                'body' => $request->all()
            ]);

            // 1. Validar body de la petición
            Log::info('🔍 Validando request...');
            $validated = $request->validate([
                'empresa_id' => 'required|integer',
                'proveedor_id' => 'required|integer',
                'orden_compra_id' => 'required|string',
                'estatus' => 'nullable|string',
            ]);
            Log::info('✅ Validación exitosa:', $validated);

            // 2. Buscar proveedor usando conexión mysql5
            Log::info('🔍 Buscando proveedor ID: ' . $validated['proveedor_id'] . ' (mysql5)');
            $proveedor = Proveedor::on('mysql5')->findOrFail($validated['proveedor_id']);
            Log::info('✅ Proveedor encontrado:', [
                'id' => $proveedor->id,
                'nombre' => $proveedor->nombre ?? 'N/A',
                'connection' => $proveedor->getConnectionName()
            ]);

            // 3. Buscar usuario principal usando conexión mysql5
            Log::info('🔍 Buscando usuario principal del proveedor (mysql5)...');
            $usuarioPrincipal = $proveedor->usuarioPrincipal();

            if (!$usuarioPrincipal) {
                Log::error('❌ Proveedor sin usuario principal', [
                    'proveedor_id' => $validated['proveedor_id']
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'El proveedor no tiene un usuario principal asignado',
                    'proveedor_id' => $validated['proveedor_id']
                ], 422);
            }

            // Forzar conexión mysql5 en el usuario
            $usuarioPrincipal->setConnection('mysql5');

            Log::info('✅ Usuario principal encontrado:', [
                'id' => $usuarioPrincipal->id,
                'name' => $usuarioPrincipal->name,
                'email' => $usuarioPrincipal->email,
                'connection' => $usuarioPrincipal->getConnectionName()
            ]);

            // 4. Insertar en tabla oc_construcc
            Log::info('💾 Insertando en tabla oc_construcc (mysql5)...');
            $dataToInsert = [
                'empresa_id' => $validated['empresa_id'],
                'proveedor_id' => $validated['proveedor_id'],
                'orden_compra_id' => $validated['orden_compra_id'],
                'estatus' => $validated['estatus'] ?? 'pendiente',
                'created_at' => now(),
                'updated_at' => now(),
            ];
            Log::info('📊 Datos a insertar:', $dataToInsert);

            $inserted = DB::connection('mysql5')->table('oc_construcc')->insert($dataToInsert);

            if ($inserted) {
                Log::info('✅ Inserción exitosa en oc_construcc');
            } else {
                Log::error('❌ Error al insertar en oc_construcc');
            }

            // 5. Enviar notificación Laravelz
            Log::info('🔔 Enviando notificación Laravel...');
            Log::info('📊 Datos de notificación:', [
                'orden_compra_id' => $validated['orden_compra_id'],
                'proveedor_id' => $validated['proveedor_id'],
                'empresa_id' => $validated['empresa_id'],
                'estatus' => $validated['estatus'] ?? 'pendiente'
            ]);

            // Verificar tabla notifications antes (mysql5)
            $notificationsBefore = DB::connection('mysql5')->table('notifications')
                ->where('notifiable_id', $usuarioPrincipal->id)
                ->count();
            Log::info('📊 Notificaciones antes (mysql5): ' . $notificationsBefore);

            $dataEvento = [
                'notificacion_id' => uniqid('notif_'),
                'empresa_id' => $validated['empresa_id'],
                'proveedor_id' => $validated['proveedor_id'],
                'orden_compra_id' => $validated['orden_compra_id'],
                'estatus' => $validated['estatus'] ?? 'pendiente',
                'user_id' => $usuarioPrincipal->id,
            ];

            // 1. Event para Broadcasting (Reverb WebSocket)
            event(new NuevaOrdenCompraEvent($dataEvento));

            // 2. CANAL PUSH: Enviar notificación push MANUALMENTE
            $tokens = $usuarioPrincipal->deviceTokens()
                ->where('is_active', true)
                ->pluck('token')
                ->toArray();

            if (!empty($tokens)) {
                $fcmService = app(FcmService::class);

                $notification = [
                    'title' => '📦 Nueva Orden de Compra #' . $validated['orden_compra_id'],
                    'body' => "Tienes una nueva orden de compra: {$validated['orden_compra_id']}",
                ];

                $data = [
                    'tipo' => 'nueva_orden_compra',
                    'orden_compra_id' => $validated['orden_compra_id'],
                    'proveedor_id' => $validated['proveedor_id'],
                    'empresa_id' => $validated['empresa_id'],
                    'estatus' => $validated['estatus'] ?? 'pendiente',
                    'timestamp' => now()->toIso8601String(),
                ];

                $pushSuccess = $fcmService->sendToTokens($tokens, $notification, $data);

                if ($pushSuccess) {
                    Log::info('✅ Notificación Push enviada exitosamente', [
                        'usuario_id' => $usuarioPrincipal->id,
                        'tokens_count' => count($tokens),
                    ]);
                } else {
                    Log::warning('⚠️ Error al enviar notificación push', [
                        'usuario_id' => $usuarioPrincipal->id,
                    ]);
                }
            } else {
                Log::info('🔔 Usuario sin tokens FCM activos', [
                    'usuario_id' => $usuarioPrincipal->id,
                ]);
            }

            // Verificar tabla notifications después (mysql5)
            $notificationsAfter = DB::connection('mysql5')->table('notifications')
                ->where('notifiable_id', $usuarioPrincipal->id)
                ->count();
            Log::info('📊 Notificaciones después (mysql5): ' . $notificationsAfter);
            Log::info('📊 Notificaciones nuevas: ' . ($notificationsAfter - $notificationsBefore));

            // Obtener última notificación (mysql5)
            $lastNotification = DB::connection('mysql5')->table('notifications')
                ->where('notifiable_id', $usuarioPrincipal->id)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($lastNotification) {
                Log::info('✅ Última notificación creada:', [
                    'id' => $lastNotification->id,
                    'type' => $lastNotification->type,
                    'created_at' => $lastNotification->created_at
                ]);
            } else {
                Log::warning('⚠️ No se encontró la notificación en la BD');
            }

            Log::info('✅ Notificación enviada correctamente', [
                'usuario_id' => $usuarioPrincipal->id,
                'orden_compra_id' => $validated['orden_compra_id'],
                'tipo' => 'nueva_orden_compra'
            ]);

            Log::info('🔵 ========== FIN nuevaOrden() EXITOSO ==========');

            // 6. Devolver respuesta
            return response()->json([
                'success' => $inserted,
                'message' => $inserted ? 'Orden de compra creada correctamente' : 'Error al crear la orden de compra',
                'data' => [
                    'empresa_id' => $validated['empresa_id'],
                    'proveedor_id' => $validated['proveedor_id'],
                    'orden_compra_id' => $validated['orden_compra_id'],
                    'estatus' => $validated['estatus'] ?? 'pendiente',
                    'usuario_notificado' => [
                        'id' => $usuarioPrincipal->id,
                        'name' => $usuarioPrincipal->name,
                        'email' => $usuarioPrincipal->email
                    ],
                    'notificaciones_count' => $notificationsAfter ?? 0
                ],
                'connection' => [
                    'default' => config('database.default'),
                    'current_connection_name' => DB::connection()->getName(),
                    'database_name' => DB::connection('mysql5')->getDatabaseName(),
                    'driver' => DB::connection('mysql5')->getDriverName(),
                ],
            ], $inserted ? 201 : 500);
        } catch (\Exception $e) {
            Log::error('🔴 ========== ERROR en nuevaOrden() ==========');
            Log::error('❌ Excepción capturada:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la orden de compra',
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Recibir notificación de nueva orden de compra desde API Construcciones
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function nuevaOrdenOld(Request $request)
    {
        DB::purge('mysql'); // Limpia la conexión
        DB::reconnect('mysql'); // Reconecta
        DB::purge('mysql5'); // Limpia la conexión
        DB::reconnect('mysql5'); // Reconecta

        Log::info('📦 Request antes de validar:', $request->all());

        // 1. Validar body de la petición
        $validated = $request->validate([
            'empresa_id' => 'required|integer',
            'proveedor_id' => 'required|integer',
            'orden_compra_id' => 'required|string',
            'estatus' => 'nullable|string',
        ]);

        // 2. Insertar en la tabla usando la conexión mysql5
        $inserted = DB::connection('mysql5')->table('oc_construcc')->insert([
            'empresa_id' => $validated['empresa_id'],
            'proveedor_id' => $validated['proveedor_id'],
            'orden_compra_id' => $validated['orden_compra_id'],
            'estatus' => $validated['estatus'] ?? 'pendiente', // Valor por defecto
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Devolver información de la inserción y la conexión actual
        return response()->json([
            'success' => $inserted,
            'message' => $inserted ? 'Orden de compra creada correctamente' : 'Error al crear la orden de compra',
            'data' => [
                'empresa_id' => $validated['empresa_id'],
                'proveedor_id' => $validated['proveedor_id'],
                'orden_compra_id' => $validated['orden_compra_id'],
                'estatus' => $validated['estatus'] ?? 'pendiente',
            ],
            'connection' => [
                'default' => config('database.default'),
                'current_connection_name' => DB::connection()->getName(),
                'database_name' => DB::connection('mysql5')->getDatabaseName(),
                'driver' => DB::connection('mysql5')->getDriverName(),
            ],
        ], $inserted ? 201 : 500);

        // // Establecer estatus por defecto si no se proporciona
        // $validated['estatus'] = $validated['estatus'] ?? 'pendiente';

        // Log::info('📦 Nueva orden recibida:', $validated);

        // try {
        //     // 2. Buscar los usuarios del proveedor mediante las relaciones definidas en el modelo
        //     // $proveedor = Proveedor::findOrFail($validated['proveedor_id']);
        //     // $usuarioPrincipal = $proveedor->usuarioPrincipal();
        //     // $usuariosActivos = $proveedor->usuariosActivos()->get();

        //     // if (!$usuarioPrincipal) {
        //     //     return response()->json([
        //     //         'success' => false,
        //     //         'message' => 'El proveedor no tiene un usuario principal asignado',
        //     //         'proveedor_id' => $validated['proveedor_id']
        //     //     ], 422);
        //     // }

        //     // Iniciar transacción
        //     DB::beginTransaction();

        //     // 3. Almacenar la OC con los datos definidos en el body en la tabla oc_construcc
        //     // $ordenCompra = OcConstrucc::create([
        //     //     'empresa_id' => $request->empresa_id,
        //     //     'proveedor_id' => $request->proveedor_id,
        //     //     'orden_compra_id' => $request->orden_compra_id,
        //     //     'estatus' => $request->estatus,
        //     // ]);
        //     $ocConstrucc = new OcConstrucc();
        //     $ocConstrucc->setConnection('mysql5'); // Fuerza la conexión

        //     // Asignar datos del request al objeto
        //     $ocConstrucc->empresa_id = $request->empresa_id;
        //     $ocConstrucc->proveedor_id = $request->proveedor_id;
        //     $ocConstrucc->orden_compra_id = $request->orden_compra_id;
        //     $ocConstrucc->estatus = $request->estatus;

        //     // Guardar en la base de datos
        //     $ocConstrucc->save();
        //     // Crear notificación en tabla notificaciones
        //     // $notificacion = Notificacion::create([
        //     //     'tipo' => 'nueva_orden_compra',
        //     //     'proveedor_id' => $request->proveedor_id,
        //     //     'titulo' => 'Nueva Orden de Compra #' . $validated['orden_compra_id'],
        //     //     'mensaje' => "Tienes una nueva orden de compra: {$validated['orden_compra_id']}",
        //     //     'data' => [
        //     //         'orden_compra_id' => $validated['orden_compra_id'],
        //     //         'empresa_id' => $validated['empresa_id'],
        //     //         'estatus' => $validated['estatus'],
        //     //     ],
        //     //     'leida' => false,
        //     // ]);

        //     // 4. Generar notificación por el canal de usuarios
        //     // TODO: Implementar notificación asíncrona mediante queue/job para mejorar el tiempo de respuesta.
        //     // Se puede usar Jobs/Events con listeners para enviar notificaciones push y broadcast.
        //     // Ejemplo: dispatch(new NotificarNuevaOrdenJob($ordenCompra, $usuariosActivos));

        //     // // Broadcast para notificación en tiempo real (WebSocket)
        //     // broadcast(new NuevaOrdenCompraEvent([
        //     //     'orden_compra_id' => $ordenCompra->id,
        //     //     'proveedor_id' => $validated['proveedor_id'],
        //     //     'orden_compra_numero' => $validated['orden_compra_id'],
        //     //     'empresa_id' => $validated['empresa_id'],
        //     //     'estatus' => $validated['estatus'],
        //     //     'notificacion_id' => $notificacion->id,
        //     //     'titulo' => $notificacion->titulo,
        //     //     'mensaje' => $notificacion->mensaje,
        //     // ]));

        //     // Enviar notificación push al usuario principal
        //     // $usuarioPrincipal->notify(new PushNotification(
        //     //     $notificacion->titulo,
        //     //     $notificacion->mensaje,
        //     //     'info',
        //     //     [
        //     //         // 'notificacion_id' => $notificacion->id,
        //     //         'orden_compra_id' => $validated['orden_compra_id'],
        //     //         'tipo' => 'nueva_orden_compra'
        //     //     ]
        //     // ));

        //     // Log::info('✅ Notificación push enviada', [
        //     //     // 'usuario_id' => $usuarioPrincipal->id,
        //     //     // 'notificacion_id' => $notificacion->id
        //     // ]);


        //     // Confirmar transacción
        //     DB::commit();

        //     // 5. Resolver con la respuesta de la OC, la notificación creada, así como los usuarios notificados
        //     return response()->json([
        //         'success' => true,
        //         'message' => 'Orden de compra y notificación creadas correctamente',
        //         'data' => [
        //             'orden_compra' => [
        //                 // 'id' => $ordenCompra->id,
        //                 'orden_compra_id' => $ordenCompra->orden_compra_id,
        //                 'empresa_id' => $ordenCompra->empresa_id,
        //                 'proveedor_id' => $ordenCompra->proveedor_id,
        //                 'estatus' => $ordenCompra->estatus,
        //             ],
        //             // 'notificacion' => [
        //             //     // 'id' => $notificacion->id,
        //             //     'titulo' => $notificacion->titulo,
        //             //     'mensaje' => $notificacion->mensaje,
        //             // ],
        //             // 'usuarios_notificados' => [
        //             //     'principal' => [
        //             //         // 'id' => $usuarioPrincipal->id,
        //             //         'name' => $usuarioPrincipal->name,
        //             //         'email' => $usuarioPrincipal->email,
        //             //     ],
        //             //     'total_usuarios_activos' => $usuariosActivos->count(),
        //             // ]
        //         ]
        //     ], 201);
        // } catch (\Exception $e) {
        //     DB::rollBack();

        //     Log::error('❌ Error al crear orden de compra', [
        //         'error' => $e->getMessage(),
        //         'orden_compra_id' => $validated['orden_compra_id'] ?? null,
        //         'trace' => $e->getTraceAsString()
        //     ]);

        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Error al crear orden de compra y notificación',
        //         'error' => $e->getMessage()
        //     ], 500);
        // } catch (\Exception $e) {
        //     Log::error('❌ Error al enviar notificación push', [
        //         // 'usuario_id' => $usuarioPrincipal->id,
        //         'error' => $e->getMessage(),
        //     ]);
        // }
    }
}
