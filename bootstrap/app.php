<?php

use App\Http\Middleware\EnsureProductoBelongsToProveedor;
use App\Http\Middleware\EnsureUserBelongsToProveedor;
use App\Http\Middleware\LogIncomingRequests;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'proveedor.user' => EnsureUserBelongsToProveedor::class,
            'proveedor.producto' => EnsureProductoBelongsToProveedor::class,
        ]);
        // middleware globals   
        $middleware->append(
            LogIncomingRequests::class
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
