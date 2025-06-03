<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use App\Exceptions\Api\BaseApiException;
use App\Traits\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    use ApiResponse;

    protected $dontReport = [];

    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->renderable(function (BaseApiException $e, $request) {
            if ($request->expectsJson()) {
                return $e->render($request);
            }
        });

        $this->renderable(function (AuthenticationException $e, $request) {
            if ($request->expectsJson()) {
                return $this->error('No autenticado.', null, 401);
            }
        });

        $this->renderable(function (ValidationException $e, $request) {
            if ($request->expectsJson()) {
                return $this->error('Datos inválidos.', [
                    'success' => false,
                    'error_type' => 'validation_error',
                    'fields' => $e->errors(),
                ], 422);
            }
        });

        $this->renderable(function (NotFoundHttpException $e, $request) {
            if ($request->expectsJson()) {
                return $this->error('Ruta no encontrada.', null, 404);
            }
        });

        $this->renderable(function (Throwable $e, $request) {
            if ($request->expectsJson()) {
                if (config('app.debug')) {
                    return response()->json([
                        'status' => 'ERROR',
                        'code' => 500,
                        'message' => $e->getMessage(),
                        'data' => null,
                        'errors' => [
                            'exception' => get_class($e),
                            'trace' => $e->getTrace(),
                        ],
                    ], 500);
                }

                return $this->error('Error interno del servidor.', null, 500);
            }
        });
    }
}
