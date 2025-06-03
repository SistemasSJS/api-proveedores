<?php

namespace App\Http\Controllers;

use App\Enums\UserRoleEnumerate;
use App\Exceptions\Api\Auth\UnauthorizedException;

use App\Http\Requests\Auth\AuthRegisterRequest;
use App\Http\Requests\Auth\AuthRegisterCompleteRequest;
use App\Http\Requests\Auth\AuthUpdateFotoPerfilRequest;
use App\Http\Requests\Proveedor\ProveedorRegisterCompleteRequest;
use App\Http\Requests\Proveedor\ProveedorRegisterRequest;
use App\Http\Resources\UserAuthenticateResource;
use App\Mail\CompletaRegistroProveedorMail;
use App\Mail\CompletaRegistroUsuarioMail;
use App\Models\Proveedor;
use App\Models\Role;
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
        $url = config('services.frontend.url') . "/auth/registro/completar?is_user_construcc=true&token={$token}";
        Mail::to($validatedData['email'])->send(new CompletaRegistroUsuarioMail($url));


        return $this->success(
            [
                ...$validatedData,
                'url' => $url

            ],
            'Datos guardados. Revisa tu correo para continuar el registro.'
        );
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
            'name' => $data['nombre_empresa'],
            'email' => $data['email'],
            'password' => Hash::make($request->password),
            'role_id' => Role::where('nombre', UserRoleEnumerate::USUARIO_CONSTRUCCION->value)->first()->id
        ]);

        // TODO: Add request to CONSTRUCC APP
        // ...

        return $this->success($data, 'Proveedor pendiente de completar registro');
        // [
        //     'user' => new UserResource($user->load(User::eagerLodable())),
        //     'data' => $data
        // ],
    }

    /**
     * @OA\Post(
     *     path="/api/register_proveedor",
     *     tags={"Autenticación"},
     *     summary="Registrar un nuevo proveedor",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ProveedorRegisterRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Proveedor creado exitosamente"
     *     )
     * )
     */
    public function register_proveedor(ProveedorRegisterRequest $request)
    {
        /**
         * FIXME: Validar correo IN USERS TABLAE
         */


        $proveedor = Proveedor::create($request->validated());
        $token = Str::random(60);

        Cache::put("registro_proveedor_{$token}", $proveedor->id, 60 * 60 * 24 * 7 * 360); // 1 año

        $url = config('services.frontend.url') . "/auth/registro/completar?token={$token}";
        Mail::to($proveedor->email)->send(new CompletaRegistroProveedorMail($url));

        return $this->success([
            'url' => $url,
            'data' => $proveedor->load(Proveedor::eagerLodable())
        ], 'Proveedor registrado. Revisa tu correo para continuar.', 200);
    }

    /**
     * @OA\Post(
     *     path="/api/register_proveedor_completar",
     *     tags={"Autenticación"},
     *     summary="Completar registro de proveedor",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ProveedorRegisterCompleteRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Registro completado exitosamente"
     *     )
     * )
     */
    public function register_proveedor_completar(ProveedorRegisterCompleteRequest $request)
    {
        $proveedorId = Cache::get("registro_proveedor_{$request->token}");
        if (!$proveedorId) {
            return $this->error('Token inválido o expirado', [], 498);
        }

        $proveedor = Proveedor::findOrFail($proveedorId);

        if (!$proveedor->user) {
            $idRoleProveedor = Role::where('nombre', 'PROVEEDOR')->first()->id;
            $user = User::create([
                'name' => $proveedor->nombre_comercial,
                'email' => $proveedor->email,
                'password' => Hash::make($request->password),
                'role_id' => $idRoleProveedor,
            ]);

            $user->proveedores()->attach($proveedor->id, ['is_main' => true]);
        } else {
            $user = $proveedor->user;
            $user->password = Hash::make($request->password);
            $user->save();
        }

        Cache::forget("registro_proveedor_{$request->token}");
        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success([
            'user' => new UserAuthenticateResource($user->load(User::eagerLodable())),
            'proveedor' => $proveedor->load(Proveedor::eagerLodable()),
            'token' => $token,
        ], 'Registro completado', 201);
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
