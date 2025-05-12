<?php

namespace App\Http\Controllers;

use App\Enums\UserRoleEnumerate;
use App\Http\Controllers\Controller;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use App\Exceptions\Api\Crud\ResourceNotFoundException;
use App\Http\Requests\ActualizarProveedorRequest;
use App\Http\Requests\RegisterProveedorCompletarRequest;
use App\Http\Requests\RegistroProveedorRequest;
use App\Mail\CompletaRegistroProveedorMail;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;


/**
 * @OA\Tag(
 *     name="Proveedores",
 *     description="CRUD de proveedores"
 * )
 */
class ProveedorController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/proveedores",
     *     summary="Listar proveedores",
     *     operationId="getProveedores",
     *     tags={"Proveedores"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="nombre_comercial", in="query", description="Filtrar por nombre comercial del proveedor.", @OA\Schema(type="string")),
     *     @OA\Parameter(name="razon_social", in="query", description="Filtrar por razón social", @OA\Schema(type="string")),
     *     @OA\Parameter(name="rfc", in="query", description="Filtrar por el RFC.", @OA\Schema(type="string")),
     *     @OA\Parameter(name="direccion_fiscal", in="query", description="Filtrar por direccion fiscal registrada.", @OA\Schema(type="string")),
     *     @OA\Parameter(name="estado", in="query", description="Filtrar por estado.", @OA\Schema(type="string")),
     *     @OA\Parameter(name="municipio", in="query", description="Filtrar por municipio.", @OA\Schema(type="string")),
     *     @OA\Parameter(name="fecha_registro", in="query", description="Filtrar por fecha del registro del proveedor.", @OA\Schema(type="string")),
     *     @OA\Parameter(name="estatus", in="query", description="Filtrar por el estatus del proveedor.", @OA\Schema(type="string")),
     *     @OA\Parameter(name="notas", in="query", description="Filtrar por notas agregadas al proveedor.", @OA\Schema(type="string")),
     *     @OA\Parameter(name="email", in="query", description="Filtrar por email.", @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de proveedores",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Proveedor"))
     *     )
     * )
     */
    public function index(Request $request)
    {
        $fields = Proveedor::getFilters();
        $filters = $request->only($fields);
        $proveedores = Proveedor::with(Proveedor::eagerLodable())->filter($filters)->paginate(10);
        return $this->paginated($proveedores);
    }

    /**
     * @OA\Post(
     *     path="/api/proveedores",
     *     summary="Registrar un nuevo proveedor",
     *     description="Crea un nuevo proveedor. Requiere validación por correo para crear el usuario.",
     *     operationId="registrarProveedor",
     *     tags={"Proveedores"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/RegistroProveedorRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Proveedor registrado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Proveedor registrado exitosamente"),
     *             @OA\Property(property="proveedor", ref="#/components/schemas/Proveedor"),
     *             @OA\Property(property="sucursal", ref="#/components/schemas/Sucursal")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos de entrada inválidos",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 additionalProperties=@OA\Property(type="array", @OA\Items(type="string"))
     *             )
     *         )
     *     )
     * )
     */
    public function register_proveedor(RegistroProveedorRequest $request)
    {
        $proveedor = Proveedor::create($request->validated());

        // Generamos un token único
        $token = Str::random(60);


        // Guardamos el token en cache para validarlo después
        Cache::put("registro_proveedor_{$token}", $proveedor->id, 3600);
        // Expira en 1 hora


        // Enviar correo con el link para completar el registro
        $url = config('services.frontend.url') . "/auth/completar-registro-proveedor?token={$token}";
        Mail::to($proveedor->email)->send(new CompletaRegistroProveedorMail($url));

        return $this->success($proveedor->load(Proveedor::eagerLodable()), 'Proveedor registrado. Revisa tu correo para continuar.', 200);

        // return $this->success($proveedor->load(Proveedor::eagerLodable()), 201);
    }

    /**
     * @OA\Post(
     *     path="/api/register_proveedor_completar",
     *     summary="Completar el registro de un proveedor",
     *     operationId="CompletarRegistroProveedor",
     *     tags={"Proveedores"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"token", "password", "password_confirmation"},
     *             @OA\Property(property="token", type="string", description="Token proporcionado para completar el registro", example="abc123def456"),
     *             @OA\Property(property="password", type="string", format="password", description="Nueva contraseña (mínimo 8 caracteres)", example="MiClaveSegura123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", description="Confirmación de la nueva contraseña", example="MiClaveSegura123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Proveedor creado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="proveedor", ref="#/components/schemas/Proveedor"),
     *             @OA\Property(property="token", type="string", description="Token de sesión para el usuario")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Token inválido o expirado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Token inválido o expirado")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Entrada inválida",
     *         @OA\JsonContent(ref="#/components/schemas/InvalidInputException")
     *     )
     * )
     */
    public function register_proveedor_completar(RegisterProveedorCompletarRequest $request)
    {

        // Validamos los datos
        $validated_data = $request->validated();


        // Verificamos si el token existe en la caché (token temporal que generamos en el registro)
        $proveedorId = Cache::get("registro_proveedor_{$request->token}");

        if (!$proveedorId) {

            // return response()->json(['message' => 'Token inválido o expirado'], 400);
            return $this->error(
                'Token inválido o expirado',
                400
            );
        }


        // Buscamos al proveedor por el ID guardado en la caché
        $proveedor = Proveedor::findOrFail($proveedorId);


        // Si el proveedor no tiene un usuario relacionado, lo creamos
        if (!$proveedor->user) {
            $user = User::create([
                'name' => $proveedor->nombre_comercial,
                // Usamos el nombre del proveedor (o puedes cambiarlo)
                'email' => $proveedor->email,
                // Asignamos el correo del proveedor
                'password' => Hash::make($request->password),
                // Guardamos la contraseña encriptada
                'role' => UserRoleEnumerate::PROVEEDOR->value,
                // Guardamos la contraseña encriptada
            ]);


            // Asocia el usuario con el proveedor
            $proveedor->user()->associate($user);
            $proveedor->save();
            // Guardamos la relación

        } else {

            // Si ya existe un usuario, actualizamos solo la contraseña
            $user = $proveedor->user;
            $user->password = Hash::make($request->password);
            $user->save();
        }


        // Eliminamos el token temporal de la caché
        Cache::forget("registro_proveedor_{$request->token}");


        // Generamos el token para la sesión del usuario
        $token = $user->createToken('API Token')->plainTextToken;
        return $this->success(
            [
                'user' => $user,
                'proveedor' => $proveedor,
                'token' => $token
            ],
            'Contraseña establecida correctamente',
            201
        );
    }


    /**
     * @OA\Get(
     *     path="/api/proveedores/{id}",
     *     summary="Obtener un proveedor específico",
     *     operationId="getProveedor",
     *     tags={"Proveedores"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del proveedor",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Proveedor encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/Proveedor")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Proveedor no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ResourceNotFoundException")
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
     *     summary="Actualizar un proveedor existente",
     *     operationId="updateProveedor",
     *     tags={"Proveedores"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del proveedor",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos actualizados del proveedor",
     *         @OA\JsonContent(ref="#/components/schemas/ActualizarProveedorRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Proveedor actualizado exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/Proveedor")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Proveedor no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ResourceNotFoundException")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Errores de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 additionalProperties=@OA\Property(type="array", @OA\Items(type="string"))
     *             )
     *         )
     *     )
     * )
     */
    public function update(ActualizarProveedorRequest $request, $id)
    {
        $proveedor = Proveedor::find($id);

        if (!$proveedor) {
            throw new ResourceNotFoundException("Proveedor no encontrado.");
        }

        $proveedor->update($request->validated());

        return $this->success($proveedor->load(Proveedor::eagerLodable()), 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/proveedores/{id}",
     *     summary="Eliminar proveedor",
     *     operationId="deleteProveedor",
     *     tags={"Proveedores"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del proveedor",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Proveedor eliminado exitosamente"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Proveedor no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ResourceNotFoundException")
     *     )
     * )
     */
    public function destroy($id)
    {
        $proveedor = Proveedor::find($id);
        if (!$proveedor) {
            throw new ResourceNotFoundException("Proveedor no encontrado.");
        }

        // FIXME: Manejo de los estatus de los recursos:
        //      - uso de tablas en la BD
        //      - Enumerate 
        //      - a la voluntad de dios y la buena memoria (Estado actual)
        $proveedor->update(
            [
                ['estatus' => 'baja']
            ]
        );

        // $proveedor->delete();

        return $this->success(null, 204);
    }
}
