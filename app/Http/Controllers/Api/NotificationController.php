<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OcConstrucc;
use App\Models\OrdenCompra;
use App\Models\Proveedor;
use App\Models\User;
use App\Notifications\PushNotification;
use App\Notifications\NuevaOrdenCompra;
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


    //---------------
    /**
     * Recibir notificación de nueva orden de compra desde API Construcciones
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function nuevaOrden(Request $request)
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

        // 2. Buscar los usuarios del proveedor mediante las relaciones definidas en el modelo
        $proveedor = Proveedor::findOrFail($validated['proveedor_id']);
        $usuarioPrincipal = $proveedor->usuarioPrincipal();
        $usuariosActivos = $proveedor->usuariosActivos()->get();

        if (!$usuarioPrincipal) {
            return response()->json([
                'success' => false,
                'message' => 'El proveedor no tiene un usuario principal asignado',
                'proveedor_id' => $validated['proveedor_id']
            ], 422);
        }

        // 2. Insertar en la tabla usando la conexión mysql5
        $inserted = DB::connection('mysql5')->table('oc_construcc')->insert([
            'empresa_id' => $validated['empresa_id'],
            'proveedor_id' => $validated['proveedor_id'],
            'orden_compra_id' => $validated['orden_compra_id'],
            'estatus' => $validated['estatus'] ?? 'pendiente', // Valor por defecto
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        /**
         * Enviar notificación usando Laravel Notifications
         * Esto automáticamente:
         * - Guarda en tabla 'notifications'
         * - Envía por broadcast (WebSocket)
         * - Puede procesarse en cola (queue)
         */
        $usuarioPrincipal->notify(new NuevaOrdenCompra(
            $validated['orden_compra_id'],
            $validated['proveedor_id'],
            $validated['empresa_id'],
            $validated['estatus']
        ));

        Log::info('✅ Notificación enviada correctamente', [
            'usuario_id' => $usuarioPrincipal->id,
            'orden_compra_id' => $validated['orden_compra_id'],
            'tipo' => 'nueva_orden_compra'
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
