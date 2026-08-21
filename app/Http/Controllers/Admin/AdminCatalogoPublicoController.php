<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminCatalogoPublicoImportRequest;
use App\Http\Requests\Admin\AdminCatalogoPublicoUpdateRequest;
use App\Http\Resources\Catalogo\CatalogoPublicoImportResultResource;
use App\Http\Resources\Catalogo\CatalogoPublicoItemResource;
use App\Models\CatalogoPublicoItem;
use App\Services\CatalogoPublico\CatalogoPublicoImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

class AdminCatalogoPublicoController extends Controller
{
    public function __construct(private CatalogoPublicoImportService $importService) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(CatalogoPublicoItem::getFilters());
        $sortBy = $request->input('sort_by', 'nombre');
        $order = strtolower((string) $request->input('order', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = (int) $request->input('per_page', 15);

        $allowedSort = [
            'nombre', 'codigo', 'empresa', 'categoria', 'marca',
            'precio_base', 'activo', 'created_at', 'updated_at', 'id',
        ];
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
            'Catálogo público paginado.'
        );
    }

    public function show(CatalogoPublicoItem $catalogoPublicoItem): JsonResponse
    {
        return $this->success(
            new CatalogoPublicoItemResource($catalogoPublicoItem),
            'Ítem del catálogo público.'
        );
    }

    public function import(AdminCatalogoPublicoImportRequest $request): JsonResponse
    {
        try {
            $result = $this->importService->import($request->file('file'));

            return $this->success(
                new CatalogoPublicoImportResultResource($result),
                'Importación del catálogo público completada.'
            );
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), null, 422);
        } catch (Throwable $e) {
            return $this->error('No fue posible importar el catálogo público.', [$e->getMessage()], 500);
        }
    }

    public function update(
        AdminCatalogoPublicoUpdateRequest $request,
        CatalogoPublicoItem $catalogoPublicoItem
    ): JsonResponse {
        $catalogoPublicoItem->update($request->validated());

        return $this->success(
            new CatalogoPublicoItemResource($catalogoPublicoItem->fresh()),
            'Ítem actualizado.'
        );
    }
}
