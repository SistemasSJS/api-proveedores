<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use App\Models\Proveedor;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::none();
        });

        /**
         * Route Model Binding condicional
         * Si la ruta es admin → ignora el global scope
         */
        Route::bind('proveedor', function ($value, $route) {

            // Si la URL empieza con api/admin
            if (str_starts_with($route->uri(), 'api/admin')) {
                return Proveedor::withoutGlobalScope('solo_activos')
                    ->where('id', $value)
                    ->firstOrFail();
            }

            // Rutas normales → aplica scope
            return Proveedor::where('id', $value)->firstOrFail();
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));
        });
    }
}
