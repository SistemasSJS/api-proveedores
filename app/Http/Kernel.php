<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
  /**
   * The application's global HTTP middleware stack.
   *
   * These middleware are run during every request to your application.
   *
   * @var array<int, class-string|string>
   */
  protected $middleware = [
    // \App\Http\Middleware\TrustHosts::class,
    // \App\Http\Middleware\TrustProxies::class,
    \Illuminate\Http\Middleware\HandleCors::class,
    // \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
    \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
    // \App\Http\Middleware\TrimStrings::class,
    \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
  ];

  /**
   * The application's route middleware groups.
   *
   * @var array<string, array<int, class-string|string>>
   */
  protected $middlewareGroups = [
    'web' => [
      // \App\Http\Middleware\EncryptCookies::class,
      \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
      \Illuminate\Session\Middleware\StartSession::class,
      \Illuminate\View\Middleware\ShareErrorsFromSession::class,
      // \App\Http\Middleware\VerifyCsrfToken::class,
      \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],

    'api' => [
      // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
      \Illuminate\Routing\Middleware\ThrottleRequests::class . ':api',
      \Illuminate\Routing\Middleware\SubstituteBindings::class,

      // Middleware de auditoría - aplicar a todas las rutas API
      \App\Http\Middleware\LogApiActions::class,
    ],
  ];

  /**
   * The application's middleware aliases.
   *
   * Aliases may be used instead of class names to conveniently assign middleware to routes and groups.
   *
   * @var array<string, class-string|string>
   */
  protected $middlewareAliases = [
    // 'auth' => \App\Http\Middleware\Authenticate::class,
    'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
    'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
    'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
    'can' => \Illuminate\Auth\Middleware\Authorize::class,
    // 'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
    'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
    'precognitive' => \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
    // 'signed' => \App\Http\Middleware\ValidateSignature::class,
    'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
    'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,

    // ============================================
    // NUEVOS MIDDLEWARE DE SEGURIDAD Y AUDITORÍA
    // ============================================

    /**
     * Middleware para validar que usuarios solo accedan a recursos de sus proveedores.
     * Uso: Route::middleware(['auth:sanctum', 'proveedor.access'])->group(...)
     *
     * Parámetros opcionales:
     * - 'proveedor.access:proveedor' (por defecto)
     * - 'proveedor.access:supplier' (si el parámetro se llama 'supplier')
     */
    'proveedor.access' => \App\Http\Middleware\EnsureProveedorOwnership::class,

    /**
     * Middleware para rate limiting avanzado basado en roles.
     * Uso: Route::middleware(['auth:sanctum', 'api.access'])->group(...)
     *
     * Aplica automáticamente limits según el rol:
     * - ADMINISTRADOR: 1000 req/min
     * - GERENTE: 500 req/min
     * - SUPERVISOR: 300 req/min
     * - VENTAS: 200 req/min
     * - AUXILIAR: 100 req/min
     * - default: 60 req/min
     */
    'api.access' => \App\Http\Middleware\ValidateApiAccess::class,

    /**
     * Middleware de auditoría para acciones críticas.
     * Ya aplicado globalmente en el grupo 'api', pero puede usarse específicamente:
     * Route::middleware('audit')->group(...)
     */
    'audit' => \App\Http\Middleware\LogApiActions::class,

    // ============================================
    // MIDDLEWARE COMBINADOS PARA USO COMÚN
    // ============================================

    /**
     * Middleware completo para rutas de proveedores.
     * Combina autenticación, validación de acceso y rate limiting.
     * Uso: Route::middleware('proveedor.full')->group(...)
     */
    'proveedor.full' => [
      'auth:sanctum',
      'api.access',
      'proveedor.access'
    ],

    /**
     * Middleware para acciones administrativas de proveedores.
     * Incluye todos los controles de seguridad.
     * Uso: Route::middleware('proveedor.admin')->group(...)
     */
    'proveedor.admin' => [
      'auth:sanctum',
      'api.access',
      'proveedor.access',
      'audit'
    ],
  ];
}
