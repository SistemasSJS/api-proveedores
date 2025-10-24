<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notificacion;
use App\Models\OrdenCompra;
use App\Models\Proveedor;
use App\Models\User;
use App\Notifications\PushNotification;
use App\Events\NuevaOrdenCompraEvent;
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
     */
    public function nuevaOrden(Request $request)
    {
        $validated = $request->validate([
            'num_orden' => 'required|string',
            'proveedor_id' => 'required|integer|exists:proveedores,id', // Verifica que existe en tabla proveedores, columna id
            'fecha' => 'required|string',
            'obra_id' => 'required|integer',
            'empresa' => 'required|integer',
            'usuario' => 'nullable|integer',
            'tipo_orden' => 'required|string',
            'requisicion_id' => 'nullable|integer',
            'tiene_requisicion' => 'required|boolean',
            'subtotal' => 'required|numeric',
            'iva' => 'required|numeric',
            'tasa' => 'required|numeric',
            'importe' => 'required|numeric',
            'estatus' => 'required|string',
            'observaciones' => 'nullable|string',
        ]);

        try {
            // 1. Obtener el proveedor y su usuario principal
            $proveedor = Proveedor::findOrFail($validated['proveedor_id']);
            $usuarioPrincipal = $proveedor->usuarioPrincipal();
            
            // Verificar que el proveedor tiene un usuario principal asignado
            if (!$usuarioPrincipal) {
                return response()->json([
                    'success' => false,
                    'message' => 'El proveedor no tiene un usuario principal asignado',
                    'proveedor_id' => $validated['proveedor_id']
                ], 422);
            }

            // Iniciar transacción
            DB::beginTransaction();

            // 2. Guardar orden de compra en tabla ordenes_compra
            $ordenCompra = OrdenCompra::create([
                'numero_orden' => $validated['num_orden'],
                'fecha_orden' => $validated['fecha'],
                'proveedor_id' => $validated['proveedor_id'],
                'empresa_construcc_id' => $validated['empresa'],
                'importe_total' => $validated['importe'],
                'estado' => 'pendiente', // Estado inicial en sistema proveedores
                'observaciones' => $validated['observaciones'],
                // Campos específicos de API Construcciones
                'obra_id' => $validated['obra_id'],
                'usuario_id' => $validated['usuario'],
                'tipo_orden' => $validated['tipo_orden'],
                'requisicion_id' => $validated['requisicion_id'],
                'tiene_requisicion' => $validated['tiene_requisicion'],
                'subtotal' => $validated['subtotal'],
                'iva' => $validated['iva'],
                'tasa' => $validated['tasa'],
                'estatus_construcc' => $validated['estatus'], // Estatus original de construcciones
            ]);

            // 3. Guardar notificación en tabla
            $notificacion = Notificacion::create([
                'tipo' => 'nueva_orden_compra',
                'proveedor_id' => $validated['proveedor_id'],
                'titulo' => 'Nueva Orden de Compra #' . $validated['num_orden'],
                'mensaje' => "Tienes una nueva orden de compra por $" . number_format($validated['importe'], 2),
                'data' => [
                    'num_orden' => $validated['num_orden'],
                    'fecha' => $validated['fecha'],
                    'obra_id' => $validated['obra_id'],
                    'empresa' => $validated['empresa'],
                    'usuario' => $validated['usuario'] ?? null,
                    'tipo_orden' => $validated['tipo_orden'],
                    'requisicion_id' => $validated['requisicion_id'] ?? null,
                    'tiene_requisicion' => $validated['tiene_requisicion'],
                    'subtotal' => $validated['subtotal'],
                    'iva' => $validated['iva'],
                    'tasa' => $validated['tasa'],
                    'importe' => $validated['importe'],
                    'estatus' => $validated['estatus'],
                    'observaciones' => $validated['observaciones'] ?? null
                ],
                'leida' => false,
            ]);

            Log::channel('inter_api')->info('Orden de compra y notificación guardadas', [
                'orden_compra_id' => $ordenCompra->id,
                'notificacion_id' => $notificacion->id,
                'num_orden' => $validated['num_orden'],
                'proveedor_id' => $validated['proveedor_id'],
                'importe_total' => $validated['importe'],
                'usuario_notificado_id' => $usuarioPrincipal->id,
                'usuario_notificado_name' => $usuarioPrincipal->name
            ]);

            // 4. Broadcast para notificación en tiempo real (WebSocket)
            broadcast(new NuevaOrdenCompraEvent([
                'notificacion_id' => $notificacion['num_orden'],
                'proveedor_id' => $validated['proveedor_id'],
                'num_orden' => $validated['num_orden'],
                'fecha' => $validated['fecha'],
                'obra_id' => $validated['obra_id'],
                'empresa' => $validated['empresa'],
                'usuario' => $validated['usuario'] ?? null,
                'tipo_orden' => $validated['tipo_orden'],
                'requisicion_id' => $validated['requisicion_id'] ?? null,
                'tiene_requisicion' => $validated['tiene_requisicion'],
                'subtotal' => $validated['subtotal'],
                'iva' => $validated['iva'],
                'tasa' => $validated['tasa'],
                'importe' => $validated['importe'],
                'estatus' => $validated['estatus'],
                'observaciones' => $validated['observaciones'] ?? null,
                'titulo' => $notificacion->titulo,
                'mensaje' => $notificacion->mensaje
            ]));

            // 5. Enviar notificación push al usuario principal del proveedor
            try {
                $usuarioPrincipal->notify(new PushNotification(
                    $notificacion->titulo,
                    $notificacion->mensaje,
                    'info',
                    [
                        'notificacion_id' => $notificacion->id,
                        'num_orden' => $validated['num_orden'],
                        'importe' => $validated['importe'],
                        'tipo' => 'nueva_orden_compra'
                    ]
                ));
                
                Log::channel('inter_api')->info('Notificación push enviada correctamente', [
                    'usuario_id' => $usuarioPrincipal->id,
                    'usuario_name' => $usuarioPrincipal->name,
                    'notificacion_id' => $notificacion->id
                ]);
            } catch (\Exception $e) {
                Log::channel('inter_api')->error('Error al enviar notificación push a usuario principal', [
                    'usuario_id' => $usuarioPrincipal->id,
                    'proveedor_id' => $validated['proveedor_id'],
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }


            // 6. Confirmar transacción
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Orden de compra y notificación creadas correctamente',
                'data' => [
                    'orden_compra_id' => $ordenCompra->id,
                    'notificacion_id' => $notificacion->id,
                    'numero_orden' => $ordenCompra->numero_orden
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::channel('inter_api')->error('Error al crear orden de compra y notificación', [
                'error' => $e->getMessage(),
                'num_orden' => $validated['num_orden'] ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear orden de compra y notificación',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
