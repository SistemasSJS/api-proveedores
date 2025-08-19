<?php

namespace App\Providers;

use App\Exceptions\Handler;
use App\Models\Notificacion;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Requisicion;
use App\Models\Sucursal;
use App\Observers\ProductoObserver;
use App\Observers\RequisicionObserver;
use App\Policies\NotificacionPolicy;
use App\Policies\ProductoPolicy;
use App\Policies\ProveedorPolicy;
use App\Policies\RequisicionPolicy;
use App\Policies\SucursalPolicy;
use App\Services\AuditService;
use App\Services\DashboardService;
use App\Services\FileParserService;
use App\Services\NotificacionService;
use App\Services\ProductoSearchService;
use App\Services\ReporteService;
use App\Services\RequisicionService;
use App\Services\SucursalService;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{

    /**
     * 
     */
    protected $policies = [
        Requisicion::class => RequisicionPolicy::class,
        Sucursal::class => SucursalPolicy::class,
        Notificacion::class => NotificacionPolicy::class,
        // Producto::class => ProductoPolicy::class,
        // Proveedor::class => ProveedorPolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ExceptionHandler::class, Handler::class);

        // Servicios que se INYECTAN en controladores
        $this->app->singleton(RequisicionService::class, function ($app) {
            return new RequisicionService();
        });

        $this->app->singleton(SucursalService::class, function ($app) {
            return new SucursalService();
        });

        $this->app->singleton(ProductoSearchService::class, function ($app) {
            return new ProductoSearchService();
        });

        $this->app->singleton(ReporteService::class, function ($app) {
            return new ReporteService();
        });

        $this->app->singleton(DashboardService::class, function ($app) {
            return new DashboardService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        // Registrar Observers
        Requisicion::observe(RequisicionObserver::class);
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
