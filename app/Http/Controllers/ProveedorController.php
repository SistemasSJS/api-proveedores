<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Role;
use App\Models\User;
use App\Models\Proveedor;

use App\Http\Resources\UserAuthenticateResource;

use App\Http\Requests\ProveedorUpdateRequest;
use App\Http\Requests\ProveedorRegisterCompleteRequest;
use App\Http\Requests\ProveedorRegisterRequest;
use App\Http\Requests\ProveedorUpdateLogoRequest;


use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

use App\Mail\CompletaRegistroProveedorMail;

use App\Exceptions\Api\Crud\ResourceNotFoundException;
use App\Http\Resources\ProveedorResource;

use function Laravel\Prompts\error;

/**
 * @OA\Tag(
 *     name="Proveedores",
 *     description="Endpoints para la gestión de proveedores"
 * )
 */
class ProveedorController extends Controller
{

    /**
     * @OA\Put(
     *     path="/api/proveedores/logo",
     *     tags={"Proveedores"},
     *     summary="Actualizar logo del proveedor",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(mediaType="multipart/form-data",
     *             @OA\Schema(ref="#/components/schemas/ProveedorUpdateLogoRequest")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Logo actualizado"
     *     )
     * )
     */
    public function updateLogo(ProveedorUpdateLogoRequest $request)
    {
        $user = $request->user();
        $proveedor = $user->mainProveedor()->first();
        if (!$proveedor) {
            throw new ResourceNotFoundException("Proveedor no encontrado.");
        }

        if ($proveedor->logo !== null) {
            $rutaAnterior = str_replace(asset('storage') . '/', '', $proveedor->logo);
            Storage::disk('public')->delete($rutaAnterior);
        }

        $file = $request->file('logo');
        $filename = 'logo_' . $proveedor->id . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs("uploads", $filename, 'public');
        $url = asset("storage/{$path}");
        $proveedor->update(['logo' => $url]);

        return $this->success($proveedor->fresh(Proveedor::eagerLodable()));
    }

    /**
     * @OA\Get(
     *     path="/api/proveedores/user/{id}",
     *     tags={"Proveedores"},
     *     summary="Obtener proveedor por ID de usuario",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Proveedor encontrado"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Proveedor no encontrado"
     *     )
     * )
     */
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


    /**
     * @OA\Get(
     *     path="/api/proveedores",
     *     tags={"Proveedores"},
     *     summary="Listar todos los proveedores",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de proveedores"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $fields = Proveedor::getFilters();
        $filters = $request->only($fields);

        $sortBy = $request->input('sort_by', 'nombre_comercial');
        $order = $request->input('order', 'asc');

        // $proveedores = Proveedor::with(Proveedor::eagerLodable())
        //     ->filter($filters)
        //     ->orderBy($sortBy, $order)
        //     ->paginate(10);
        // return $this->paginated($proveedores);

        $originalPaginator = Proveedor::with(Proveedor::eagerLodable())
            ->filter($filters)
            ->orderBy($sortBy, $order)
            ->paginate(10);

        $proveedores = ProveedorResource::collection($originalPaginator)->resolve();
        return $this->paginated($originalPaginator->setCollection(collect($proveedores)));
    }

    /**
     * @OA\Get(
     *     path="/api/proveedores/{id}",
     *     tags={"Proveedores"},
     *     summary="Obtener un proveedor por ID",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Proveedor encontrado"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Proveedor no encontrado"
     *     )
     * )
     */
    public function show($id)
    {
        $proveedor = Proveedor::with(Proveedor::eagerLodable())->find($id);
        if (!$proveedor) {
            throw new ResourceNotFoundException("Proveedor no encontrado.");
        }

        return $this->success($proveedor);
    }

    /**
     * @OA\Put(
     *     path="/api/proveedores/{id}",
     *     tags={"Proveedores"},
     *     summary="Actualizar proveedor existente",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ProveedorUpdateRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Proveedor actualizado"
     *     )
     * )
     */
    public function update(ProveedorUpdateRequest $request, $id)
    {
        $proveedor = Proveedor::find($id);
        if (!$proveedor) {
            throw new ResourceNotFoundException("Proveedor no encontrado.");
        }
        $proveedor->update($request->all());
        $proveedor->load(Proveedor::eagerLodable());
        return $this->success($proveedor, 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/proveedores/{id}",
     *     tags={"Proveedores"},
     *     summary="Eliminar un proveedor",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Proveedor eliminado"
     *     )
     * )
     */
    public function destroy($id)
    {
        $proveedor = Proveedor::find($id);
        if (!$proveedor) {
            throw new ResourceNotFoundException("Proveedor no encontrado.");
        }
        $proveedor->update([['estatus' => 'baja']]);
        return $this->success(null, 204);
    }

    /**
     * @OA\Post(
     *     path="/api/proveedores",
     *     tags={"Proveedores"},
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
        $proveedor = Proveedor::create($request->validated());
        $token = Str::random(60);

        Cache::put("registro_proveedor_{$token}", $proveedor->id, 60 * 60 * 24 * 7 * 360); // 1 año

        $url = config('services.frontend.url') . "/auth/completar-registro-proveedor?token={$token}";
        Mail::to($proveedor->email)->send(new CompletaRegistroProveedorMail($url));

        return $this->success($proveedor->load(Proveedor::eagerLodable()), 'Proveedor registrado. Revisa tu correo para continuar.', 200);
    }

    /**
     * @OA\Post(
     *     path="/api/proveedores/completar-registro",
     *     tags={"Proveedores"},
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


    // public function test(Request $request)
    // {
    //     $user = User::findOrFail(1);
    //     $proveedor = Proveedor::findOrFail(1);
    //     $token = $user->createToken('auth_token')->plainTextToken;

    //     return $this->success([
    //         'user' => new UserAuthenticateResource($user->load(User::eagerLodable())),
    //         'proveedor' => $proveedor->load(Proveedor::eagerLodable()),
    //         'token' => $token,
    //     ], 'Registro completado', 201);
    // }
}
