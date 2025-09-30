<?php

namespace App\Http\Resources\Import;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ImportHistoryCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'total' => $this->resource->total(),
                'count' => $this->resource->count(),
                'per_page' => $this->resource->perPage(),
                'current_page' => $this->resource->currentPage(),
                'total_pages' => $this->resource->lastPage(),
                'from' => $this->resource->firstItem(),
                'to' => $this->resource->lastItem(),
            ],
            'links' => [
                'first' => $this->resource->url(1),
                'last' => $this->resource->url($this->resource->lastPage()),
                'prev' => $this->resource->previousPageUrl(),
                'next' => $this->resource->nextPageUrl(),
            ],
            'stats' => $this->getCollectionStats(),
        ];
    }

    /**
     * Get collection statistics
     */
    private function getCollectionStats(): array
    {
        $items = $this->collection;

        if ($items->isEmpty()) {
            return [];
        }

        return [
            'total_importaciones' => $items->count(),
            'estados' => [
                'completed' => $items->where('estado', 'completed')->count(),
                'processing' => $items->where('estado', 'processing')->count(),
                'pending' => $items->where('estado', 'pending')->count(),
                'failed' => $items->where('estado', 'failed')->count(),
                'cancelled' => $items->where('estado', 'cancelled')->count(),
            ],
            'tipos' => [
                'productos' => $items->where('tipo', 'productos')->count(),
                'marcas' => $items->where('tipo', 'marcas')->count(),
                'categorias' => $items->where('tipo', 'categorias')->count(),
                'mixed' => $items->where('tipo', 'mixed')->count(),
            ],
            'formatos' => [
                'csv' => $items->where('formato', 'csv')->count(),
                'xlsx' => $items->where('formato', 'xlsx')->count(),
                'json' => $items->where('formato', 'json')->count(),
            ],
            'resumen' => [
                'total_registros_procesados' => $items->sum('total_registros'),
                'total_nuevos' => $items->sum('nuevos'),
                'total_actualizados' => $items->sum('actualizados'),
                'total_eliminados' => $items->sum('eliminados'),
                'total_errores' => $items->sum('errores'),
                'promedio_progreso' => $items->whereNotNull('progreso')->avg('progreso'),
                'con_errores' => $items->where('errores', '>', 0)->count(),
                'sin_errores' => $items->where('errores', 0)->count(),
            ],
            'fechas' => [
                'ultima_importacion' => $items->max('created_at'),
                'primera_importacion' => $items->min('created_at'),
                'importaciones_hoy' => $items->where('created_at', '>=', now()->startOfDay())->count(),
                'importaciones_esta_semana' => $items->where('created_at', '>=', now()->startOfWeek())->count(),
                'importaciones_este_mes' => $items->where('created_at', '>=', now()->startOfMonth())->count(),
            ]
        ];
    }

    /**
     * Get additional data to append to the response
     */
    public function with(Request $request): array
    {
        return [
            'filters_applied' => array_filter($request->only([
                'search',
                'tipo',
                'estado',
                'formato',
                'date_from',
                'date_to',
                'fase',
                'has_errors',
                'min_registros',
                'max_registros'
            ])),
            'sort_options' => [
                'created_at' => 'Fecha de creación',
                'updated_at' => 'Fecha de actualización',
                'tipo' => 'Tipo',
                'estado' => 'Estado',
                'archivo' => 'Archivo',
                'total_registros' => 'Total de registros',
            ],
            'filter_options' => [
                'tipos' => [
                    ['value' => 'productos', 'label' => 'Productos'],
                    ['value' => 'marcas', 'label' => 'Marcas'],
                    ['value' => 'categorias', 'label' => 'Categorías'],
                    ['value' => 'mixed', 'label' => 'Mixta'],
                ],
                'estados' => [
                    ['value' => 'pending', 'label' => 'Pendiente'],
                    ['value' => 'processing', 'label' => 'Procesando'],
                    ['value' => 'completed', 'label' => 'Completado'],
                    ['value' => 'failed', 'label' => 'Falló'],
                    ['value' => 'cancelled', 'label' => 'Cancelado'],
                ],
                'formatos' => [
                    ['value' => 'csv', 'label' => 'CSV'],
                    ['value' => 'xlsx', 'label' => 'Excel'],
                    ['value' => 'json', 'label' => 'JSON'],
                ],
                'fases' => [
                    ['value' => 'upload', 'label' => 'Subida'],
                    ['value' => 'validation', 'label' => 'Validación'],
                    ['value' => 'processing', 'label' => 'Procesamiento'],
                    ['value' => 'cleanup', 'label' => 'Limpieza'],
                    ['value' => 'completed', 'label' => 'Completado'],
                ],
            ]
        ];
    }
}
