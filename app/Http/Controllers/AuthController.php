<?php

namespace App\Http\Controllers;

use App\Exceptions\Api\Auth\UnauthorizedException;
use App\Http\Requests\AuthRegisterRequest;
use App\Http\Requests\AuthRegisterCompleteRequest;
use App\Http\Requests\AuthUpdateFotoPerfilRequest;
use App\Http\Resources\UserAuthenticateResource;
use App\Http\Resources\UserResource;
use App\Mail\CompletaRegistroUsuarioMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;


class AuthController extends Controller
{

    /**
     * @OA\Post(
     *     path="/api/auth/register",
     *     tags={"Autenticación"},
     *     summary="Iniciar registro de usuario (Constructor o Solicitante)",
     *     description="Guarda datos preliminares del usuario y envía correo con token para completar el registro.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/AuthRegisterRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token generado y enviado por correo",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Datos guardados. Revisa tu correo para continuar el registro."),
     *             @OA\Property(property="token", type="string", example="kJH23jhkL23JKnlk2323jh2h3k4")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos o incompletos"
     *     )
     * )
     */

    public function register(AuthRegisterRequest $request)
    {
        $validatedData = $request->validated();
        $token = Str::random(60);
        Cache::put("registro_user_construcc{$token}", $validatedData, 60 * 24 * 365);
        $url = config('services.frontend.url') . "/auth/completar-registro-proveedor?token={$token}";
        Mail::to($validatedData['email'])->send(new CompletaRegistroUsuarioMail($url));


        return $this->success([
            'message' => 'Datos guardados. Revisa tu correo para continuar el registro.',
            'url' => $url
        ], 'Proveedor pendiente de completar registro');
    }


    /**
     * @OA\Post(
     *     path="/api/auth/completar-registro",
     *     tags={"Autenticación"},
     *     summary="Completar registro de usuario (Constructor o Solicitante)",
     *     description="Completa el registro del usuario usando el token previamente enviado por correo.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/AuthRegisterCompleteRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Usuario registrado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Registro completado."),
     *             @OA\Property(property="user", ref="#/components/schemas/UserResource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=498,
     *         description="Token inválido o expirado"
     *     )
     * )
     */

    public function register_completar(AuthRegisterCompleteRequest $request)
    {
        $data = Cache::get("registro_user_construcc{$request->token}");

        if (!$data) {
            return $this->error('Token inválido o expirado', [], 498);
        }

        $user = User::create([
            'name' => $data['nombre_comercial'],
            'email' => $data['email'],
            'password' => Hash::make($request->password),
        ]);

        // TODO: Add request to CONSTRUCC APP
        // ...

        return $this->success([
            'message' => 'Datos guardados. Registro completado con exito.',
            'user' => new UserResource($user->load(User::eagerLodable()))
        ], 'Proveedor pendiente de completar registro');
    }

    /**
     * @OA\Post(
     *     path="/api/update-foto-perfil",
     *     summary="Actualizar foto de perfil",
     *     tags={"Autenticación"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"foto_perfil"},
     *                 @OA\Property(property="foto_perfil", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Foto de perfil actualizada con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(property="path", type="string", example="/storage/uploads/unique_filename.jpg")
     *         )
     *     )
     * )
     */
    public function update_foto_perfil(AuthUpdateFotoPerfilRequest $request)
    {
        $file = $request->file('foto_perfil');

        $nombre = uniqid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('uploads', $nombre, 'public');
        $url = asset("storage/$path");
        $user = $request->user();
        $user->foto_perfil_url = $url;
        $user->save();

        return $this->success(
            ['path' => $url],
            'Foto de perfil actualizada con éxito',
            201
        );
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

        $user->load(User::eagerLodable());

        return $this->success([
            'user' => new UserAuthenticateResource($user),
            'token' => $token,
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
        if (!$request->user()) {
            throw new UnauthorizedException("No autorizado o sesión no válida");
        }

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
