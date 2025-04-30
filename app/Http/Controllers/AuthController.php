<?php

namespace App\Http\Controllers;

use App\Enums\UserRoleEnumerate;
use App\Exceptions\Api\Auth\RegistrationException;
use App\Exceptions\Api\Auth\UnauthorizedException;
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
     *             required={"razon_social", "nombre_comercial", "rfc", "email", "password", "telefono", "direccion"},
     *             @OA\Property(property="razon_social", type="string", example="Proveedor S.A. de C.V."),
     *             @OA\Property(property="nombre_comercial", type="string", example="Proveedor Comercial"),
     *             @OA\Property(property="rfc", type="string", example="QUMA470929F37"),
     *             @OA\Property(property="email", type="string", format="email", example="proveedor@empresa.com"),
     *             @OA\Property(property="password", type="string", format="password", example="contraseñaSegura123"),
     *             @OA\Property(property="telefono", type="string", example="1234567890"),
     *             @OA\Property(property="direccion", type="string", example="Av. Ejemplo 123, Ciudad, Estado")
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
     *                 @OA\Property(property="email", type="string", format="email", example="proveedor@empresa.com"),
     *                 @OA\Property(property="telefono", type="string", example="1234567890"),
     *                 @OA\Property(property="direccion", type="string", example="Av. Ejemplo 123, Ciudad, Estado")
     *             ),
     *             @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOi...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación en el registro",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error_type", type="string", example="registration_error"),
     *             @OA\Property(property="message", type="string", example="Error al registrar el proveedor. Campos no validados."),
     *             @OA\Property(property="code", type="integer", example=422),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="email", type="array",
     *                     @OA\Items(type="string", example="El correo electrónico debe ser válido.")
     *                 ),
     *                 @OA\Property(property="password", type="array",
     *                     @OA\Items(type="string", example="La contraseña debe tener al menos 8 caracteres.")
     *                 )
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
            'rfc' => 'required|string|max:13',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'telefono' => 'required|string|max:15',
            'direccion' => 'required|string|max:255',
        ], [
            'razon_social.required' => 'La razón social es obligatoria.',
            'nombre_comercial.required' => 'El nombre comercial es obligatorio.',
            'rfc.required' => 'El RFC es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico debe ser válido.',
            'email.unique' => 'Esta empresa ya está registrada en el sistema, por favor contacte a soporte técnico.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'telefono.required' => 'El teléfono es obligatorio.',
            'telefono.max' => 'El teléfono no debe exceder los 15 caracteres.',
            'direccion.required' => 'La dirección es obligatoria.',
            'direccion.max' => 'La dirección no debe exceder los 255 caracteres.',
        ]);

        if ($validator->fails()) {
            // Lanzamos la excepción de validación personalizada
            throw new RegistrationException("Error al registrar el proveedor. Campos no validados.", $validator->errors());
        }

        try {
            // Iniciar transacción
            DB::beginTransaction();

            // Crear el usuario (proveedor)
            $user = User::create([
                'name' => $request->nombre_comercial,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => UserRoleEnumerate::PROVEEDOR->value,
            ]);

            // Crear el proveedor asociado al usuario
            $proveedor = Proveedor::create([
                'razon_social' => $request->razon_social,
                'nombre_comercial' => $request->nombre_comercial,
                'rfc' => $request->rfc,
                'email' => $request->email,
                'telefono' => $request->telefono,
                'direccion' => $request->direccion,
                'user_id' => $user->id,
            ]);

            // Confirmar transacción
            DB::commit();

            // Crear token de autenticación con Sanctum
            $token = $user->createToken('sanctum')->plainTextToken;

            return $this->success(
                [
                    'user' => $user,
                    'proveedor' => $proveedor,
                    'token' => $token
                ],
                'Proveedor registrado correctamente',
                201
            );
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
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Login exitoso."),
     *             @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOi..."),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Juan Pérez"),
     *                 @OA\Property(property="email", type="string", format="email", example="juan@example.com")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Credenciales inválidas",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Credenciales incorrectas."),
     *             @OA\Property(property="code", type="integer", example=401)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error en el inicio de sesión."),
     *             @OA\Property(property="error", type="string", example="Mensaje de excepción"),
     *             @OA\Property(property="code", type="integer", example=500)
     *         )
     *     )
     * )
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw new UnauthorizedException("Credenciales incorrectas.");
        }

        $token = $user->createToken('API Token')->plainTextToken;

        return $this->success([
            'user' => $user,
            'token' => $token
        ], 'Login exitoso.', 201);
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
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Usuario autenticado."),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Juan Pérez"),
     *                 @OA\Property(property="email", type="string", format="email", example="juan@example.com")
     *             )
     *         )
     *     )
     * )
     */
    public function me(Request $request)
    {
        return $this->success(
            [
                'success' => true,
                'user' => $request->user()
            ],
            'Usuario autenticado.',
            200
        );
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
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Sesión cerrada correctamente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado o sesión no válida",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No autorizado o sesión no válida")
     *         )
     *     )
     * )
     */
    public function logout(Request $request)
    {
        // Verificar si el usuario está autenticado
        if (!$request->user()) {
            throw new UnauthorizedException("No autorizado o sesión no válida");
        }

        // Revocar todos los tokens del usuario autenticado
        $request->user()->tokens()->delete();

        return $this->success(
            [
                'success' => true,
            ],
            'Sesión cerrada correctamente',
            200
        );
    }
}
