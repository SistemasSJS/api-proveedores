<?php

namespace App\Http\Controllers\Catalogo;

use App\Http\Controllers\Controller;
use App\Http\Resources\Catalogo\CatalogoPublicoItemResource;
use App\Models\CatalogoPublicoItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogoPublicoItemController extends Controller
{
    /**
     * Resumen de empresas del catálogo público (logo + conteo).
     */
    public function empresas(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('search', ''));

        $query = CatalogoPublicoItem::query()->where('activo', true);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('empresa', 'like', "%{$search}%")
                    ->orWhere('nombre', 'like', "%{$search}%")
                    ->orWhere('codigo', 'like', "%{$search}%")
                    ->orWhere('marca', 'like', "%{$search}%");
            });
        }

        $empresas = $query
            ->select('empresa')
            ->selectRaw('COUNT(*) as total_productos')
            ->selectRaw('MAX(logo) as logo')
            ->groupBy('empresa')
            ->orderBy('empresa')
            ->get()
            ->map(fn ($row) => [
                'empresa' => (string) $row->empresa,
                'logo' => $row->logo ? (string) $row->logo : null,
                'total_productos' => (int) $row->total_productos,
            ])
            ->values()
            ->all();

        return $this->success($empresas, 'Empresas del catálogo público.');
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(CatalogoPublicoItem::getFilters());
        if (! array_key_exists('activo', $filters) || $filters['activo'] === '' || $filters['activo'] === null) {
            $filters['activo'] = true;
        }

        $sortBy = $request->input('sort_by', 'nombre');
        $order = strtolower((string) $request->input('order', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = (int) $request->input('per_page', 20);

        $allowedSort = ['nombre', 'codigo', 'empresa', 'categoria', 'marca', 'precio_base', 'id'];
        if (! in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'nombre';
        }

        $paginator = CatalogoPublicoItem::query()
            ->filter($filters)
            ->orderBy($sortBy, $order)
            ->paginate(max(1, min($perPage, 100)));

        $data = CatalogoPublicoItemResource::collection($paginator)->resolve();

        return $this->paginated(
            $paginator->setCollection(collect($data)),
            'Catálogo público.'
        );
    }

    public function show(CatalogoPublicoItem $catalogoPublicoItem): JsonResponse
    {
        if (! $catalogoPublicoItem->activo) {
            return $this->error('El ítem no está disponible.', null, 404);
        }

        return $this->success(
            new CatalogoPublicoItemResource($catalogoPublicoItem),
            'Ítem del catálogo público.'
        );
    }
}
