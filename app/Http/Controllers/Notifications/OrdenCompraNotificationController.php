<?php

namespace App\Http\Controllers\Notifications;

use App\Http\Controllers\Controller;
use App\Models\Proveedor;
use App\Notifications\OrdenCompra\NuevaOrdenCompraNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrdenCompraNotificationController extends Controller
{
    /**
     * Recibir notificación de nueva orden de compra desde API Construcciones
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function nuevaOrden(Request $request)
    {
        try {
            // Reconectar bases de datos
            DB::purge('mysql');
            DB::reconnect('mysql');
            DB::purge('mysql5');
            DB::reconnect('mysql5');

            // 1. Validar body de la petición
            $validated = $request->validate([
                'empresa_id' => 'required|integer',
                'proveedor_id' => 'required|integer',
                'orden_compra_id' => 'required|string',
                'estatus' => 'nullable|string',
            ]);

            // 2. Buscar proveedor usando conexión mysql5
            $proveedor = Proveedor::on('mysql5')->findOrFail($validated['proveedor_id']);

            // 3. Buscar usuario principal usando conexión mysql5
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

            // 4. Insertar en tabla oc_construcc
            $dataToInsert = [
                'empresa_id' => $validated['empresa_id'],
                'proveedor_id' => $validated['proveedor_id'],
                'orden_compra_id' => $validated['orden_compra_id'],
                'estatus' => $validated['estatus'] ?? 'pendiente',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $inserted = DB::connection('mysql5')->table('oc_construcc')->insert($dataToInsert);

            if ($inserted) {
                Log::info('✅ Inserción exitosa en oc_construcc');
            } else {
                Log::error('❌ Error al insertar en oc_construcc');
            }

            // 5. Enviar notificación Laravel
            $usuarioPrincipal->notify(new NuevaOrdenCompraNotification(
                $validated['orden_compra_id'],
                $validated['proveedor_id'],
                $validated['empresa_id'],
                $validated['estatus'] ?? 'pendiente'
            ));

            Log::info('✅ Notificación enviada correctamente', [
                'usuario_id' => $usuarioPrincipal->id,
                'orden_compra_id' => $validated['orden_compra_id'],
                'tipo' => 'nueva_orden_compra'
            ]);

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
                    ]
                ]
            ], $inserted ? 201 : 500);
        } catch (\Exception $e) {
            Log::error('🔴 ERROR en nuevaOrden()', [
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
}
