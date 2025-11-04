<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserDeviceToken;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Controller para manejar tokens de dispositivos FCM
 * Permite registrar, actualizar y gestionar tokens de push notifications
 */
class DeviceTokenController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/device-tokens",
     *     summary="Registrar token de dispositivo para notificaciones push",
     *     tags={"Notificaciones"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"token", "platform"},
     *             @OA\Property(property="token", type="string", example="FCM_TOKEN_HERE"),
     *             @OA\Property(property="platform", type="string", enum={"ios", "android", "web"}),
     *             @OA\Property(property="device_id", type="string", nullable=true),
     *             @OA\Property(property="device_name", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Token registrado correctamente")
     * )
     * Registrar o actualizar un token de dispositivo
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'token' => 'required|string|max:255',
                'platform' => 'required|in:ios,android,web',
                'device_id' => 'nullable|string|max:255',
                'device_name' => 'nullable|string|max:255',
                'metadata' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = Auth::user();
            $validated = $validator->validated();

            // Buscar token existente por usuario y device_id (si existe)
            $existingToken = null;
            if ($validated['device_id']) {
                $existingToken = UserDeviceToken::where('user_id', $user->id)
                    ->where('device_id', $validated['device_id'])
                    ->first();
            }

            // Si no hay device_id, buscar por token exacto
            if (! $existingToken) {
                $existingToken = UserDeviceToken::where('user_id', $user->id)
                    ->where('token', $validated['token'])
                    ->first();
            }

            if ($existingToken) {
                // Actualizar token existente
                $existingToken->update([
                    'token' => $validated['token'],
                    'platform' => $validated['platform'],
                    'device_name' => $validated['device_name'] ?? $existingToken->device_name,
                    'metadata' => array_merge($existingToken->metadata ?? [], $validated['metadata'] ?? []),
                    'last_used_at' => now(),
                    'is_active' => true,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Token actualizado correctamente',
                    'data' => [
                        'id' => $existingToken->id,
                        'token' => $existingToken->token,
                        'platform' => $existingToken->platform,
                        'device_info' => $existingToken->device_info,
                        'updated' => true,
                    ],
                ]);
            } else {
                // Crear nuevo token
                $deviceToken = UserDeviceToken::create([
                    'user_id' => $user->id,
                    'token' => $validated['token'],
                    'platform' => $validated['platform'],
                    'device_id' => $validated['device_id'],
                    'device_name' => $validated['device_name'],
                    'metadata' => $validated['metadata'] ?? [],
                    'last_used_at' => now(),
                    'is_active' => true,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Token registrado correctamente',
                    'data' => [
                        'id' => $deviceToken->id,
                        'token' => $deviceToken->token,
                        'platform' => $deviceToken->platform,
                        'device_info' => $deviceToken->device_info,
                        'created' => true,
                    ],
                ], 201);
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/device-tokens",
     *     summary="Listar tokens de dispositivos del usuario",
     *     tags={"Notificaciones"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="platform", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="active", in="query", required=false, @OA\Schema(type="boolean")),
     *     @OA\Response(response=200, description="Lista de tokens")
     * )
     * Obtener todos los tokens del usuario autenticado
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            $query = $user->deviceTokens();

            // Filtrar por plataforma si se especifica
            if ($request->has('platform')) {
                $query->byPlatform($request->platform);
            }

            // Filtrar por estado activo si se especifica
            if ($request->has('active')) {
                if ($request->boolean('active')) {
                    $query->active();
                } else {
                    $query->where('is_active', false);
                }
            }

            $tokens = $query->orderBy('last_used_at', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $tokens->map(function ($token) {
                    return [
                        'id' => $token->id,
                        'platform' => $token->platform,
                        'device_info' => $token->device_info,
                        'is_active' => $token->is_active,
                        'created_at' => $token->created_at->toISOString(),
                        'last_used_at' => $token->last_used_at?->toISOString(),
                        // No incluir el token por seguridad
                    ];
                }),
                'meta' => [
                    'total' => $tokens->count(),
                    'active' => $tokens->where('is_active', true)->count(),
                    'platforms' => $tokens->groupBy('platform')->keys()->toArray(),
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo tokens',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Desactivar un token específico
     */
    public function deactivate(Request $request, int $tokenId): JsonResponse
    {
        try {
            $user = Auth::user();

            $token = UserDeviceToken::where('user_id', $user->id)
                ->where('id', $tokenId)
                ->first();

            if (! $token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token no encontrado',
                ], 404);
            }

            $token->deactivate();

            return response()->json([
                'success' => true,
                'message' => 'Token desactivado correctamente',
                'data' => [
                    'id' => $token->id,
                    'is_active' => $token->is_active,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error desactivando token',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar tokens expirados (comando de limpieza)
     */
    public function cleanup(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $days = $request->get('days', 60); // Por defecto 60 días

            $expiredTokens = $user->deviceTokens()
                ->where('last_used_at', '<', now()->subDays($days))
                ->orWhere(function ($query) use ($days) {
                    $query->whereNull('last_used_at')
                        ->where('created_at', '<', now()->subDays($days));
                });

            $count = $expiredTokens->count();
            $expiredTokens->delete();

            return response()->json([
                'success' => true,
                'message' => "Se eliminaron {$count} tokens expirados",
                'data' => [
                    'deleted_count' => $count,
                    'criteria_days' => $days,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en limpieza de tokens',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Probar envío de notificación de prueba (solo desarrollo)
     */
    public function testNotification(Request $request): JsonResponse
    {
        if (! app()->environment('local', 'development')) {
            return response()->json([
                'success' => false,
                'message' => 'Endpoint solo disponible en desarrollo',
            ], 403);
        }

        try {
            $user = Auth::user();
            $tokens = $user->fcm_tokens;

            if (empty($tokens)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay tokens activos para este usuario',
                ]);
            }

            // Aquí iría la lógica de envío de FCM cuando esté implementada

            return response()->json([
                'success' => true,
                'message' => 'Notificación de prueba programada',
                'data' => [
                    'tokens_count' => count($tokens),
                    'test_payload' => [
                        'title' => 'Notificación de prueba',
                        'body' => 'Esta es una notificación de prueba del sistema',
                        'data' => [
                            'type' => 'general',
                            'action' => 'view',
                            'timestamp' => now()->toISOString(),
                        ],
                    ],
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error enviando notificación de prueba',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
