<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EstadoSP;
use App\Http\Controllers\Controller;
use App\Models\Proveedor;
use App\Models\User;
use App\Models\UserProveedor;
use App\Services\ProveedorHomologacionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Controlador para homologación de proveedores duplicados
 * Gestiona la reasignación de usuarios entre proveedores con la misma razón social
 */
class ProveedorHomologacionController extends Controller
{
    use ApiResponse;

    protected ProveedorHomologacionService $homologacionService;

    public function __construct(ProveedorHomologacionService $homologacionService)
    {
        $this->homologacionService = $homologacionService;
    }

    /**
     * Listar proveedores con información para homologación
     * 
     * GET /api/admin/homologacion/proveedores
     * 
     * Query params:
     * - search: buscar por razón social o nombre comercial
     * - sort_by: id|razon_social|usuarios_count (default: razon_social)
     * - sort_order: asc|desc (default: asc)
     * - per_page: cantidad de registros por página (default: 50)
     */
    public function listarProveedores(Request $request): JsonResponse
    {
        try {
            $search = $request->query('search');
            $sortBy = $request->query('sort_by', 'razon_social');
            $sortOrder = $request->query('sort_order', 'asc');
            $perPage = (int) $request->query('per_page', 50);

            $query = Proveedor::withCount('users')
                ->select('id', 'razon_social', 'nombre_comercial', 'rfc', 'created_at');

            // Filtro de búsqueda
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('razon_social', 'like', "%{$search}%")
                        ->orWhere('nombre_comercial', 'like', "%{$search}%")
                        ->orWhere('rfc', 'like', "%{$search}%");
                });
            }

            // Ordenamiento
            $allowedSortFields = ['id', 'razon_social', 'nombre_comercial', 'created_at', 'users_count'];
            if (in_array($sortBy, $allowedSortFields)) {
                $query->orderBy($sortBy, $sortOrder);
            }

            $proveedores = $query->paginate($perPage);

            return $this->paginated($proveedores, 'Proveedores obtenidos exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al listar proveedores para homologación', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'Error al obtener la lista de proveedores',
                ['exception' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Reporte de proveedores duplicados por razon social
     *
     * GET /api/admin/homologacion/reporte-proveedores-duplicados
     */
    public function reporteProveedoresDuplicados(Request $request): JsonResponse
    {
        try {
            $duplicateRazonSociales = Proveedor::query()
                ->selectRaw('LOWER(TRIM(razon_social)) as razon_social_normalizada')
                ->whereNotNull('razon_social')
                ->whereRaw("TRIM(razon_social) <> ''")
                ->groupBy('razon_social_normalizada')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('razon_social_normalizada');

            if ($duplicateRazonSociales->isEmpty()) {
                return $this->success([
                    'total_proveedores_duplicados' => 0,
                    'total_grupos_duplicados' => 0,
                    'proveedores' => [],
                ], 'No se encontraron proveedores duplicados');
            }

            $proveedores = Proveedor::query()
                ->whereIn(DB::raw('LOWER(TRIM(razon_social))'), $duplicateRazonSociales)
                ->with([
                    'solicitudesPago:id,proveedor_id,estado_solicitud,created_at,updated_at',
                    'userProveedores' => function ($query) {
                        $query->where('activo', true)
                            ->orderByRaw("FIELD(tipo_relacion, 'PRINCIPAL', 'SECUNDARIO')")
                            ->with([
                                'user:id,name,email,telefono,role_id',
                                'user.role:id,nombre',
                            ]);
                    },
                ])
                ->orderBy('razon_social')
                ->orderBy('nombre_comercial')
                ->get();

            $proveedoresPorGrupo = $proveedores->groupBy(function ($proveedor) {
                return mb_strtolower(trim((string) $proveedor->razon_social));
            });

            $data = $proveedores->map(function ($proveedor) use ($proveedoresPorGrupo) {
                $spps = $proveedor->solicitudesPago;
                $estadosBase = collect(EstadoSP::values())
                    ->push('procesando')
                    ->unique()
                    ->values()
                    ->all();

                $conteoPorEstado = $spps
                    ->groupBy(fn($spp) => $spp->estado_solicitud ?? 'sin_estado')
                    ->map(fn($items) => $items->count())
                    ->all();

                foreach ($estadosBase as $estado) {
                    $conteoPorEstado[$estado] = $conteoPorEstado[$estado] ?? 0;
                }

                $idsSppPorEstado = $spps
                    ->groupBy(fn($spp) => $spp->estado_solicitud ?? 'sin_estado')
                    ->map(fn($items) => $items->pluck('id')->values()->all())
                    ->all();

                foreach ($estadosBase as $estado) {
                    $idsSppPorEstado[$estado] = $idsSppPorEstado[$estado] ?? [];
                }

                $usuarioRelacion = $proveedor->userProveedores->first();
                $usuario = $usuarioRelacion?->user;
                $razonSocialNormalizada = mb_strtolower(trim((string) $proveedor->razon_social));

                return [
                    'proveedor_id' => $proveedor->id,
                    'razon_social' => $proveedor->razon_social,
                    'nombre_comercial' => $proveedor->nombre_comercial,
                    'duplicados_en_razon_social' => $proveedoresPorGrupo[$razonSocialNormalizada]->count(),
                    'total_spp' => $spps->count(),
                    'conteo_spp_por_estado' => $conteoPorEstado,
                    'ultima_spp_creada_en' => optional($spps->sortByDesc('created_at')->first())->created_at?->toDateTimeString(),
                    'ultima_actualizacion_spp_en' => optional($spps->sortByDesc('updated_at')->first())->updated_at?->toDateTimeString(),
                    'ids_spp' => $spps->pluck('id')->values()->all(),
                    'ids_spp_por_estado' => $idsSppPorEstado,
                    'usuario' => $usuario ? [
                        'id' => $usuario->id,
                        'name' => $usuario->name,
                        'email' => $usuario->email,
                        'telefono' => $usuario->telefono,
                        'role_id' => $usuario->role_id,
                        'role' => $usuario->role?->nombre,
                        'tipo_relacion' => $usuarioRelacion?->tipo_relacion,
                        'activo' => $usuarioRelacion?->activo,
                        'fecha_asignacion' => $usuarioRelacion?->fecha_asignacion,
                    ] : null,
                ];
            })->values();

            return $this->success([
                'total_proveedores_duplicados' => $data->count(),
                'total_grupos_duplicados' => $proveedoresPorGrupo->count(),
                'proveedores' => $data,
            ], 'Reporte de proveedores duplicados generado exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al generar reporte de proveedores duplicados', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'Error al generar el reporte de proveedores duplicados',
                ['exception' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Obtener detalle de un proveedor con sus usuarios
     * 
     * GET /api/admin/homologacion/proveedores/{id}
     */
    public function obtenerDetalleProveedor(int $proveedorId): JsonResponse
    {
        try {
            $proveedor = Proveedor::with([
                'users' => function ($query) {
                    $query->select('users.id', 'users.name', 'users.email', 'users.telefono', 'users.role_id')
                        ->with('role:id,nombre');
                },
            ])->findOrFail($proveedorId);

            // Formatear usuarios con información del pivot
            $usuarios = $proveedor->users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role ? $user->role->nombre : null,
                    'role_id' => $user->role_id,
                    'is_main' => $user->pivot->tipo_relacion === 'PRINCIPAL',
                    'tipo_relacion' => $user->pivot->tipo_relacion,
                    'activo' => $user->pivot->activo,
                    'fecha_asignacion' => $user->pivot->fecha_asignacion,
                ];
            });

            return $this->success([
                'proveedor' => [
                    'id' => $proveedor->id,
                    'razon_social' => $proveedor->razon_social,
                    'nombre_comercial' => $proveedor->nombre_comercial,
                    'rfc' => $proveedor->rfc,
                    'created_at' => $proveedor->created_at,
                ],
                'usuarios' => $usuarios,
            ], 'Detalle del proveedor obtenido exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al obtener detalle del proveedor', [
                'proveedor_id' => $proveedorId,
                'error' => $e->getMessage(),
            ]);

            return $this->error(
                'Error al obtener el detalle del proveedor',
                ['exception' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Obtener usuarios de múltiples proveedores para homologación
     * 
     * POST /api/admin/homologacion/usuarios-para-reasignar
     * 
     * Body:
     * {
     *   "proveedor_ids": [1, 2, 3]
     * }
     */
    public function obtenerUsuariosParaReasignar(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'proveedor_ids' => 'required|array|min:2',
            'proveedor_ids.*' => 'required|integer|exists:proveedores,id',
        ]);

        if ($validator->fails()) {
            return $this->error(
                'Datos inválidos',
                $validator->errors(),
                422
            );
        }

        try {
            $proveedorIds = $request->input('proveedor_ids');

            // Obtener proveedores con sus usuarios
            $proveedores = Proveedor::whereIn('id', $proveedorIds)
                ->with([
                    'users' => function ($query) {
                        $query->select('users.id', 'users.name', 'users.email', 'users.role_id')
                            ->with('role:id,nombre');
                    },
                ])
                ->orderBy('created_at', 'asc')
                ->get();

            if ($proveedores->count() < 2) {
                return $this->error(
                    'Se requieren al menos 2 proveedores para homologar',
                    null,
                    422
                );
            }

            // Proveedor destino (el más antiguo)
            $proveedorDestino = $proveedores->first();

            // Usuarios agrupados por proveedor
            $usuariosPorProveedor = $proveedores->map(function ($proveedor) use ($proveedorDestino) {
                return [
                    'proveedor_id' => $proveedor->id,
                    'razon_social' => $proveedor->razon_social,
                    'nombre_comercial' => $proveedor->nombre_comercial,
                    'es_destino' => $proveedor->id === $proveedorDestino->id,
                    'created_at' => $proveedor->created_at,
                    'usuarios' => $proveedor->users->map(function ($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'role' => $user->role ? $user->role->nombre : null,
                            'role_id' => $user->role_id,
                            'is_main' => $user->pivot->tipo_relacion === 'PRINCIPAL',
                            'tipo_relacion' => $user->pivot->tipo_relacion,
                            'activo' => $user->pivot->activo,
                        ];
                    }),
                ];
            });

            return $this->success([
                'proveedor_destino' => [
                    'id' => $proveedorDestino->id,
                    'razon_social' => $proveedorDestino->razon_social,
                    'nombre_comercial' => $proveedorDestino->nombre_comercial,
                ],
                'proveedores' => $usuariosPorProveedor,
                'usuarios' => $proveedores->flatMap->users->map(function ($user) use ($proveedores) {
                    $proveedor = $proveedores->first(fn($p) => $p->users->contains($user));
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role ? $user->role->nombre : null,
                        'role_id' => $user->role_id,
                        'is_main' => $user->pivot->tipo_relacion === 'PRINCIPAL',
                        'proveedor_id' => $proveedor->id,
                        'proveedor_razon_social' => $proveedor->razon_social,
                        'suggested_role' => $user->role ? $user->role->nombre : null,
                    ];
                }),
            ], 'Usuarios obtenidos exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al obtener usuarios para reasignar', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'Error al obtener usuarios para reasignación',
                ['exception' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Ejecutar la homologación de proveedores
     * 
     * POST /api/admin/homologacion/ejecutar
     * 
     * Body:
     * {
     *   "proveedor_ids": [1, 2, 3],
     *   "eliminar_proveedores_origen": true
     * }
     */
    public function ejecutarHomologacion(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'proveedor_ids' => 'required|array|min:2',
            'proveedor_ids.*' => 'required|integer|exists:proveedores,id',
            'eliminar_proveedores_origen' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->error(
                'Datos inválidos',
                $validator->errors(),
                422
            );
        }

        try {
            $proveedorIds = $request->input('proveedor_ids');
            $eliminarProveedoresOrigen = $request->input('eliminar_proveedores_origen', true);

            $resultado = $this->homologacionService->homologarProveedores(
                $proveedorIds,
                $eliminarProveedoresOrigen
            );

            return $this->success($resultado, 'Homologación completada exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al ejecutar homologación', [
                'proveedor_ids' => $request->input('proveedor_ids'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'Error al ejecutar la homologación: ' . $e->getMessage(),
                ['exception' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Previsualizar la homologación sin ejecutarla
     * 
     * POST /api/admin/homologacion/previsualizar
     * 
     * Body:
     * {
     *   "proveedor_ids": [1, 2, 3]
     * }
     */
    public function previsualizarHomologacion(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'proveedor_ids' => 'required|array|min:2',
            'proveedor_ids.*' => 'required|integer|exists:proveedores,id',
        ]);

        if ($validator->fails()) {
            return $this->error(
                'Datos inválidos',
                $validator->errors(),
                422
            );
        }

        try {
            $proveedorIds = $request->input('proveedor_ids');

            $preview = $this->homologacionService->previsualizarHomologacion($proveedorIds);

            return $this->success($preview, 'Previsualización generada exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al previsualizar homologación', [
                'error' => $e->getMessage(),
            ]);

            return $this->error(
                'Error al previsualizar la homologación',
                ['exception' => $e->getMessage()],
                500
            );
        }
    }
}
