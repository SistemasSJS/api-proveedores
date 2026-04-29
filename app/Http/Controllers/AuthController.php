<?php

namespace App\Http\Controllers;

use App\Enums\EstadoUsuario;
use App\Enums\UserRoleEnumerate;
use App\Exceptions\Api\Auth\UnauthorizedException;
use App\Http\Requests\Auth\AuthRegisterCompleteRequest;
use App\Http\Requests\Auth\AuthRegisterRequest;
use App\Http\Requests\Auth\AuthUpdateCredentialsRequest;
use App\Http\Requests\Auth\AuthUpdateFotoPerfilRequest;
use App\Http\Requests\Auth\AuthUpdateUserDataRequest;
use App\Http\Requests\Auth\PasswordResetRequest;
use App\Http\Requests\Auth\PasswordResetCompleteRequest;
use App\Http\Requests\Proveedor\ProveedorRegisterCompleteRequest;
use App\Http\Requests\Proveedor\ProveedorRegisterRequest;
use App\Http\Requests\Proveedor\ProveedorRegistroBasicoCompleteRequest;
use App\Http\Requests\Proveedor\ProveedorRegistroBasicoRequest;
use App\Http\Requests\Proveedor\ProveedorAsociarEmpresaRequest;
use App\Http\Requests\Auth\CompletarRegistroProveedorRequest;
use App\Http\Resources\ProveedorResource;
use App\Http\Resources\UserAuthenticateResource;
use App\Mail\CompletaRegistroProveedorMail;
use App\Mail\CompletaRegistroUsuarioMail;
use App\Mail\VerifyUpdatedEmailMail;
use App\Mail\ValidaCorreoProveedorBasicoMail;
use App\Mail\PasswordResetMail;
use App\Models\Proveedor;
use App\Models\EmpresaConstrucc;
use App\Models\Role;
use App\Models\User;
use App\Notifications\Auth\CuentaVerificadaNotification;
use App\Notifications\ProveedorEmpresa\ProveedorAsociadoAEmpresaNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;



class AuthController extends Controller
{


    public function register(AuthRegisterRequest $request)
    {
        $validatedData = $request->validated();
        $token = Str::random(60);
        Cache::put("registro_user_construcc{$token}", $validatedData, 60 * 24 * 365);
        $url = config('services.frontend.url') . "/gen-pass?is_user_construcc=true&token={$token}";
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

        return $this->success($data, 'Usuario pendiente de completar registro en GestionPro');
        // [
        //     'user' => new UserResource($user->load(User::eagerLodable())),
        //     'data' => $data
        // ],
    }

    /**
     * Registra un nuevo proveedor en la base de datos
     * 
     * @return \Illuminate\Http\JsonResponse 
     */
    public function register_proveedor(ProveedorRegisterRequest $request)
    {
        $validatedData = $request->validated();

        // ANTES DE CREAR EL PROVEEDOR, VALIDAMOS QUE NO EXISTA UN USUARIO O PROVEEDOR CON EL MISMO CORREO O TELÉFONO
        // VALIDACIÓN: Verificar si el proveedor ya existe (RFC, email o teléfono)
        $proveedorExistente = Proveedor::where(function ($query) use ($validatedData) {
            // telefono: codigo y telefono que sean iguales
            if (isset($validatedData['telefono']['codigo']) && isset($validatedData['telefono']['telefono'])) {
                $query->where('telefono_codigo_pais', $validatedData['telefono']['codigo'])
                    ->where('telefono', $validatedData['telefono']['telefono']);
            }

            // razon_social
            if (isset($validatedData['razon_social'])) {
                $query->orWhere('razon_social', strtoupper($validatedData['razon_social']));
            }

            // Si se proporcionó email diferente al teléfono
            $telefonoCompleto = ($validatedData['telefono']['codigo'] ?? '') . ($validatedData['telefono']['telefono'] ?? '');
            if (isset($validatedData['email']) && $validatedData['email'] !== $telefonoCompleto) {
                $query->orWhere('email', $validatedData['email']);
            }
        })->first();


        if ($proveedorExistente) {
            // Caso 1: Proveedor ya tiene usuario asignado (tipo_alta = 1)
            if ($proveedorExistente->tipo_alta == 1) {
                return $this->error(
                    'Este teléfono ya está registrado con un usuario activo. Si olvidaste tu contraseña, usa la opción de recuperación.',
                    [
                        'campo_duplicado' => 'telefono',
                        'valor' => $proveedorExistente->telefono,
                    ],
                    409
                );
            }

            // Caso 2: Proveedor registrado desde construcción (tipo_alta = 2)
            if ($proveedorExistente->tipo_alta == 2) {
                // Cargar relaciones necesarias
                $proveedorExistente->load(['cuentasBancarias', 'empresasConstrucc']);

                // Generar token temporal cifrado con los datos del proveedor
                $tokenData = [
                    'proveedor_id' => $proveedorExistente->id,
                    'timestamp' => time(),
                    'telefono' => $proveedorExistente->telefono,
                ];
                $tokenTemporal = base64_encode(json_encode($tokenData));

                return $this->success([
                    'requiere_completar_registro' => true,
                    'proveedor' => [
                        'id' => $proveedorExistente->id,
                        'razon_social' => $proveedorExistente->razon_social,
                        'nombre_comercial' => $proveedorExistente->nombre_comercial,
                        'email' => $proveedorExistente->email,
                        'telefono' => $proveedorExistente->telefono,
                        'cuentas_bancarias' => $proveedorExistente->cuentasBancarias->map(function ($cuenta) {
                            $tipo = $cuenta->clabe ? 'clabe' : ($cuenta->cuenta ? 'cuenta' : 'tarjeta');
                            return [
                                'id' => $cuenta->id,
                                'alias' => $cuenta->alias,
                                'banco_nombre' => $cuenta->banco_nombre,
                                'tipo_cuenta' => $tipo,
                                'cuenta' => $cuenta->cuenta,
                                'clabe' => $cuenta->clabe,
                                'tarjeta' => $cuenta->tarjeta,
                                'preferida' => $cuenta->preferida,
                            ];
                        }),
                        'empresas_construcc' => $proveedorExistente->empresasConstrucc->map(function ($empresa) {
                            return [
                                'id' => $empresa->id,
                                'nombre' => $empresa->nombre,
                            ];
                        }),
                    ],
                    'token_temporal' => $tokenTemporal,
                ], 'Empresa ya registrada. Verifica tus datos y completa el registro.', 200);
            }
        }

        // $proveedorPayload = $validatedData;

        // $proveedorPayload['telefono_codigo_pais'] = $validatedData['telefono']['codigo'] ?? null;
        // $proveedorPayload['telefono'] = $validatedData['telefono']['telefono'] ?? null;
        // $proveedorPayload['nombre_quien_registra'] = $validatedData['nombre_comercial'] ?? null;
        // $proveedorPayload['nombre_comercial'] = $validatedData['razon_social'] ?? null;

        $proveedor = Proveedor::create($validatedData);
        $token = Str::random(60);
        $cacheKey = "registro_proveedor_{$token}";
        Cache::store('file')->forever($cacheKey, $proveedor->id);
        Cache::forever($cacheKey, $proveedor->id);
        Log::info('register_proveedor token cache write', [
            'cache_key' => $cacheKey,
            'proveedor_id' => $proveedor->id,
            'cache_default' => config('cache.default'),
            'file_store_value' => Cache::store('file')->get($cacheKey),
            'default_store_value' => Cache::get($cacheKey),
        ]);

        $url = config('services.frontend.url') . "/gen-pass?token={$token}";
        Mail::to($proveedor->email)->send(new CompletaRegistroProveedorMail($url, $proveedor));

        return $this->success([
            'url' => $url,
            'data' => $proveedor->load(Proveedor::eagerLodable()),
        ], 'Empresa registrada y pendiente de completar registro en GestionPro. Revisa tu correo para continuar.', 200);
    }

    /**
     * Completa el registro de un proveedor
     * 
     * @param ProveedorRegisterCompleteRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register_proveedor_completar(ProveedorRegisterCompleteRequest $request)
    {
        $normalizedToken = preg_replace('/\s+/', '', urldecode(trim((string) $request->input('token'))));
        $cacheKey = "registro_proveedor_{$normalizedToken}";
        $fileStoreValue = Cache::store('file')->get($cacheKey);
        $defaultStoreValue = Cache::get($cacheKey);
        Log::info('register_proveedor_completar token cache read', [
            'raw_token' => (string) $request->input('token'),
            'normalized_token' => $normalizedToken,
            'cache_key' => $cacheKey,
            'cache_default' => config('cache.default'),
            'file_store_value' => $fileStoreValue,
            'default_store_value' => $defaultStoreValue,
        ]);
        $proveedorId = $fileStoreValue ?? $defaultStoreValue;
        if (! $proveedorId) {
            return $this->error('Token inválido o expirado', [], 498);
        }

        $proveedor = Proveedor::findOrFail($proveedorId);

        if (! $proveedor->user) {
            $idRoleProveedor = Role::where('nombre', UserRoleEnumerate::GERENTE->value)->first()->id;
            $user = User::create([
                'name' => $proveedor->nombre_comercial,
                'email' => $proveedor->email,
                'telefono_codigo_pais' => $proveedor->telefono_codigo_pais,
                'telefono' => $proveedor->telefono,
                'password' => Hash::make($request->password),
                'role_id' => $idRoleProveedor,
            ]);

            $user->proveedores()->attach($proveedor->id, [
                'tipo_relacion' => 'PRINCIPAL',
                'activo' => true,
                'fecha_asignacion' => now(),
                'observaciones' => 'Usuario principal de la empresa',
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

        Cache::store('file')->forget($cacheKey);
        Cache::forget($cacheKey);
        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success([
            'user' => new UserAuthenticateResource($user->load(User::eagerLodable())),
            'proveedor' => new ProveedorResource($proveedor->load(Proveedor::eagerLodable())),
            'token' => $token,
        ], 'Registro completado exitosamente en GestionPro', 201);
    }

    public function update_foto_perfil(AuthUpdateFotoPerfilRequest $request)
    {
        $file = $request->file('foto_perfil');

        $nombre = uniqid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('uploads', $nombre, 'public');

        $user = $request->user();
        $user->foto_perfil_url = $path; // guardamos la ruta
        $user->save();

        $proveedor = $user->proveedorPrincipal();
        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success([
            'user' => new UserAuthenticateResource($user->load(User::eagerLodable())),
            'proveedor' => $proveedor ? new ProveedorResource($proveedor->load(Proveedor::eagerLodable())) : null,
            'token' => $token,
        ], 'Registro completado', 201);
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => ['required'], // 'email'],
                'password' => ['required'],
            ]);

            // Buscar usuario por email o teléfono
            $user = User::where(function ($q) use ($request) {
                $q->where('email', $request->email)
                    ->orWhere('telefono', $request->email);
            })
                ->where('status', '!=', EstadoUsuario::BLOQUEADO->value)
                ->where('status', '!=', EstadoUsuario::SUSPENDIDO->value)
                ->first();

            if (! $user || ! Hash::check($request->password, $user->password)) {
                throw new UnauthorizedException('Credenciales incorrectas en GestionPro.');
            }

            $token = $user->createToken('API Token')->plainTextToken;

            $user->load(User::eagerLodable());

            return $this->success([
                'user' => new UserAuthenticateResource($user),
                'token' => $token,
            ], 'Login exitoso en GestionPro.', 201);
        } catch (ValidationException $e) {
            // Error en la validación de los datos de entrada
            return $this->error('Los datos proporcionados no son válidos en GestionPro.', $e->errors(), 422);
        } catch (UnauthorizedException $e) {
            // Credenciales incorrectas o acceso no autorizado
            Log::error('Error al iniciar sesión en GestionPro: ' . $e->getMessage());
            return $this->error($e->getMessage(), [], 401);
        } catch (\Exception $e) {
            // Cualquier otro error inesperado
            Log::error('Error al iniciar sesión en GestionPro: ' . $e->getMessage());
            return $this->error('Ocurrió un error al intentar iniciar sesión en GestionPro.', [], 500);
        }
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
            throw new UnauthorizedException('No autorizado o sesión no válida en GestionPro');
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
            throw new UnauthorizedException('No autorizado o sesión no válida en GestionPro');
        }

        // Solo eliminar el token del dispositivo actual, no todos los tokens
        $request->user()->currentAccessToken()->delete();

        return $this->success(
            [
                'success' => true,
            ],
            'Sesión cerrada correctamente en GestionPro',
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
        ], 'Token renovado exitosamente en GestionPro', 200);
    }

    /**
     * Registrar un nuevo proveedor (versión básica sin usuario asociado)
     * Envía un correo de validación para que el usuario cree su contraseña
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register_proveedor_basico_sp(ProveedorRegistroBasicoRequest $request)
    {
        try {
            $validatedData = $request->validated();

            $proveedorExistente = Proveedor::where(function ($query) use ($validatedData) {

                // TELÉFONO
                if (!empty($validatedData['telefono'])) {
                    $query->orWhere('telefono', $validatedData['telefono']);
                }

                // RFC
                if (!empty($validatedData['rfc'])) {
                    $query->orWhereRaw('UPPER(rfc) = ?', [
                        strtoupper(trim($validatedData['rfc']))
                    ]);
                }

                // RAZÓN SOCIAL
                if (!empty($validatedData['razon_social'])) {
                    $query->orWhereRaw('LOWER(razon_social) = ?', [
                        strtolower(trim($validatedData['razon_social']))
                    ]);
                }

                // EMAIL
                if (!empty($validatedData['email'])) {
                    $query->orWhereRaw('LOWER(email) = ?', [
                        strtolower(trim($validatedData['email']))
                    ]);
                }
            })
                ->where(function ($q) {
                    $q->where('tipo_alta', 1)
                        ->orWhereNull('tipo_alta');
                })
                ->first();

            $this->success($proveedorExistente, 'Proveedor encontrado en GestionPro', 200);

            if ($proveedorExistente) {
                // Caso 1: Proveedor ya tiene usuario asignado (tipo_alta = 1)
                if ($proveedorExistente->tipo_alta == 1) {
                    return $this->error(
                        'Este teléfono ya está registrado con un usuario activo en GestionPro. Si olvidaste tu contraseña, usa la opción de recuperación en GestionPro.',
                        [
                            'campo_duplicado' => 'telefono',
                            'valor' => $validatedData['telefono'],
                        ],
                        409
                    );
                }

                // Caso 2: Proveedor registrado desde construcción (tipo_alta = 2)
                if ($proveedorExistente->tipo_alta == 2) {
                    // Cargar relaciones necesarias
                    $proveedorExistente->load(['cuentasBancarias', 'empresasConstrucc']);

                    // Generar token temporal cifrado con los datos del proveedor
                    $tokenData = [
                        'proveedor_id' => $proveedorExistente->id,
                        'timestamp' => time(),
                        'telefono' => $proveedorExistente->telefono,
                    ];
                    $tokenTemporal = base64_encode(json_encode($tokenData));

                    return $this->success([
                        'requiere_completar_registro' => true,
                        'proveedor' => [
                            'id' => $proveedorExistente->id,
                            'razon_social' => $proveedorExistente->razon_social,
                            'nombre_comercial' => $proveedorExistente->nombre_comercial,
                            'rfc' => $proveedorExistente->rfc,
                            'email' => $proveedorExistente->email,
                            'telefono' => $proveedorExistente->telefono,
                            'cuentas_bancarias' => $proveedorExistente->cuentasBancarias->map(function ($cuenta) {
                                $tipo = $cuenta->clabe ? 'clabe' : ($cuenta->cuenta ? 'cuenta' : 'tarjeta');
                                return [
                                    'id' => $cuenta->id,
                                    'alias' => $cuenta->alias,
                                    'banco_nombre' => $cuenta->banco_nombre,
                                    'tipo_cuenta' => $tipo,
                                    'cuenta' => $cuenta->cuenta,
                                    'clabe' => $cuenta->clabe,
                                    'tarjeta' => $cuenta->tarjeta,
                                    'preferida' => $cuenta->preferida,
                                ];
                            }),
                            'empresas_construcc' => $proveedorExistente->empresasConstrucc->map(function ($empresa) {
                                return [
                                    'id' => $empresa->id,
                                    'nombre' => $empresa->nombre,
                                ];
                            }),
                        ],
                        'token_temporal' => $tokenTemporal,
                    ], 'Empresa ya registrada. Verifica tus datos y completa el registro en GestionPro.', 200);
                }
            }

            // Si no existe, continuar con el registro normal
            // Crear proveedor
            $proveedor = Proveedor::create([
                'nombre_comercial' => $validatedData['nombre_comercial'],
                'razon_social' => $validatedData['nombre_comercial'], // Usar el mismo nombre comercial por defecto
                'telefono' => $validatedData['telefono'],
                'is_proveedor_sp' => true,
                'is_proveedor_catalogo' => false,
                // FIXME: @deprecated
                'cambiar_pass_default' => false,
                'perfil_empresa_completo' => false,
            ]);

            // EL USUARIO ES CUANDO NOS E VALIDA
            if (!$request->solo_validar) {

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
            }

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

            if ($request->solo_validar) {
                return $this->success([
                    'proveedor' => new ProveedorResource($proveedor->load(Proveedor::eagerLodable())),
                ], 'Validación exitosa. El proveedor no fue registrado, solo se verificaron los datos en GestionPro.', 200);
            }

            // Registrar relación con empresa Construcc si se proporcionaron los datos
            if (isset($validatedData['empresa_construcc_id'])) {
                $empresa = $this->getOrCreateEmpresaConstruccFromRequestData($validatedData);

                if ($empresa) {
                    $proveedor->empresasConstrucc()->attach($empresa->id, [
                        'usuario_construcc_id' => $validatedData['usuario_construcc_id'] ?? null,
                        'usuario_construcc_nombre' => $validatedData['usuario_construcc_nombre'] ?? null,
                    ]);
                }
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
            ], 'Registro completado exitosamente en GestionPro', 201);
        } catch (ValidationException $e) {
            // Error en la validación de los datos de entrada
            return $this->error('Los datos proporcionados no son válidos en GestionPro.', $e->errors(), 422);
        } catch (\Exception $e) {
            Log::info('Error en register_proveedor_basico_sp en GestionPro', [
                'error_message' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
            ]);
            // Cualquier otro error inesperado
            return $this->error('Ocurrió un error al intentar completar el registro en GestionPro. Por favor, intenta nuevamente.', [], 500);
        }
    }

    /**
     * Completar registro de proveedor que fue registrado desde construcción (tipo_alta = 2)
     * Cambia el tipo_alta a 1, crea usuario y lo asocia con el proveedor
     *
     * @param CompletarRegistroProveedorRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function completarRegistroProveedor(CompletarRegistroProveedorRequest $request)
    {
        DB::beginTransaction();

        try {
            $validatedData = $request->validated();

            // 1. Buscar el proveedor
            $proveedor = Proveedor::findOrFail($validatedData['proveedor_id']);

            // 2. Validar que sea tipo_alta = 2
            if ($proveedor->tipo_alta !== 2) {
                return $this->error(
                    'Este proveedor no requiere completar registro en GestionPro. Ya tiene un usuario asignado.',
                    null,
                    403
                );
            }

            // 3. Validar token temporal
            try {
                $tokenData = json_decode(base64_decode($validatedData['token_temporal']), true);

                if (
                    !$tokenData ||
                    $tokenData['proveedor_id'] !== $proveedor->id ||
                    $tokenData['telefono'] !== $proveedor->telefono ||
                    (time() - $tokenData['timestamp']) > 3600
                ) { // Token válido por 1 hora

                    return $this->error('Token inválido o expirado en GestionPro. Por favor, intenta registrarte nuevamente.', null, 403);
                }
            } catch (\Exception $e) {
                return $this->error('Token inválido en GestionPro. Por favor, intenta registrarte nuevamente.', null, 403);
            }

            // 4. Actualizar datos del proveedor si se enviaron
            if (isset($validatedData['razon_social'])) {
                $proveedor->razon_social = $validatedData['razon_social'];
            }
            if (isset($validatedData['nombre_comercial'])) {
                $proveedor->nombre_comercial = $validatedData['nombre_comercial'];
            }
            if (isset($validatedData['email'])) {
                $proveedor->email = $validatedData['email'];
            }
            if (isset($validatedData['telefono'])) {
                $proveedor->telefono = $validatedData['telefono'];
            }

            // 5. Cambiar tipo_alta a 1
            $proveedor->tipo_alta = 1;
            $proveedor->save();

            // 6. Obtener rol de gerente
            $idRoleProveedor = Role::where('nombre', UserRoleEnumerate::GERENTE->value)->first()->id;

            // 7. Buscar si ya existe un usuario con ese teléfono, si no, crear uno nuevo
            $user = User::where('email', $proveedor->telefono)->first();

            if (!$user) {
                $user = User::create([
                    'name' => $proveedor->nombre_comercial,
                    'email' => $proveedor->telefono,
                    'password' => Hash::make($validatedData['password']),
                    'role_id' => $idRoleProveedor,
                    'cambiar_pass_default' => false,
                ]);
            } else {
                // Si el usuario ya existe, actualizar la contraseña
                $user->password = Hash::make($validatedData['password']);
                $user->save();
            }

            // 8. Asociar usuario con proveedor si no está asociado
            $estaAsociado = $user->proveedores()->where('proveedor_id', $proveedor->id)->exists();

            if (!$estaAsociado) {
                $user->proveedores()->attach($proveedor->id, [
                    'tipo_relacion' => 'PRINCIPAL',
                    'activo' => true,
                    'fecha_asignacion' => now(),
                    'observaciones' => 'Usuario completó registro desde tipo_alta=2 en GestionPro',
                ]);
            }

            // 9. Crear sucursal matriz si no existe
            if ($proveedor->sucursales()->count() === 0) {
                $proveedor->sucursales()->create([
                    'nombre' => 'Matriz',
                    'direccion' => 'Dirección pendiente',
                    'telefono' => $proveedor->telefono,
                    'email' => $proveedor->email ?? $proveedor->telefono . '@temp.com',
                    'encargado' => $proveedor->nombre_comercial,
                    'activa' => true,
                    'coordenadas_lat' => null,
                    'coordenadas_lng' => null,
                    'estatus' => 'activo',
                ]);
            }

            // 10. Registrar en logs
            Log::info('Proveedor completó registro desde tipo_alta=2 a tipo_alta=1 en GestionPro', [
                'proveedor_id' => $proveedor->id,
                'user_id' => $user->id,
                'telefono' => $proveedor->telefono,
            ]);

            DB::commit();

            // 11. Crear token de autenticación
            $token = $user->createToken('auth_token')->plainTextToken;
            $user->load(User::eagerLodable());

            // 12. Retornar respuesta exitosa
            return $this->success([
                'user' => new UserAuthenticateResource($user),
                'proveedor' => new ProveedorResource($proveedor->load(Proveedor::eagerLodable())),
                'token' => $token,
            ], 'Registro completado exitosamente en GestionPro. Ya puedes iniciar sesión.', 201);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al completar registro de proveedor en GestionPro', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'Ocurrió un error al completar el registro en GestionPro. Por favor, intenta nuevamente.',
                null,
                500
            );
        }
    }

    /**
     * Asociar proveedor existente a una empresa constructora
     * Se usa cuando el teléfono ya está registrado y se quiere vincular a una nueva empresa
     *
     * @param ProveedorAsociarEmpresaRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function asociar_proveedor_existente(ProveedorAsociarEmpresaRequest $request)
    {
        /**
         * PARA ESTA FUNCION SE DEBE:
         * 1. VALIDAR LA EXISTENCIA DEL REGISTRO DE EMPRESA USUARIO EN EL MODELO EmpresaConstrucc
         * 2. VALIDAR EL PROVEEDOR CON EL TELEFONO
         * 3. REGISTRAR LA RELACION
         * 4. MANDAR NOTIFICACION AL PROVEEDOR (DATABASE, PUSH, BROADCAST, MAIL ..)
         */
        try {
            // Log::info('Iniciando proceso de asociar proveedor existente.', [
            //     'payload_request' => $request->all()
            // ]);

            $validatedData = $request->validated();
            // Log::info('Datos validados correctamente.', [
            //     'validated' => $validatedData
            // ]);

            // 1. Obtener o crear la empresa
            // Log::info('Buscando / creando empresa constructora...', [
            //     'empresa_construcc_id' => $validatedData['empresa_construcc_id'] ?? null
            // ]);

            $empresa = $this->getOrCreateEmpresaConstruccFromRequestData($validatedData);

            // Log::info('Resultado búsqueda/creación empresa:', [
            //     'empresa_id' => $empresa->id ?? null,
            //     'empresa_nombre' => $empresa->nombre ?? null,
            //     'empresa_rfc' => $empresa->rfc ?? null
            // ]);

            // 2. Buscar proveedor por teléfono
            // Log::info('Buscando proveedor por teléfono...', [
            //     'telefono' => $validatedData['telefono']
            // ]);

            $proveedor = Proveedor::where('telefono', $validatedData['telefono'])->first();

            if (!$proveedor) {
                Log::warning('Proveedor no encontrado por teléfono.', [
                    'telefono' => $validatedData['telefono']
                ]);
                return $this->error('No se encontró un proveedor con este teléfono en GestionPro.', [], 404);
            }

            // Log::info('Proveedor encontrado.', [
            //     'proveedor_id' => $proveedor->id,
            //     'proveedor_nombre' => $proveedor->nombre_comercial ?? $proveedor->razon_social
            // ]);

            // 3. Validar si ya existe la asociación
            // Log::info('Verificando si ya existe una asociación...', [
            //     'empresa_id' => $empresa->id,
            //     'proveedor_id' => $proveedor->id,
            //     'usuario_construcc_id' => $validatedData['usuario_construcc_id']
            // ]);

            $existeAsociacion = DB::table('empresa_construcc_proveedor')
                ->where('empresa_construcc_id', $empresa->id)
                ->where('proveedor_id', $proveedor->id)
                ->where('usuario_construcc_id', $validatedData['usuario_construcc_id'])
                ->exists();

            if ($existeAsociacion) {
                Log::warning('Asociación ya existente.', [
                    'empresa_id' => $empresa->id,
                    'proveedor_id' => $proveedor->id
                ]);

                return $this->error('Este usuario ya tiene registrada una invitación para este proveedor en GestionPro.', null, 400);
            }

            // 4. Crear asociación
            // Log::info('Creando asociación proveedor-empresa en GestionPro...');

            $proveedor->empresasConstrucc()->attach($empresa->id, [
                'usuario_construcc_id' => $validatedData['usuario_construcc_id'],
                'usuario_construcc_nombre' => $validatedData['usuario_construcc_nombre'],
            ]);

            Log::info('Asociación creada exitosamente en GestionPro.', [
                'empresa_id'    => $empresa->id,
                'proveedor_id'  => $proveedor->id,
                'usuario_construcc_id' => $validatedData['usuario_construcc_id']
            ]);

            // 5. Enviar notificación
            try {
                Log::info('Intentando enviar notificación al proveedor en GestionPro...');

                $usuario = $proveedor->usuarioPrincipal();

                if ($usuario) {
                    $usuario->notify(new ProveedorAsociadoAEmpresaNotification(
                        $proveedor->id,
                        $proveedor->nombre_comercial ?? $proveedor->razon_social,
                        $empresa->id,
                        $empresa->nombre,
                        $empresa->rfc ?? '',
                        $validatedData['usuario_construcc_id'],
                        $validatedData['usuario_construcc_nombre']
                    ));

                    // Log::info('Notificación enviada al proveedor.', [
                    //     'usuario_principal' => $usuario->id,
                    // ]);
                } else {
                    Log::warning('Proveedor no tiene usuario principal. No se envió notificación.');
                }
            } catch (\Exception $e) {
                Log::error('Error al enviar notificación de asociación proveedor-empresa', [
                    'proveedor_id' => $proveedor->id,
                    'empresa_id' => $empresa->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Log::info('Proceso de asociación completado exitosamente.');

            return $this->success([
                'proveedor' => new ProveedorResource($proveedor->load(Proveedor::eagerLodable())),
                'asociado' => true,
                'empresa_id' => $empresa->id,
                'empresa_nombre' => $empresa->nombre,
            ], 'Empresa de GestionPro asociada exitosamente a la empresa en GestionPro.', 200);
        } catch (\Exception $e) {
            Log::error('Error general al asociar proveedor existente en GestionPro', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error('No fue posible asociar la Empresa de GestionPro con la empresa en GestionPro. Por favor, intenta nuevamente.', [], 500);
        }
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
            return $this->error('Token inválido o expirado en GestionPro', [], 498);
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
        ], 'Registro completado exitosamente en GestionPro.', 201);
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
                'current_password' => ['La contraseña actual no es correcta en GestionPro.'],
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
        ], 'Contraseña actualizada correctamente en GestionPro.', 200);
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

        $user = User::where(function ($q) use ($request) {
            $q->where('email', $request->email)
                ->orWhere('telefono', $request->email);
        })->first();

        if (! $user) {
            return $this->error(
                'No encontramos ninguna cuenta con ese correo electrónico o número de teléfono en GestionPro. Comprueba que escribiste bien los datos o regístrate si aún no tienes cuenta.',
                [],
                404
            );
        }

        if (in_array($user->status, [
            EstadoUsuario::BLOQUEADO->value,
            EstadoUsuario::SUSPENDIDO->value,
        ], true)) {
            return $this->error(
                'No podemos enviar el enlace de recuperación porque la cuenta asociada está bloqueada o suspendida en GestionPro. Para resolverlo, contacta a soporte.',
                [],
                403
            );
        }

        $userEmail = $user->email;
        if ($userEmail === null || filter_var($userEmail, FILTER_VALIDATE_EMAIL) === false) {
            return $this->error(
                'Tu cuenta no tiene un correo electrónico válido donde enviar el enlace de recuperación en GestionPro. Completa o actualiza tu correo en tu perfil o pide ayuda a soporte.',
                [],
                422
            );
        }

        $token = Str::random(60);

        Cache::put("password_reset_{$token}", [
            'user_id' => $user->id,
            'email' => $user->email,
            'created_at' => now(),
        ], 60 * 60);

        $url = config('services.frontend.url') . "/auth/reset-password?token={$token}";

        try {
            Mail::to($userEmail)->send(new PasswordResetMail($url, $user->name));
        } catch (\Throwable $e) {
            Log::error('Fallo al enviar correo de recuperación de contraseña', [
                'user_id' => $user->id,
                'exception' => $e->getMessage(),
            ]);

            return $this->error(
                'Encontramos tu cuenta, pero no pudimos enviar el correo de recuperación en este momento (fallo temporal del servicio de correo). Vuelve a intentarlo en unos minutos o contacta a soporte si el problema continúa.',
                [],
                503
            );
        }

        return $this->success(
            ['email' => $email],
            'Te enviamos un correo con instrucciones para restablecer tu contraseña en GestionPro. Revisa tu bandeja de entrada, la carpeta de spam y el apartado de promociones.',
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
                'El enlace de recuperación ha expirado o es inválido en GestionPro. Por favor, solicita uno nuevo.',
                [],
                400
            );
        }

        // Verificar que no haya pasado más de 1 hora
        $createdAt = \Carbon\Carbon::parse($data['created_at']);
        if ($createdAt->diffInMinutes(now()) > 60) {
            Cache::forget("password_reset_{$request->token}");
            return $this->error(
                'El enlace de recuperación ha expirado en GestionPro. Por favor, solicita uno nuevo.',
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
     * Obtener o crear empresa constructora a partir de datos de request de enlace
     *
     * @param array $validatedData
     * @return EmpresaConstrucc|null
     */
    private function getOrCreateEmpresaConstruccFromRequestData(array $validatedData): ?EmpresaConstrucc
    {
        if (empty($validatedData['empresa_construcc_id'])) {
            return null;
        }

        $empresaId = $validatedData['empresa_construcc_id'];

        $empresa = EmpresaConstrucc::find($empresaId);

        if (! $empresa) {
            $empresa = new EmpresaConstrucc();
            $empresa->id = $empresaId;
            $empresa->nombre = $validatedData['empresa_construcc_nombre'] ?? "Empresa {$empresaId}";
            $empresa->razon_social = $validatedData['empresa_construcc_nombre'] ?? null;
            // RFC es opcional en esta integración; se deja nulo si no viene
            $empresa->rfc = $validatedData['rfc'] ?? null;
            $empresa->activo = true;
            $empresa->save();
        }

        return $empresa;
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
        // También verificar en proveedores.email para evitar conflictos aunque el email no se use como username
        $existeEnProveedores = Proveedor::where('email', $request->email)->exists();

        return $this->success([
            'existe' => $existe || $existeEnProveedores,
            'email' => $request->email,
        ], ($existe || $existeEnProveedores) ? 'El correo ya está registrado.' : 'El correo está disponible.', 200);
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
        // normalizar para mismo formato de comparación (trim y mayúsculas)\
        $existe = Proveedor::whereRaw('UPPER(TRIM(razon_social)) = ?', [trim(strtoupper($request->razon_social))])
            ->where(function ($q) {
                $q->where('tipo_alta', 1)
                    ->orWhereNull('tipo_alta');
            })
            ->exists();

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
        // FIXME: Realizar revision de la validacion para el telefono del proveedor, ¿1aqui no se debe validar el proveedor???
        $existeEnProveedores = Proveedor::where('telefono', $telefono)
            ->where('tipo_alta', '!=', 2)
            ->exists();

        // Verificar si el teléfono existe como email en users (se usa como username)
        $existeEnUsers = User::where('telefono', $telefono)->exists();

        $existe = $existeEnProveedores || $existeEnUsers;

        return $this->success([
            'existe' => $existe,
            'telefono' => $telefono,
        ], $existe ? 'El teléfono ya está registrado.' : 'El teléfono está disponible.', 200);
    }

    /**
     * Update user data: name, email, telefono
     * Esta actualizacion no debe afectar el perfil de la empresa.
     */
    public function updateUserData(AuthUpdateUserDataRequest $request)
    {
        $validatedData = $request->validated();
        $user = $request->user();
        $emailBeforeUpdate = $user->email;

        $user->update($validatedData);

        $verificationEmailSent = false;

        $emailChanged = array_key_exists('email', $validatedData) && $validatedData['email'] !== $emailBeforeUpdate;
        $requiresEmailVerification = $emailChanged || is_null($user->email_verified_at);

        if ($emailChanged) {
            $user->email_verified_at = null;
            $user->save();
        }

        if ($requiresEmailVerification && !empty($user->email)) {
            $verificationToken = Str::random(64);
            $cacheKey = "email_verification_user_update_{$verificationToken}";
            $userTokenKey = "email_verification_user_update_latest_token_{$user->id}";

            $oldToken = Cache::get($userTokenKey);
            if (!empty($oldToken)) {
                Cache::forget("email_verification_user_update_{$oldToken}");
            }

            Cache::put($cacheKey, [
                'user_id' => $user->id,
                'email' => $user->email,
                'created_at' => now()->toIso8601String(),
            ], 60 * 60 * 24 * 360); // 360 horas = 15 días

            Cache::put($userTokenKey, $verificationToken, 60 * 60 * 24 * 360); // 360 horas = 15 días

            $verificationUrl = url("/api/auth/verificar-email-token?token={$verificationToken}");
            Mail::to($user->email)->send(new VerifyUpdatedEmailMail($verificationUrl, $user->name));
            $verificationEmailSent = true;
        }

        $user->load(User::eagerLodable());
        $token = $user->createToken('API Token')->plainTextToken;

        return $this->success(
            [
                'user' => new UserAuthenticateResource($user),
                'token' => $token,
                'proveedor' => $user->proveedorPrincipal() ? new ProveedorResource($user->proveedorPrincipal()) : null,
                'email_verification_required' => $requiresEmailVerification,
                'email_verification_sent' => $verificationEmailSent,
                'email_verification_message' => $verificationEmailSent
                    ? 'Te enviamos un correo para validar tu email. Revisa tu bandeja de entrada.'
                    : null,
            ],
            $verificationEmailSent
                ? 'Datos de usuario actualizados correctamente. Te enviamos un correo para validar tu email.'
                : 'Datos de usuario actualizados correctamente.',
            200
        );
    }

    /**
     * Verifica el correo actualizado desde el enlace enviado por email.
     */
    public function verifyUpdatedEmail(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
        ]);

        $cacheKey = "email_verification_user_update_{$request->token}";
        $data = Cache::get($cacheKey);

        if (!$data) {
            return $this->error('El enlace de validación es inválido o expiró.', [], 400);
        }

        $user = User::find($data['user_id']);
        if (!$user) {
            Cache::forget($cacheKey);
            return $this->error('Usuario no encontrado para validar el correo.', [], 404);
        }

        if ($user->email !== $data['email']) {
            Cache::forget($cacheKey);
            return $this->error('El correo ya fue modificado. Solicita una nueva validación.', [], 400);
        }

        if (is_null($user->email_verified_at)) {
            $user->email_verified_at = now();
            $user->save();
        }

        Cache::forget($cacheKey);
        Cache::forget("email_verification_user_update_latest_token_{$user->id}");

        $user->notify(new CuentaVerificadaNotification(
            email: $user->email,
            userId: $user->id,
            verifiedAtIso: optional($user->email_verified_at)->toIso8601String()
        ));

        $frontendHomeUrl = rtrim((string) config('services.frontend.url', 'http://localhost:8100'), '/') . '/';

        return redirect()->away($frontendHomeUrl);
    }

    /**
     * Verificar si un teléfono ya está registrado
     * Excluyendo el usuario actual
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verificarTelefonoExistenteExcluyendoUsuario(Request $request)
    {
        $request->validate([
            'telefono' => ['required', 'string'],
        ]);


        $telefono = $request->input('telefono');
        $existe = User::where('telefono', $telefono)->where('id', '!=', $request->user()->id)->exists();
        return $this->success([
            'existe' => $existe,
            'telefono' => $telefono,
        ], $existe ? 'El teléfono ya está registrado.' : 'El teléfono está disponible.', 200);
    }

    /**
     * Verificar si un email ya está registrado
     * Excluyendo el usuario actual
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verificarEmailExistenteExcluyendoUsuario(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string'],
        ]);


        $email = $request->input('email');
        $existe = User::where('email', $email)->where('id', '!=', $request->user()->id)->exists();
        return $this->success([
            'existe' => $existe,
            'email' => $email,
        ], $existe ? 'El email ya está registrado.' : 'El email está disponible.', 200);
    }
}
