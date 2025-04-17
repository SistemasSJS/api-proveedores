<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use App\Exceptions\Api\BaseApiException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    protected $dontReport = [];

    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->renderable(function (BaseApiException $e, $request) {
            return $e->render($request);
        });

        $this->renderable(function (AuthenticationException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.',
                'code' => 401
            ], 401);
        });

        $this->renderable(function (ValidationException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos.',
                'errors' => $e->errors(),
                'code' => 422
            ], 422);
        });

        $this->renderable(function (NotFoundHttpException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => 'Ruta no encontrada.',
                'code' => 404
            ], 404);
        });

        $this->renderable(function (Throwable $e, $request) {
            if (config('app.debug')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'trace' => $e->getTrace(),
                    'code' => $e->getCode() ?: 500
                ], 500);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor.',
                'code' => 500
            ], 500);
        });
    }
}
