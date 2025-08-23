<?php

namespace App\Http\Controllers;

use App\Enums\UserRoleEnumerate;
use App\Exceptions\Api\Auth\UnauthorizedException;

use App\Models\Role;
use App\Models\User;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\ProveedorResource;
use App\Mail\CompletaRegistroUsuarioMail;
use App\Mail\CompletaRegistroProveedorMail;
use App\Http\Requests\Auth\AuthRegisterRequest;
use App\Http\Resources\UserAuthenticateResource;
use App\Http\Requests\Auth\AuthRegisterCompleteRequest;
use App\Http\Requests\Auth\AuthUpdateFotoPerfilRequest;
use App\Http\Requests\Proveedor\ProveedorRegisterRequest;
use App\Http\Requests\Proveedor\ProveedorRegisterCompleteRequest;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{

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
            'role_id' => Role::where('nombre', UserRoleEnumerate::USUARIO->value)->first()->id
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

        $url = config('services.frontend.url') . "/auth/registro/completar?token={$token}";
        Mail::to($proveedor->email)->send(new CompletaRegistroProveedorMail($url));

        return $this->success([
            'url' => $url,
            'data' => $proveedor->load(Proveedor::eagerLodable())
        ], 'Proveedor registrado. Revisa tu correo para continuar.', 200);
    }

    public function register_proveedor_completar(ProveedorRegisterCompleteRequest $request)
    {
        $proveedorId = Cache::get("registro_proveedor_{$request->token}");
        if (!$proveedorId) {
            return $this->error('Token inválido o expirado', [], 498);
        }

        $proveedor = Proveedor::findOrFail($proveedorId);

        if (!$proveedor->user) {
            $idRoleProveedor = Role::where('nombre', UserRoleEnumerate::GERENTE->value)->first()->id;
            $user = User::create([
                'name' => $proveedor->nombre_comercial,
                'email' => $proveedor->email,
                'password' => Hash::make($request->password),
                'role_id' => $idRoleProveedor,
            ]);

            // $user->proveedores()->attach($proveedor->id, ['is_main' => true]);
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

    public function me(Request $request)
    {
        $user = $request->user();
        $user->load(User::eagerLodable());
        $proveedor = $user->proveedorPrincipal();

        return $this->success([
            'user' => new UserAuthenticateResource($user),
            'token' => null,
            'proveedor' => $proveedor
        ], 'Login exitoso.', 200);
    }

    public function refresh(Request $request)
    {
        if (!$request->user()) {
            throw new UnauthorizedException("No autorizado o sesión no válida");
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
            'proveedor' => $proveedor
        ], 'Token renovado exitosamente', 200);
    }

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
