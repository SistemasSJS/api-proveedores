<?php

namespace App\Providers;

use App\Exceptions\Handler;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Observers\ProductoObserver;
use App\Policies\SucursalPolicy;
use App\Services\DashboardService;
use App\Services\ProductoSearchService;
use App\Services\ReporteService;
use App\Services\SucursalService;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        Sucursal::class => SucursalPolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ExceptionHandler::class, Handler::class);

        $this->app->singleton(SucursalService::class, function ($app) {
            return new SucursalService;
        });

        $this->app->singleton(ProductoSearchService::class, function ($app) {
            return new ProductoSearchService;
        });

        $this->app->singleton(ReporteService::class, function ($app) {
            return new ReporteService;
        });

        $this->app->singleton(DashboardService::class, function ($app) {
            return new DashboardService;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        // Registrar Observers
        Producto::observe(ProductoObserver::class);

        // Configurar timezone si es necesario
        if (config('app.timezone')) {
            date_default_timezone_set(config('app.timezone'));
        }

        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        if (env('APP_ENV') === 'production') {
            $this->app['request']->server->set('HTTPS', true);
        }
    }
}
