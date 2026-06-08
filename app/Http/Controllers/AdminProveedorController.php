<?php



namespace App\Http\Controllers;



use App\Exceptions\Api\Crud\ResourceNotFoundException;

use App\Http\Requests\Admin\AdminProveedorStoreRequest;

use App\Http\Requests\Proveedor\ProveedorUpdateRequest;

use App\Http\Resources\Admin\AdminProveedorAcordeonResource;

use App\Http\Resources\Presupuesto\PresupuestoResource;

use App\Http\Resources\ProductoResource;

use App\Http\Resources\ProveedorResource;

use App\Http\Resources\SolicitudPago\SolicitudPagoResource;

use App\Models\Presupuesto;

use App\Models\Proveedor;

use App\Models\SolicitudPago;

use Carbon\Carbon;

use Illuminate\Http\JsonResponse;

use Illuminate\Http\Request;



class AdminProveedorController extends Controller

{

    /**

     * Lista los proveedores con filtros, ordenamiento y paginación.

     */

    public function index(Request $request)

    {

        $filters = $request->only(Proveedor::getFilters());

        $sortBy = $request->input('sort_by', 'nombre_comercial');

        $order = $request->input('order', 'asc');

        $perPage = min(max(1, (int) $request->input('per_page', 10)), 100);



        $originalPaginator = Proveedor::queryParaAdmin()

            ->with(Proveedor::eagerLodable())

            ->filter($filters)

            ->orderBy($sortBy, $order)

            ->paginate($perPage);



        $data = ProveedorResource::collection($originalPaginator)->resolve();

        return $this->paginated($originalPaginator->setCollection(collect($data)));

    }

    /**
     * Conteos para segmentos del listado admin (Todos / Operativos / Suspendidos / Bloqueados).
     * Respeta filtros del listado (búsqueda, tipo de alta, etc.) excepto estatus y grupo_operativos.
     */
    public function conteosListado(Request $request): JsonResponse
    {
        $filters = $request->only(Proveedor::getFilters());
        unset($filters['estatus'], $filters['grupo_operativos']);

        $base = Proveedor::queryParaAdmin()->filter($filters);

        $todos = (clone $base)->count();
        $bloqueados = (clone $base)->where('estatus', 'bloqueado')->count();
        $suspendidos = (clone $base)->where('estatus', 'suspendido')->count();
        $operativos = max(0, $todos - $bloqueados - $suspendidos);

        return $this->success([
            'todos' => $todos,
            'operativos' => $operativos,
            'suspendidos' => $suspendidos,
            'bloqueados' => $bloqueados,
        ], 'Conteos de proveedores para listado administrativo.');
    }

    /**

     * Crea un nuevo proveedor.

     *

     * @param  ProveedorStoreRequest  $request

     * @return \Illuminate\Http\JsonResponse

     */

    public function store(AdminProveedorStoreRequest $request)

    {

        $validated = $request->validated();



        // Crear proveedor

        $proveedor = Proveedor::create($validated);



        return $this->success(

            new ProveedorResource($proveedor->fresh(Proveedor::eagerLodable())),

            'Proveedor creado con éxito.',

            201

        );

    }



    /**

     * Muestra los datos de un proveedor específico.

     */

    public function show(Request $request, Proveedor $proveedor)

    {

        $proveedor->load(['tipos_empresa', 'cuentasBancarias', 'empresaConstruccAlta', 'empresasConstrucc']);

        return $this->success(new ProveedorResource($proveedor));

    }



    /**

     * Resumen de relaciones, actividad e historial para gestión administrativa.

     */

    public function resumen(Request $request, Proveedor $proveedor): JsonResponse

    {

        $proveedor->load(['tipos_empresa']);



        $proveedor->loadCount([

            'productos',

            'sucursales',

            'users',

            'presupuestos',

            'solicitudesPago',

            'cuentasBancarias',

            'empresasConstrucc',

            'pagosSPP as pagos_spp_count',

        ]);



        $usuariosActivos = $proveedor->users()

            ->wherePivot('activo', true)

            ->count();



        $presupuestosPorEstado = Presupuesto::query()

            ->where('proveedor_id', $proveedor->id)

            ->selectRaw('estado, COUNT(*) as total')

            ->groupBy('estado')

            ->pluck('total', 'estado');



        $sppPorEstado = $proveedor->solicitudesPago()

            ->selectRaw('estado_solicitud, COUNT(*) as total')

            ->groupBy('estado_solicitud')

            ->pluck('total', 'estado_solicitud');



        $usuarios = $proveedor->users()

            ->with(['role:id,nombre'])

            ->orderByRaw("FIELD(user_proveedor.tipo_relacion, 'PRINCIPAL', 'SECUNDARIO')")

            ->limit(50)

            ->get()

            ->map(function ($user) {

                return [

                    'id' => $user->id,

                    'name' => $user->name,

                    'email' => $user->email,

                    'telefono' => $user->telefono,

                    'rol' => $user->role?->nombre,

                    'tipo_relacion' => $user->pivot->tipo_relacion,

                    'activo' => (bool) $user->pivot->activo,

                    'fecha_asignacion' => $user->pivot->fecha_asignacion,

                    'fecha_desasignacion' => $user->pivot->fecha_desasignacion,

                    'observaciones' => $user->pivot->observaciones,

                ];

            })

            ->values();



        $ultimosPresupuestos = PresupuestoResource::collection(
            Presupuesto::query()
                ->where('proveedor_id', $proveedor->id)
                ->with(Presupuesto::eagerLodable())
                ->orderByDesc('updated_at')
                ->limit(10)
                ->get()
        )->resolve();

        $ultimasSpp = SolicitudPagoResource::collection(
            $proveedor->solicitudesPago()
                ->with(SolicitudPago::eagerLodable())
                ->orderByDesc('updated_at')
                ->limit(10)
                ->get()
        )->resolve();



        $empresasConstrucc = $proveedor->empresasConstrucc()

            ->select('empresa_construcc.id', 'empresa_construcc.nombre', 'empresa_construcc.razon_social', 'empresa_construcc.rfc')

            ->limit(20)

            ->get();



        $cuentasBancarias = $proveedor->cuentasBancarias()

            ->orderByDesc('updated_at')

            ->limit(10)

            ->get(['id', 'alias', 'banco_nombre', 'estatus', 'created_at']);



        $montos = [

            'presupuestos_total_monto' => (float) Presupuesto::query()

                ->where('proveedor_id', $proveedor->id)

                ->sum('total'),

            'spp_monto_total' => (float) $proveedor->solicitudesPago()->sum('monto_total'),

            'spp_monto_abonado' => (float) $proveedor->solicitudesPago()->sum('monto_abonado'),

        ];



        return $this->success([

            'proveedor_id' => $proveedor->id,

            'conteos' => [

                'productos' => $proveedor->productos_count,

                'sucursales' => $proveedor->sucursales_count,

                'usuarios' => $proveedor->users_count,

                'usuarios_activos' => $usuariosActivos,

                'presupuestos' => $proveedor->presupuestos_count,

                'solicitudes_pago' => $proveedor->solicitudes_pago_count,

                'cuentas_bancarias' => $proveedor->cuentas_bancarias_count,

                'empresas_construcc' => $proveedor->empresas_construcc_count,

                'pagos_spp' => (int) ($proveedor->pagos_spp_count ?? 0),

            ],

            'montos' => $montos,

            'presupuestos_por_estado' => $presupuestosPorEstado,

            'spp_por_estado' => $sppPorEstado,

            'usuarios' => $usuarios,

            'ultimos_presupuestos' => $ultimosPresupuestos,

            'ultimas_spp' => $ultimasSpp,

            'empresas_construcc' => $empresasConstrucc,

            'cuentas_bancarias' => $cuentasBancarias,

            'historial' => $this->buildHistorialProveedor($proveedor, $usuarios),

        ], 'Resumen de relaciones del proveedor.');

    }



    /**

     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $usuarios

     * @return array<int, array<string, mixed>>

     */

    private function buildHistorialProveedor(Proveedor $proveedor, $usuarios): array

    {

        $eventos = [];



        if ($proveedor->created_at) {

            $eventos[] = [

                'tipo' => 'creacion',

                'titulo' => 'Alta en GestionPlus',

                'detalle' => 'Registro inicial de la empresa proveedora.',

                'fecha' => $proveedor->created_at->toDateTimeString(),

            ];

        }



        if ($proveedor->fecha_registro) {

            $eventos[] = [

                'tipo' => 'fecha_registro',

                'titulo' => 'Fecha de registro',

                'detalle' => 'Fecha de registro comercial declarada.',

                'fecha' => $proveedor->fecha_registro->toDateTimeString(),

            ];

        }



        if ($proveedor->registro_completado_at) {

            $eventos[] = [

                'tipo' => 'registro_completado',

                'titulo' => 'Registro completado',

                'detalle' => 'El proveedor finalizó el flujo de registro en la plataforma.',

                'fecha' => $proveedor->registro_completado_at->toDateTimeString(),

            ];

        }



        if ($proveedor->updated_at && $proveedor->created_at && ! $proveedor->updated_at->equalTo($proveedor->created_at)) {

            $eventos[] = [

                'tipo' => 'actualizacion',

                'titulo' => 'Última actualización de datos',

                'detalle' => 'Cambio en la ficha de la empresa (estatus, contacto, fiscal, etc.).',

                'fecha' => $proveedor->updated_at->toDateTimeString(),

            ];

        }



        foreach ($usuarios as $usuario) {

            if (! empty($usuario['fecha_asignacion'])) {

                $eventos[] = [

                    'tipo' => 'usuario_vinculado',

                    'titulo' => 'Usuario vinculado',

                    'detalle' => sprintf(

                        '%s (%s) — relación %s%s',

                        $usuario['name'] ?? 'Usuario',

                        $usuario['email'] ?? '',

                        $usuario['tipo_relacion'] ?? 'N/D',

                        ($usuario['activo'] ?? false) ? '' : ' (inactivo)'

                    ),

                    'fecha' => Carbon::parse($usuario['fecha_asignacion'])->toDateTimeString(),

                ];

            }

            if (! empty($usuario['fecha_desasignacion'])) {

                $eventos[] = [

                    'tipo' => 'usuario_desvinculado',

                    'titulo' => 'Usuario desvinculado',

                    'detalle' => ($usuario['name'] ?? 'Usuario').' dejó de estar asignado a esta empresa.',

                    'fecha' => Carbon::parse($usuario['fecha_desasignacion'])->toDateTimeString(),

                ];

            }

        }



        usort($eventos, fn ($a, $b) => strcmp($b['fecha'], $a['fecha']));



        return array_slice($eventos, 0, 40);

    }



    /**

     * Actualiza la información de un proveedor.

     */

    public function update(ProveedorUpdateRequest $request, Proveedor $proveedor)

    {

        $validated = $request->validated();

        $proveedor->update($validated);

        $proveedor = $proveedor->fresh(Proveedor::eagerLodable());

        $proveedor->load(['tipos_empresa', 'cuentasBancarias', 'empresaConstruccAlta', 'empresasConstrucc']);

        return $this->success(new ProveedorResource($proveedor), 'Proveedor actualizado con éxito.', 200);

    }



    /**

     * Marca un proveedor como baja (eliminación lógica).

     */

    public function destroy(Proveedor $proveedor)

    {

        $proveedor->delete();



        return $this->success(null, 204);

    }



    /**

     * Obtiene los proveedores con sus categorías raíz, subcategorías y conteo de productos.

     */

    public function proveedoresConCategoriasConSubcatCountProductos(Request $request)

    {

        $proveedores = Proveedor::queryParaAdmin()->with([

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

     * Lista productos de un proveedor con paginación.

     */

    public function productos(Request $request, Proveedor $proveedor)

    {

        $perPage = min(max(1, (int) $request->input('per_page', 10)), 100);

        $sortBy = $request->input('sort_by', 'nombre');

        $order = $request->input('order', 'asc');



        $paginator = $proveedor->productos()

            ->with(['marca', 'categoria', 'subcategoria', 'unidad_medida'])

            ->orderBy($sortBy, $order)

            ->paginate($perPage);



        $data = ProductoResource::collection($paginator)->resolve();



        return $this->paginated(

            $paginator->setCollection(collect($data)),

            'Listado de productos del proveedor.'

        );

    }

}


