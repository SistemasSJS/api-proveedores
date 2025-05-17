<?php

namespace App\Http\Controllers;

use App\Enums\UserRoleEnumerate;
use App\Http\Controllers\Controller;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use App\Exceptions\Api\Crud\ResourceNotFoundException;
use App\Http\Requests\ActualizarLogoProveedor;
use App\Http\Requests\ActualizarProveedorRequest;
use App\Http\Requests\RegisterProveedorCompletarRequest;
use App\Http\Requests\RegistroProveedorRequest;
use App\Mail\CompletaRegistroProveedorMail;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;



class ProveedorController extends Controller
{

    public function updateLogoProveedor(ActualizarLogoProveedor $request, $id)
    {
        Log::channel('requests')->info('FILES:', $request->allFiles());
        Log::channel('requests')->info('ALL:', $request->all());
        $user = User::find($id);
        if (!$user) {
            throw new ResourceNotFoundException("Usuario no encontrado.");
        }

        $proveedor = $user->mainProveedor()->first();
        if (!$proveedor) {
            throw new ResourceNotFoundException("Proveedor no encontrado.");
        }

        $file = $request->file('logo');
        $filename = 'logo_' . $proveedor->id . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('uploads', $filename, 'public');
        $url = asset("storage/{$path}");

        $proveedor->update(['logo' => $url]);

        return $this->success($proveedor->fresh(Proveedor::eagerLodable()));
    }

    public function getProveedorByUserId(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            throw new ResourceNotFoundException("Usuario no encontrado.");
        }
        $proveedor = $user->mainProveedor()->first();
        if (!$proveedor) {
            throw new ResourceNotFoundException("Proveedor no encontrado.");
        }
        return $this->success($proveedor->load(Proveedor::eagerLodable()));
    }

    public function index(Request $request)
    {
        $fields = Proveedor::getFilters();
        $filters = $request->only($fields);

        $sortBy = $request->input('sort_by', 'nombre_comercial');
        $order = $request->input('order', 'asc');

        $proveedores = Proveedor::with(Proveedor::eagerLodable())
            ->filter($filters)
            ->orderBy($sortBy, $order)
            ->paginate(10);
        return $this->paginated($proveedores);
    }

    public function show($id)
    {
        $proveedor = Proveedor::with(Proveedor::eagerLodable())->find($id);
        if (!$proveedor) {
            throw new ResourceNotFoundException("Proveedor no encontrado.");
        }

        return $this->success($proveedor);
    }

    public function update(ActualizarProveedorRequest $request, $id)
    {
        $proveedor = Proveedor::find($id);
        if (!$proveedor) {
            throw new ResourceNotFoundException("Proveedor no encontrado.");
        }
        $proveedor->update($request->all());
        $proveedor->load(Proveedor::eagerLodable());
        return $this->success($proveedor, 200);
    }

    public function destroy($id)
    {
        $proveedor = Proveedor::find($id);
        if (!$proveedor) {
            throw new ResourceNotFoundException("Proveedor no encontrado.");
        }
        $proveedor->update([['estatus' => 'baja']]);
        return $this->success(null, 204);
    }

    public function register_proveedor(RegistroProveedorRequest $request)
    {
        $proveedor = Proveedor::create($request->validated());
        $token = Str::random(60);

        Cache::put("registro_proveedor_{$token}", $proveedor->id, 3600);

        $url = config('services.frontend.url') . "/auth/completar-registro-proveedor?token={$token}";
        Mail::to($proveedor->email)->send(new CompletaRegistroProveedorMail($url));

        return $this->success($proveedor->load(Proveedor::eagerLodable()), 'Proveedor registrado. Revisa tu correo para continuar.', 200);
    }

    public function register_proveedor_completar(RegisterProveedorCompletarRequest $request)
    {
        $validated_data = $request->validated();

        $proveedorId = Cache::get("registro_proveedor_{$request->token}");
        if (!$proveedorId) {
            return $this->error('Token inválido o expirado', 400);
        }

        $proveedor = Proveedor::findOrFail($proveedorId);

        if (!$proveedor->user) {
            $idRoleProveedor = Role::where('nombre', UserRoleEnumerate::PROVEEDOR->value)->first()->id;
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
            'proveedor' => $proveedor->load(Proveedor::eagerLodable()),
            'token' => $token,
        ], 'Registro completado', 201);
    }
}
