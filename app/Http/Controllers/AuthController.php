<?php

namespace App\Http\Controllers;

use App\Enums\UserRoleEnumerate;
use App\Exceptions\Api\Auth\UnauthorizedException;
use App\Http\Requests\Auth\AuthRegisterCompleteRequest;
use App\Http\Requests\Auth\AuthRegisterRequest;
use App\Http\Requests\Auth\AuthUpdateCredentialsRequest;
use App\Http\Requests\Auth\AuthUpdateFotoPerfilRequest;
use App\Http\Requests\Auth\PasswordResetRequest;
use App\Http\Requests\Auth\PasswordResetCompleteRequest;
use App\Http\Requests\Proveedor\ProveedorRegisterCompleteRequest;
use App\Http\Requests\Proveedor\ProveedorRegisterRequest;
use App\Http\Requests\Proveedor\ProveedorRegistroBasicoCompleteRequest;
use App\Http\Requests\Proveedor\ProveedorRegistroBasicoRequest;
use App\Http\Resources\ProveedorResource;
use App\Http\Resources\UserAuthenticateResource;
use App\Mail\CompletaRegistroProveedorMail;
use App\Mail\CompletaRegistroUsuarioMail;
use App\Mail\ValidaCorreoProveedorBasicoMail;
use App\Mail\PasswordResetMail;
use App\Models\Proveedor;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(AuthRegisterRequest $request)
    {
        $validatedData = $request->validated();
        $token = Str::random(60);
        Cache::put("registro_user_construcc{$token}", $validatedData, 60 * 24 * 365);
        $url = config('services.frontend.url')."/gen-pass?is_user_construcc=true&token={$token}";
        Mail::to($validatedData['email'])->send(new CompletaRegistroUsuarioMail($url));

        return $this->success(
            [
                ...$validatedData,
                'url' => $url,

            ],
            'Datos guardados. Revisa tu correo para continuar el registro.'
        );
    }

    public function register_completar(AuthRegisterCompleteRequest $request)
    {
        $data = Cache::get("registro_user_construcc{$request->token}");

        if (! $data) {
            return $this->error('Token inválido o expirado', [], 498);
        }

        $user = User::create([
            'name' => $data['nombre_empresa'],
            'email' => $data['email'],
            'password' => Hash::make($request->password),
            'role_id' => Role::where('nombre', UserRoleEnumerate::USUARIO->value)->first()->id,
        ]);

        // TODO: Add request to CONSTRUCC APP
        // ...

        return $this->success($data, 'Proveedor pendiente de completar registro');
        // [
        //     'user' => new UserResource($user->load(User::eagerLodable())),
        //     'data' => $data
        // ],
    }

    public function register_proveedor(ProveedorRegisterRequest $request)
    {
        $proveedor = Proveedor::create($request->validated());
        $token = Str::random(60);

        Cache::put("registro_proveedor_{$token}", $proveedor->id, 60 * 60 * 24 * 7 * 360); // 1 año

        $url = config('services.frontend.url')."/gen-pass?token={$token}";
        Mail::to($proveedor->email)->send(new CompletaRegistroProveedorMail($url));

        return $this->success([
            'url' => $url,
            'data' => $proveedor->load(Proveedor::eagerLodable()),
        ], 'Proveedor registrado. Revisa tu correo para continuar.', 200);
    }

    public function register_proveedor_completar(ProveedorRegisterCompleteRequest $request)
    {
        $proveedorId = Cache::get("registro_proveedor_{$request->token}");
        if (! $proveedorId) {
            return $this->error('Token inválido o expirado', [], 498);
        }

        $proveedor = Proveedor::findOrFail($proveedorId);

        if (! $proveedor->user) {
            $idRoleProveedor = Role::where('nombre', UserRoleEnumerate::GERENTE->value)->first()->id;
            $user = User::create([
                'name' => $proveedor->nombre_comercial,
                'email' => $proveedor->email,
                'password' => Hash::make($request->password),
                'role_id' => $idRoleProveedor,
            ]);

            $user->proveedores()->attach($proveedor->id, [
                'tipo_relacion' => 'PRINCIPAL',
                'activo' => true,
                'fecha_asignacion' => now(),
                'observaciones' => 'Usuario principal del proveedor',
            ]);
        } else {
            $user = $proveedor->user;
            $user->password = Hash::make($request->password);
            $user->save();
        }

        /**
         * Crear sucursal matriz por defecto si el proveedor aún no tiene ninguna.
         */
        if (! $proveedor->sucursales()->exists()) {
            $proveedor->sucursales()->create([
                'nombre' => 'Matriz',
                'direccion' => $proveedor->direccion ?? 'Dirección pendiente',
                'telefono' => $proveedor->telefono ?? '0000000000',
                'email' => $proveedor->email,
                'encargado' => $proveedor->nombre_comercial,
                'activa' => true,
                'coordenadas_lat' => null,
                'coordenadas_lng' => null,
                'estatus' => 'activo',
            ]);
        }

        Cache::forget("registro_proveedor_{$request->token}");
        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success([
            'user' => new UserAuthenticateResource($user->load(User::eagerLodable())),
            'proveedor' => new ProveedorResource($proveedor->load(Proveedor::eagerLodable())),
            'token' => $token,
        ], 'Registro completado', 201);
    }

    public function update_foto_perfil(AuthUpdateFotoPerfilRequest $request)
    {
        $file = $request->file('foto_perfil');

        $nombre = uniqid().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs('uploads', $nombre, 'public');
        $url = asset("storage/$path");
        $user = $request->user();
        $user->foto_perfil_url = $url;
        $user->save();

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success([
            'user' => new UserAuthenticateResource($user->load(User::eagerLodable())),
            'token' => $token,
        ], 'Registro completado', 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required'], // 'email'],
            'password' => ['required'],
        ]);

        // Buscar usuario por email o teléfono
        $user = User::where('email', $request->email)->first();
        
        // Si no se encuentra por email, buscar por teléfono, razón social o nombre comercial del proveedor
        if (!$user) {
            $proveedor = Proveedor::where('razon_social', $request->email)
                ->orWhere('nombre_comercial', $request->email)
                ->orWhere('telefono', $request->email)
                ->first();
            
            if ($proveedor) {
                $user = $proveedor->users()->first();
            }
        }

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw new UnauthorizedException('Credenciales incorrectas.');
        }

        $token = $user->createToken('API Token')->plainTextToken;

        $user->load(User::eagerLodable());

        return $this->success([
            'user' => new UserAuthenticateResource($user),
            'token' => $token,
        ], 'Login exitoso.', 201);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        $user->load(User::eagerLodable());

        if ($user->proveedores()->count() == 0) {
            return $this->success([
                'user' => new UserAuthenticateResource($user),
                'token' => null,
                'proveedor' => null,
            ], 'Login exitoso.', 200);
        }

        $proveedor = $user->proveedorPrincipal();

        return $this->success([
            'user' => new UserAuthenticateResource($user),
            'token' => null,
            'proveedor' => new ProveedorResource($proveedor),
        ], 'Login exitoso.', 200);
    }

    public function refresh(Request $request)
    {
        if (! $request->user()) {
            throw new UnauthorizedException('No autorizado o sesión no válida');
        }

        $user = $request->user();

        // Revocar el token actual
        $request->user()->currentAccessToken()->delete();

        // Crear un nuevo token
        $newToken = $user->createToken('API Token')->plainTextToken;

        // Cargar relaciones necesarias
        $user->load(User::eagerLodable());
        $proveedor = $user->proveedorPrincipal();

        return $this->success([
            'user' => new UserAuthenticateResource($user),
            'token' => $newToken,
            'proveedor' => new ProveedorResource($proveedor),
        ], 'Token renovado exitosamente', 200);
    }

    public function logout(Request $request)
    {
        if (! $request->user()) {
            throw new UnauthorizedException('No autorizado o sesión no válida');
        }

        // Solo eliminar el token del dispositivo actual, no todos los tokens
        $request->user()->currentAccessToken()->delete();

        return $this->success(
            [
                'success' => true,
            ],
            'Sesión cerrada correctamente',
            200
        );
    }

    public function updateUser(Request $request)
    {
        $user = $request->user();

        if ($request->filled('nombre')) {
            $user->name = $request->input('nombre');
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($request->input('password'));
        }

        $user->save();

        // Regenerar el token
        $request->user()->currentAccessToken()->delete();
        $newToken = $user->createToken('API Token')->plainTextToken;

        // Cargar relaciones necesarias
        $user->load(User::eagerLodable());
        $proveedor = $user->proveedorPrincipal();

        return $this->success([
            'user' => new UserAuthenticateResource($user),
            'token' => $newToken,
            'proveedor' => new ProveedorResource($proveedor),
        ], 'Token renovado exitosamente', 200);
    }

    /**
     * Registrar un nuevo proveedor (versión básica sin usuario asociado)
     * Envía un correo de validación para que el usuario cree su contraseña
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register_proveedor_basico_sp(ProveedorRegistroBasicoRequest $request)
    {
        $validatedData = $request->validated();
        
        // Crear proveedor
        $proveedor = Proveedor::create([
            'nombre_comercial' => $validatedData['nombre_comercial'],
            'razon_social' => $validatedData['nombre_comercial'], // Usar el mismo nombre comercial por defecto
            'telefono' => $validatedData['telefono'],
            'is_proveedor_sp' => true,
            'is_proveedor_catalogo' => false,
            'cambiar_pass_default' => false,
            'perfil_empresa_completo' => false,
        ]);

        // Obtener rol de gerente
        $idRoleProveedor = Role::where('nombre', UserRoleEnumerate::GERENTE->value)->first()->id;

        // Crear usuario usando el teléfono como identificador
        $user = User::create([
            'name' => $validatedData['nombre_comercial'],
            'email' => $validatedData['telefono'], // Usar teléfono como email/usuario
            'password' => Hash::make($validatedData['password']),
            'role_id' => $idRoleProveedor,
        ]);

        // Relacionar usuario con proveedor
        $user->proveedores()->attach($proveedor->id, [
            'tipo_relacion' => 'PRINCIPAL',
            'activo' => true,
            'fecha_asignacion' => now(),
            'observaciones' => 'Usuario principal del proveedor - Registro por enlace',
        ]);

        // Crear sucursal matriz por defecto
        $proveedor->sucursales()->create([
            'nombre' => 'Matriz',
            'direccion' => 'Dirección pendiente',
            'telefono' => $validatedData['telefono'],
            'email' => $validatedData['telefono'] . '@temp.com',
            'encargado' => $validatedData['nombre_comercial'],
            'activa' => true,
            'coordenadas_lat' => null,
            'coordenadas_lng' => null,
            'estatus' => 'activo',
        ]);

        // Registrar relación con empresa Construcc si se proporcionaron los datos
        if (isset($validatedData['empresa_construcc_id'])) {
            $proveedor->empresasConstrucc()->attach($validatedData['empresa_construcc_id'], [
                'usuario_construcc_id' => $validatedData['usuario_construcc_id'] ?? null,
                'usuario_construcc_nombre' => $validatedData['usuario_construcc_nombre'] ?? null,
            ]);
        }

        // Crear token de autenticación
        $token = $user->createToken('auth_token')->plainTextToken;
        $user->load(User::eagerLodable());

        // Retornar información para el modal (sin mostrar la contraseña)
        return $this->success([
            'user' => new UserAuthenticateResource($user),
            'proveedor' => new ProveedorResource($proveedor->load(Proveedor::eagerLodable())),
            'token' => $token,
            'credenciales' => [
                'usuario' => $validatedData['telefono'],
                'mensaje' => 'Guarda tu usuario para iniciar sesión',
            ],
        ], 'Registro completado exitosamente', 201);
    }

    /**
     * Completar el registro básico del proveedor con contraseña
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register_proveedor_basico_completar(ProveedorRegistroBasicoCompleteRequest $request)
    {
        $data = Cache::get("registro_proveedor_basico_{$request->token}");

        if (! $data) {
            return $this->error('Token inválido o expirado', [], 498);
        }

        // Crear proveedor con datos del cache
        $proveedor = Proveedor::create([
            'empresa' => $data['empresa'],
            'nombre_comercial' => $data['alias'] ?? $data['empresa'],
            'razon_social' => $data['razon_social'],
            'email' => $data['email'],
            'telefono' => $data['telefono'],
            'is_proveedor_sp' => true,
            'is_proveedor_catalogo' => false,
            'cambiar_pass_default' => false,
            'perfil_empresa_completo' => false,
        ]);

        // Obtener rol de gerente
        $idRoleProveedor = Role::where('nombre', UserRoleEnumerate::GERENTE->value)->first()->id;

        // Crear usuario
        $user = User::create([
            'name' => $data['empresa'],
            'email' => $data['email'],
            'password' => Hash::make($request->password),
            'role_id' => $idRoleProveedor,
        ]);

        // Relacionar usuario con proveedor
        $user->proveedores()->attach($proveedor->id, [
            'tipo_relacion' => 'PRINCIPAL',
            'activo' => true,
            'fecha_asignacion' => now(),
            'observaciones' => 'Usuario principal del proveedor',
        ]);

        /**
         * Crear sucursal matriz por defecto si el proveedor aún no tiene ninguna.
         */
        if (! $proveedor->sucursales()->exists()) {
            $proveedor->sucursales()->create([
                'nombre' => 'Matriz',
                'direccion' => $proveedor->direccion ?? 'Dirección pendiente',
                'telefono' => $proveedor->telefono ?? '0000000000',
                'email' => $proveedor->email,
                'encargado' => $proveedor->nombre_comercial,
                'activa' => true,
                'coordenadas_lat' => null,
                'coordenadas_lng' => null,
                'estatus' => 'activo',
            ]);
        }

        // Eliminar datos del cache
        Cache::forget("registro_proveedor_basico_{$request->token}");

        // Crear token de autenticación
        $token = $user->createToken('auth_token')->plainTextToken;
        $user->load(User::eagerLodable());

        return $this->success([
            'user' => new UserAuthenticateResource($user),
            'proveedor' => new ProveedorResource($proveedor->load(Proveedor::eagerLodable())),
            'token' => $token,
        ], 'Registro completado exitosamente', 201);
    }

    /**
     * Actualizar la contraseña del usuario autenticado
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePassword(AuthUpdateCredentialsRequest $request)
    {
        $user = $request->user();

        // Verificar que la contraseña actual sea correcta
        if (! Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['La contraseña actual no es correcta.'],
            ]);
        }

        // Actualizar la nueva contraseña
        $user->password = Hash::make($request->new_password);
        $user->save();

        // Revocar token actual y generar uno nuevo
        $request->user()->currentAccessToken()->delete();
        $newToken = $user->createToken('API Token')->plainTextToken;

        // Cargar relaciones necesarias
        $user->load(User::eagerLodable());
        $proveedor = $user->proveedorPrincipal();

        // Si el proveedor tiene la bandera cambiar_pass_default en true, actualizarla a false
        $proveedor->cambiar_pass_default = false;
        $proveedor->save();

        return $this->success([
            'user' => new UserAuthenticateResource($user),
            'token' => $newToken,
            'proveedor' => new ProveedorResource($proveedor),
        ], 'Contraseña actualizada correctamente.', 200);
    }

    /**
     * Solicitar recuperación de contraseña
     *
     * @param PasswordResetRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function requestPasswordReset(PasswordResetRequest $request)
    {
        $email = $request->email;
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            // Retornar éxito aunque no exista para evitar enumeration attacks
            return $this->success(
                ['email' => $email],
                'Si existe una cuenta con este correo, recibirás las instrucciones para recuperar tu contraseña.',
                200
            );
        }

        // Generar token único
        $token = Str::random(60);
        
        // Guardar en cache con expiración de 1 hora
        Cache::put("password_reset_{$token}", [
            'user_id' => $user->id,
            'email' => $user->email,
            'created_at' => now()
        ], 60 * 60); // 1 hora

        // Generar URL para reset
        $url = config('services.frontend.url') . "/auth/reset-password?token={$token}";
        
        // Enviar email
        Mail::to($user->email)->send(new PasswordResetMail($url, $user->name));

        return $this->success(
            ['email' => $email],
            'Si existe una cuenta con este correo, recibirás las instrucciones para recuperar tu contraseña.',
            200
        );
    }

    /**
     * Resetear contraseña con token
     *
     * @param PasswordResetCompleteRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword(PasswordResetCompleteRequest $request)
    {
        $data = Cache::get("password_reset_{$request->token}");
        
        if (!$data) {
            return $this->error(
                'El enlace de recuperación ha expirado o es inválido. Por favor, solicita uno nuevo.',
                [],
                400
            );
        }

        // Verificar que no haya pasado más de 1 hora
        $createdAt = \Carbon\Carbon::parse($data['created_at']);
        if ($createdAt->diffInMinutes(now()) > 60) {
            Cache::forget("password_reset_{$request->token}");
            return $this->error(
                'El enlace de recuperación ha expirado. Por favor, solicita uno nuevo.',
                [],
                400
            );
        }

        // Buscar usuario
        $user = User::find($data['user_id']);
        
        if (!$user) {
            return $this->error(
                'Usuario no encontrado.',
                [],
                404
            );
        }

        // Actualizar contraseña
        $user->password = Hash::make($request->password);
        $user->save();

        // Eliminar token del cache
        Cache::forget("password_reset_{$request->token}");

        // Crear nuevo token de autenticación
        $token = $user->createToken('API Token')->plainTextToken;
        $user->load(User::eagerLodable());
        
        // Obtener proveedor si existe
        $proveedor = $user->proveedorPrincipal();

        return $this->success([
            'user' => new UserAuthenticateResource($user),
            'token' => $token,
            'proveedor' => $proveedor ? new ProveedorResource($proveedor) : null,
        ], 'Contraseña restablecida exitosamente.', 200);
    }

    /**
     * Verificar si un correo electrónico ya está registrado
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verificarEmailExistente(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Verificar si el correo existe en la tabla users
        $existe = User::where('email', $request->email)->exists();

        return $this->success([
            'existe' => $existe,
            'email' => $request->email,
        ], $existe ? 'El correo ya está registrado.' : 'El correo está disponible.', 200);
    }

    /**
     * Verificar si una razón social/nombre comercial ya está registrado
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verificarRazonSocialExistente(Request $request)
    {
        $request->validate([
            'razon_social' => ['required', 'string'],
        ]);

        // Verificar si la razón social existe en la tabla proveedores
        $existe = Proveedor::where('razon_social', $request->razon_social)->exists();

        return $this->success([
            'existe' => $existe,
            'razon_social' => $request->razon_social,
        ], $existe ? 'La razón social ya está registrada.' : 'La razón social está disponible.', 200);
    }

    /**
     * Verificar si un teléfono ya está registrado
     * Busca en proveedores.telefono y users.email
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verificarTelefonoExistente(Request $request)
    {
        $request->validate([
            'telefono' => ['required', 'string'],
        ]);

        $telefono = $request->telefono;

        // Verificar si el teléfono existe en la tabla proveedores
        $existeEnProveedores = Proveedor::where('telefono', $telefono)->exists();

        // Verificar si el teléfono existe como email en users (se usa como username)
        $existeEnUsers = User::where('email', $telefono)->exists();

        $existe = $existeEnProveedores || $existeEnUsers;

        return $this->success([
            'existe' => $existe,
            'telefono' => $telefono,
        ], $existe ? 'El teléfono ya está registrado.' : 'El teléfono está disponible.', 200);
    }
}
