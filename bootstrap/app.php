<?php

use App\Http\Middleware\EnsureCategoriaBelongsToProveedor;
use App\Http\Middleware\EnsureMarcaBelongsToProveedor;
use App\Http\Middleware\EnsureProductoBelongsToProveedor;
use App\Http\Middleware\EnsureUserBelongsToProveedor;
use App\Http\Middleware\LogApiActions;
use App\Http\Middleware\LogIncomingRequests;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\EnsureProveedorOwnership;
use App\Http\Middleware\ValidateApiAccess;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Middleware globales (todos van con append)
        $middleware->append([
            \Illuminate\Http\Middleware\HandleCors::class,
            \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
            \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
            LogIncomingRequests::class,
        ]);

        // Alias de middleware (middleware individuales o agrupados)
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'proveedor.user' => EnsureUserBelongsToProveedor::class,
            'proveedor.producto' => EnsureProductoBelongsToProveedor::class,
            'proveedor.categoria' => EnsureCategoriaBelongsToProveedor::class,
            'proveedor.marca' => EnsureMarcaBelongsToProveedor::class,
            'proveedor.access' => EnsureProveedorOwnership::class,
            'api.access' => ValidateApiAccess::class,
            'audit' => LogApiActions::class,
            'proveedor.full' => [
                'auth:sanctum',
                'api.access',
                'proveedor.access',
            ],
            'proveedor.admin' => [
                'auth:sanctum',
                'api.access',
                'proveedor.access',
                'audit',
            ],
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Aquí puedes configurar el manejo de excepciones
    })
    ->create();
