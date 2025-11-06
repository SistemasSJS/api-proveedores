<?php

namespace App\Http\Controllers\Notifications;

use App\Http\Controllers\Controller;
use App\Models\Proveedor;
use App\Notifications\SolicitudPago\SolicitudPagoPagada;
use App\Notifications\SolicitudPago\SolicitudPagoRechazada;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SolicitudPagoNotificationController extends Controller
{
    /**
     * Notificar que una solicitud de pago ha sido pagada/aceptada
     * Endpoint llamado desde API Construcciones
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function solicitudPagada(Request $request)
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
                'solicitud_pago_folio' => 'required|string',
                'monto' => 'nullable|numeric',
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

            // 4. Enviar notificación Laravel
            $usuarioPrincipal->notify(new SolicitudPagoPagada(
                $validated['solicitud_pago_folio'],
                $validated['proveedor_id'],
                $validated['empresa_id'],
                $validated['monto'] ?? null
            ));

            Log::info('✅ Notificación de SP Pagada enviada correctamente', [
                'usuario_id' => $usuarioPrincipal->id,
                'solicitud_pago_folio' => $validated['solicitud_pago_folio'],
                'tipo' => 'solicitud_pago_pagada'
            ]);

            // 5. Devolver respuesta
            return response()->json([
                'success' => true,
                'message' => 'Notificación de solicitud de pago pagada enviada correctamente',
                'data' => [
                    'empresa_id' => $validated['empresa_id'],
                    'proveedor_id' => $validated['proveedor_id'],
                    'solicitud_pago_folio' => $validated['solicitud_pago_folio'],
                    'monto' => $validated['monto'] ?? null,
                    'usuario_notificado' => [
                        'id' => $usuarioPrincipal->id,
                        'name' => $usuarioPrincipal->name,
                        'email' => $usuarioPrincipal->email
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('🔴 ERROR en solicitudPagada()', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la notificación de solicitud de pago pagada',
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Notificar que una solicitud de pago ha sido rechazada
     * Endpoint llamado desde API Construcciones
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function solicitudRechazada(Request $request)
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
                'solicitud_pago_folio' => 'required|string',
                'motivo' => 'nullable|string',
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

            // 4. Enviar notificación Laravel
            $usuarioPrincipal->notify(new SolicitudPagoRechazada(
                $validated['solicitud_pago_folio'],
                $validated['proveedor_id'],
                $validated['empresa_id'],
                $validated['motivo'] ?? null
            ));

            Log::info('✅ Notificación de SP Rechazada enviada correctamente', [
                'usuario_id' => $usuarioPrincipal->id,
                'solicitud_pago_folio' => $validated['solicitud_pago_folio'],
                'tipo' => 'solicitud_pago_rechazada'
            ]);

            // 5. Devolver respuesta
            return response()->json([
                'success' => true,
                'message' => 'Notificación de solicitud de pago rechazada enviada correctamente',
                'data' => [
                    'empresa_id' => $validated['empresa_id'],
                    'proveedor_id' => $validated['proveedor_id'],
                    'solicitud_pago_folio' => $validated['solicitud_pago_folio'],
                    'motivo' => $validated['motivo'] ?? null,
                    'usuario_notificado' => [
                        'id' => $usuarioPrincipal->id,
                        'name' => $usuarioPrincipal->name,
                        'email' => $usuarioPrincipal->email
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('🔴 ERROR en solicitudRechazada()', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la notificación de solicitud de pago rechazada',
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }
}
