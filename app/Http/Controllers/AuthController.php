<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/register",
     *     summary="Registrar un proveedor",
     *     tags={"Autenticación"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"razon_social", "nombre_comercial", "email", "password"},
     *             @OA\Property(property="razon_social", type="string", example="Proveedor S.A. de C.V."),
     *             @OA\Property(property="nombre_comercial", type="string", example="Proveedor Comercial"),
     *             @OA\Property(property="email", type="string", format="email", example="proveedor@empresa.com"),
     *             @OA\Property(property="password", type="string", format="password", example="contraseñaSegura123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Proveedor registrado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Proveedor registrado correctamente"),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Proveedor Comercial"),
     *                 @OA\Property(property="email", type="string", format="email", example="proveedor@empresa.com")
     *             ),
     *             @OA\Property(property="proveedor", type="object",
     *                 @OA\Property(property="razon_social", type="string", example="Proveedor S.A. de C.V."),
     *                 @OA\Property(property="nombre_comercial", type="string", example="Proveedor Comercial"),
     *                 @OA\Property(property="email", type="string", format="email", example="proveedor@empresa.com")
     *             ),
     *             @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOi...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error en el registro"),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="razon_social", type="array", @OA\Items(type="string", example="La razón social es obligatoria.")),
     *                 @OA\Property(property="nombre_comercial", type="array", @OA\Items(type="string", example="El nombre comercial es obligatorio.")),
     *                 @OA\Property(property="email", type="array", @OA\Items(type="string", example="El correo electrónico es obligatorio.")),
     *                 @OA\Property(property="password", type="array", @OA\Items(type="string", example="La contraseña es obligatoria."))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error en el registro"),
     *             @OA\Property(property="error", type="string", example="El mensaje de error generado en la transacción")
     *         )
     *     )
     * )
     */
    public function register(Request $request)
    {
        // Validación de entrada
        $validator = Validator::make($request->all(), [
            'razon_social' => 'required|string|max:255',
            'nombre_comercial' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ], [
            'razon_social.required' => 'La razón social es obligatoria.',
            'nombre_comercial.required' => 'El nombre comercial es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico debe ser válido.',
            'email.unique' => 'Esta empresa ya está registrada en el sistema, por favor contacte a soporte técnico.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error en el registro',
                'errors' => $validator->errors()
            ], 422, [], JSON_UNESCAPED_UNICODE);
        }

        try {
            // Iniciar transacción
            DB::beginTransaction();

            // Crear el usuario (proveedor)
            $user = User::create([
                'name' => $request->nombre_comercial,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // Crear el proveedor asociado al usuario
            $proveedor = Proveedor::create([
                'razon_social' => $request->razon_social,
                'nombre_comercial' => $request->nombre_comercial,
                'email' => $request->email,
                'user_id' => $user->id,
            ]);

            // Confirmar transacción
            DB::commit();

            // Crear token de autenticación con Sanctum
            $token = $user->createToken('ProveedorToken')->plainTextToken;

            return response()->json([
                'message' => 'Proveedor registrado correctamente',
                'user' => $user,
                'proveedor' => $proveedor,
                'token' => $token
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error en el registro',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="Autenticación de usuario",
     *     tags={"Autenticación"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="secret123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login exitoso",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOi...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Credenciales inválidas"
     *     )
     * )
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return response()->json(['error' => 'Credenciales incorrectas'], 401);
        }

        // Obtener usuario autenticado
        $user = Auth::user();

        // Generar token con Sanctum
        $token = $user->createToken('ProveedorToken')->plainTextToken;

        return response()->json([
            'message' => 'Login exitoso',
            'token' => $token,
            'user' => $user
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/me",
     *     summary="Obtener información del usuario autenticado",
     *     tags={"Autenticación"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Usuario autenticado",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Juan Pérez"),
     *             @OA\Property(property="email", type="string", format="email", example="juan@example.com")
     *         )
     *     )
     * )
     */
    public function me(Request $request)
    {
        return response()->json(['user' => $request->user()], 200);
    }

/**
 * @OA\Post(
 *     path="/api/logout",
 *     summary="Cerrar sesión y revocar tokens",
 *     tags={"Autenticación"},
 *     security={{"sanctum":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Sesión cerrada correctamente",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Sesión cerrada correctamente")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="No autenticado o sesión no válida",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="No autorizado o sesión no válida")
 *         )
 *     )
 * )
 */
public function logout(Request $request)
{
    // Revocar todos los tokens del usuario autenticado
    $request->user()->tokens()->delete();

    return response()->json(['message' => 'Sesión cerrada correctamente'], 200);
}
}
