<?php

namespace App\Http\Controllers;

use App\Enums\EstadoCuentaBancaria;
use App\Exceptions\Api\Crud\ResourceNotFoundException;
use App\Http\Requests\Proveedor\ProveedorUpdateConstanciaFiscalRequest;
use App\Http\Requests\Proveedor\ProveedorUpdateLogoRequest;
use App\Http\Requests\Proveedor\ProveedorUpdateRequest;
use App\Http\Resources\Admin\AdminProveedorAcordeonResource;
use App\Http\Resources\ProveedorResource;
use App\Http\Resources\ProveedorValidacionPerfilCompletoResource;
use App\Http\Resources\UserResource;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ProveedorController extends Controller
{
    /**
     * Actualiza el logo del proveedor principal del usuario autenticado.
     *
     * - Elimina el logo anterior del proveedor (si existe).
     * - Elimina la foto de perfil anterior del usuario (si existe).
     * - Guarda y asigna el nuevo logo.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws ResourceNotFoundException Si el proveedor no existe.
     */
    public function updateLogo(ProveedorUpdateLogoRequest $request, Proveedor $proveedor)
    {
        $user = $request->user();
        $proveedor = $user->proveedorPrincipal();
        if (! $proveedor) {
            throw new ResourceNotFoundException('Proveedor no encontrado.');
        }

        if ($proveedor->logo !== null) {
            $rutaAnterior = str_replace(asset('storage').'/', '', $proveedor->logo);
            Storage::disk('public')->delete($rutaAnterior);
        }

        if ($user->foto_perfil_url !== null) {
            $rutaAnterior = str_replace(asset('storage').'/', '', $user->foto_perfil_url);
            Storage::disk('public')->delete($rutaAnterior);
        }

        $file = $request->file('logo');
        $filename = 'logo_'.$proveedor->id.'_'.time().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs('uploads', $filename, 'public');

        $proveedor->update(['logo' => $path]);

        return $this->success([
            'proveedor' => new ProveedorResource(($proveedor->fresh(Proveedor::eagerLodable()))),
            'user' => new UserResource(($user->fresh(User::eagerLodable()))),
        ]);
    }

    /**
     * Obtiene el proveedor principal asociado a un usuario por ID.
     *
     * @param  int  $id  ID del usuario
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws ResourceNotFoundException Si no se encuentra el usuario o proveedor.
     */
    public function getProveedorByUserId(Request $request, $id)
    {
        $user = User::find($id);
        if (! $user) {
            throw new ResourceNotFoundException('Usuario no encontrado.');
        }
        $proveedor = $user->proveedorPrincipal();
        if (! $proveedor) {
            throw new ResourceNotFoundException('Proveedor no encontrado.');
        }

        return $this->success(new ProveedorResource($proveedor->load(Proveedor::eagerLodable())));
    }

    /**
     * Lista los proveedores con filtros, ordenamiento y paginación.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $filters = $request->only(Proveedor::getFilters());
        $sortBy = $request->input('sort_by', 'nombre_comercial');
        $order = $request->input('order', 'asc');
        $perPage = $request->input('per_page', 10);

        $originalPaginator = Proveedor::with(Proveedor::eagerLodable())
            ->filter($filters)
            ->orderBy($sortBy, $order)
            ->paginate($perPage);

        $data = ProveedorResource::collection($originalPaginator)->resolve();

        return $this->paginated($originalPaginator->setCollection(collect($data)));
    }

    /**
     * Muestra los datos de un proveedor específico.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, Proveedor $proveedor)
    {
        return $this->success(new ProveedorResource($proveedor));
    }

    /**
     * Actualiza la información de un proveedor.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(ProveedorUpdateRequest $request, Proveedor $proveedor)
    {
        $validated = $request->validated();
        $proveedor->update($validated);
        $proveedor = $proveedor->fresh(Proveedor::eagerLodable());

        return $this->success(new ProveedorResource($proveedor), 'Proveedor actualizado con éxito.', 200);
    }

    /**
     * Marca un proveedor como baja (eliminación lógica).
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws ResourceNotFoundException Si no se encuentra el proveedor.
     */
    public function destroy($id)
    {
        $proveedor = Proveedor::find($id);
        if (! $proveedor) {
            throw new ResourceNotFoundException('Proveedor no encontrado.');
        }
        $proveedor->update([['estatus' => 'baja']]);

        return $this->success(null, 204);
    }

    /**
     * Obtiene los proveedores con sus categorías raíz, subcategorías y conteo de productos.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function proveedoresConCategoriasConSubcatCountProductos(Request $request)
    {
        $proveedores = Proveedor::with([
            'categorias' => function ($query) {
                $query->whereNull('parent_id') // solo categorías raíz
                    ->with([
                        'children' => function ($subquery) {
                            $subquery->withCount('productos');
                        },
                    ])
                    ->withCount('productos');
            },
        ])
            ->withCount('productos') // total de productos por proveedor
            ->get();

        return $this->success(
            AdminProveedorAcordeonResource::collection($proveedores),
            'Listado de proveedores con sus categorías, subcategorías y contador de productos.'
        );
    }

    /**
     * Subir o actualizar la constancia fiscal del proveedor.
     */
    public function updateConstanciaFiscal(
        ProveedorUpdateConstanciaFiscalRequest $request,
        Proveedor $proveedor
    ) {
        $user = $request->user();

        // Verificar acceso
        if ($user->proveedorPrincipal()?->id !== $proveedor->id) {
            return response()->json([
                'message' => 'No tienes permisos para subir este documento.',
            ], Response::HTTP_FORBIDDEN);
        }

        $file = $request->file('constancia_fiscal');

        // Borrar constancia anterior si existe
        if ($proveedor->constancia_fiscal && Storage::disk('private')->exists($proveedor->constancia_fiscal)) {
            Storage::disk('private')->delete($proveedor->constancia_fiscal);
        }

        // Guardar nueva constancia en private
        $filename = 'constancia_'.$proveedor->id.'_'.time().'.pdf';
        $path = $file->storeAs('constancias', $filename, 'private');

        $proveedor->update(['constancia_fiscal' => $path]);

        return $this->success([
            'proveedor' => new ProveedorResource($proveedor->fresh()),
            'message' => 'Constancia fiscal actualizada con éxito.',
        ], 200);
    }

    /**
     * Vista previa o descarga de la constancia fiscal.
     */
    public function previewConstanciaFiscal(Request $request, Proveedor $proveedor)
    {
        $user = $request->user();

        // Validar acceso
        if ($user->proveedorPrincipal()?->id !== $proveedor->id) {
            return response()->json([
                'message' => 'No tienes permisos para acceder a este documento.',
            ], Response::HTTP_FORBIDDEN);
        }

        if (! $proveedor->constancia_fiscal || ! Storage::disk('private')->exists($proveedor->constancia_fiscal)) {
            return response()->json([
                'message' => 'La constancia fiscal no está disponible.',
            ], Response::HTTP_NOT_FOUND);
        }

        $path = Storage::disk('private')->path($proveedor->constancia_fiscal);

        // Mostrar inline en navegador (preview)
        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="constancia_fiscal.pdf"',
        ]);
    }

    /**
     * Valida si el proveedor puede generar una Solicitud de Pago (SP).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function puedeGenerarSP(Request $request, Proveedor $proveedor)
    {
        // Verificar primero si es proveedor de SP
        if (! $proveedor->is_proveedor_sp) {
            $responseData = [
                'puede_generar_sp' => false,
                'detalle' => [
                    'perfil_empresa_completo' => false,
                    'tiene_cuenta_bancaria' => false,
                    'tiene_constancia_fiscal' => false,
                    'tiene_logo' => false,
                    'tiene_informacion_general_y_datos_fiscales' => false,
                    'datos_faltantes' => ['El proveedor no está habilitado para generar Solicitudes de Pago'],
                ],
            ];

            return $this->success(new ProveedorValidacionPerfilCompletoResource($responseData));
        }

        // Si ya tiene el perfil marcado como completo, validar rápidamente
        if ($proveedor->perfil_empresa_completo) {
            // Cargar solo las relaciones necesarias para verificar cuenta bancaria
            // $proveedor->load(['cuentasBancarias']);
            // $proveedor->cuentasBancarias;
            $tieneCuentaBancaria = $proveedor->cuentasBancarias->where('estatus', EstadoCuentaBancaria::ACTIVA)->count() > 0;

            // Validaciones mínimas para confirmar que sigue siendo válido
            $tieneLogo = ! empty($proveedor->logo);
            $tieneConstanciaFiscal = ! empty($proveedor->constancia_fiscal);

            // Si las validaciones básicas pasan, no necesitamos hacer validaciones detalladas
            if ($tieneCuentaBancaria && $tieneLogo && $tieneConstanciaFiscal) {
                $responseData = [
                    'puede_generar_sp' => true,
                    'detalle' => [
                        'perfil_empresa_completo' => true,
                        'tiene_cuenta_bancaria' => true,
                        'tiene_constancia_fiscal' => true,
                        'tiene_logo' => true,
                        'tiene_informacion_general_y_datos_fiscales' => true,
                        'datos_faltantes' => [],
                    ],
                ];

                return $this->success(new ProveedorValidacionPerfilCompletoResource($responseData));
            }
        }

        // Si llegamos aquí, necesitamos hacer validaciones completas
        // Cargar relaciones necesarias
        $proveedor->load(['cuentasBancarias']);

        // Validar información general y datos fiscales
        $tieneInformacionGeneral = $this->validarInformacionGeneral($proveedor);
        $tieneDatosFiscales = $this->validarDatosFiscales($proveedor);
        $tieneInformacionGeneralYDatosFiscales = $tieneInformacionGeneral && $tieneDatosFiscales;

        // Validar datos de contacto
        // $tieneDatosContacto = $this->validarDatosContacto($proveedor);

        // Validar logo
        $tieneLogo = ! empty($proveedor->logo);

        // Validar cuenta bancaria
        $tieneCuentaBancaria = $proveedor->cuentasBancarias->where('estatus', EstadoCuentaBancaria::ACTIVA)->count() > 0;

        // Validar constancia fiscal
        $tieneConstanciaFiscal = ! empty($proveedor->constancia_fiscal);

        // Calcular si el perfil está completo
        $perfilEmpresaCompleto = $tieneInformacionGeneralYDatosFiscales &&
                                // $tieneDatosContacto &&
                                $tieneLogo &&
                                $tieneCuentaBancaria &&
                                $tieneConstanciaFiscal;

        // Calcular datos faltantes
        $datosFaltantes = [];
        if (! $tieneInformacionGeneralYDatosFiscales) {
            if (! $tieneInformacionGeneral) {
                $datosFaltantes[] = 'Información general de la empresa';
            }
            if (! $tieneDatosFiscales) {
                $datosFaltantes[] = 'Datos fiscales';
            }
        }
        // if (!$tieneDatosContacto) {
        //     $datosFaltantes[] = 'Datos de contacto';
        // }
        if (! $tieneLogo) {
            $datosFaltantes[] = 'Logo de la empresa';
        }
        if (! $tieneCuentaBancaria) {
            $datosFaltantes[] = 'Al menos una cuenta bancaria activa';
        }
        if (! $tieneConstanciaFiscal) {
            $datosFaltantes[] = 'Constancia de situación fiscal';
        }

        // Actualizar el campo perfil_empresa_completo en el modelo si ha cambiado
        if ($proveedor->perfil_empresa_completo !== $perfilEmpresaCompleto) {
            $proveedor->update(['perfil_empresa_completo' => $perfilEmpresaCompleto]);
        }

        // Puede generar SP si es proveedor de SP y el perfil está completo
        $puedeGenerarSP = $perfilEmpresaCompleto;

        $responseData = [
            'puede_generar_sp' => $puedeGenerarSP,
            'detalle' => [
                'perfil_empresa_completo' => $perfilEmpresaCompleto,
                'tiene_cuenta_bancaria' => $tieneCuentaBancaria,
                'tiene_constancia_fiscal' => $tieneConstanciaFiscal,
                'tiene_logo' => $tieneLogo,
                'tiene_informacion_general_y_datos_fiscales' => $tieneInformacionGeneralYDatosFiscales,
                'datos_faltantes' => $datosFaltantes,
            ],
        ];

        return $this->success(new ProveedorValidacionPerfilCompletoResource($responseData));
    }

    /**
     * Valida la información general del proveedor.
     */
    private function validarInformacionGeneral(Proveedor $proveedor): bool
    {
        $camposRequeridos = [
            'nombre_propietario',
            'nombre_comercial',
            'descripcion_giro_empresa',
            'pagina_web',
            'email',
            'telefono',
            // 'nombre_de_quien_registra',
            // 'tipos_empresa_id',
            // 'direccion_empresa',
            // 'estado',
            // 'municipio',
            // 'codigo_postal'
        ];

        foreach ($camposRequeridos as $campo) {
            if (empty($proveedor->$campo)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Valida los datos fiscales del proveedor.
     */
    private function validarDatosFiscales(Proveedor $proveedor): bool
    {
        $camposFiscales = [
            'razon_social',
            'rfc',
            'regimen_fiscal_clave',
            'regimen_fiscal_nombre',
            'calle',
            'numero_exterior',
            'colonia',
            'ciudad',
            'codigo_postal',
            'pais',
        ];

        foreach ($camposFiscales as $campo) {
            if (empty($proveedor->$campo)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Valida los datos de contacto del proveedor.
     */
    private function validarDatosContacto(Proveedor $proveedor): bool
    {
        $camposContacto = [
            'contacto_nombre',
            'contacto_cargo',
            'contacto_telefono',
            'contacto_correo',
        ];

        foreach ($camposContacto as $campo) {
            if (empty($proveedor->$campo)) {
                return false;
            }
        }

        return true;
    }
}
