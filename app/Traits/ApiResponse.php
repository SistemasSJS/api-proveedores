<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponse
{
    protected function success($data = null, string $message = 'Operación exitosa.', int $code = 200): JsonResponse
    {
        return response()->json([
            'status' => 'SUCCESS',
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'errors' => null,
        ], $code, [], JSON_UNESCAPED_UNICODE);
    }

    protected function error(string $message = 'Ha ocurrido un error.', $errors = null, int $code = 400, $data = null): JsonResponse
    {
        return response()->json([
            'status' => 'ERROR',
            'code' => $code,
            'message' => $message,
            'data' => null,
            'errors' => $errors,
        ], $code, [], JSON_UNESCAPED_UNICODE);
    }

    protected function paginated(LengthAwarePaginator $paginator, string $message = 'Datos paginados.', int $code = 200, array $extraMeta = []): JsonResponse
    {
        $meta = [
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ],
        ];

        if (! empty($extraMeta)) {
            $meta = array_merge($meta, $extraMeta);
        }

        return response()->json([
            'status' => 'SUCCESS',
            'code' => $code,
            'message' => $message,
            'meta' => $meta,
            'data' => $paginator->items(),
            'errors' => null,
        ], $code, [], JSON_UNESCAPED_UNICODE);
    }
}
