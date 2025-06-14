<?php

use App\Enums\UserRoleEnumerate;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminHomeControler;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\SucursalController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ImagenController;
use App\Http\Controllers\ImportProductoController;
use App\Http\Controllers\UnidadMedidaController;
use App\Http\Controllers\LineaController;
use App\Http\Controllers\MarcaController;
use App\Http\Controllers\ProductoCategoriaController;
use App\Http\Controllers\ProductoImagenController;
use App\Http\Controllers\ProveedorUsuarioController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TipoEmpresaController;

use App\Http\Middleware\EnsureProveedorOwnership;
use App\Http\Middleware\ValidateApiAccess;
use App\Http\Middleware\LogApiActions;

/**
 * RUTAS DE AUTENTICACION Y REGISTRO DE USUARIOS
 */
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->middleware([LogApiActions::class]);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('completar-registro', [AuthController::class, 'register_completar']);
    Route::post('register_proveedor', [AuthController::class, 'register_proveedor']);
    Route::post('register_proveedor_completar', [AuthController::class, 'register_proveedor_completar']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::get('logout', [AuthController::class, 'logout']);
        Route::post('update-img-perfil', [AuthController::class, 'update_foto_perfil']);
    });
});

/**
 * Rutas de listado de catalogos
 */
Route::get('roles-index', [RoleController::class, 'index']);
Route::get('categorias-index', [CategoriaController::class, 'index']);
Route::get('subcategorias-index', [CategoriaController::class, 'index']);
Route::get('lineas-index', [LineaController::class, 'index']);
Route::get('marcas-index', [MarcaController::class, 'index']);
Route::get('unidades-medida-index', [UnidadMedidaController::class, 'index']);
Route::get('tipos-empresa-index', [TipoEmpresaController::class, 'index']);

/**
 * Rutas con protección con apitoken
 */
Route::middleware('auth:sanctum')->group(function () {
    Route::middleware('role:' . UserRoleEnumerate::GERENTE->value . ',' . UserRoleEnumerate::ADMINISTRADOR->value)->group(function () {

        /**
         * Gestión de proveedores
         */
        Route::prefix('proveedores')->group(function () {

            Route::controller(ProveedorController::class)->group(function () {
                Route::get('/', 'index');
                Route::post('/', 'store');
                Route::get('{proveedor}', 'show')->middleware([ValidateApiAccess::class]);
                Route::patch('{proveedor}', 'update');
                Route::delete('{proveedor}', 'destroy');
                Route::post('{proveedor}/logo', 'updateLogo');
            });

            Route::post('{proveedor}/import', [ImportProductoController::class, 'import']);

            Route::prefix('{proveedor}/users')->group(function () {
                Route::get('/', [ProveedorUsuarioController::class, 'index']);
                Route::post('/', [ProveedorUsuarioController::class, 'store']);

                // Rutas protegidas por el middleware de catálogo
                Route::middleware('proveedor.user')->group(function () {
                    Route::get('{user}', [ProveedorUsuarioController::class, 'show']);
                    Route::patch('{user}', [ProveedorUsuarioController::class, 'update']);
                    Route::delete('{user}', [ProveedorUsuarioController::class, 'destroy']);
                });
            });

            Route::prefix('{proveedor}/productos')->middleware([EnsureProveedorOwnership::class])->group(function () {
                Route::get('/', [ProductoCategoriaController::class, 'index']);
                Route::post('/', [ProductoCategoriaController::class, 'store']);

                Route::middleware('proveedor.producto')->group(function () {
                    Route::get('{producto}', [ProductoCategoriaController::class, 'show']);
                    Route::patch('{producto}', [ProductoCategoriaController::class, 'update']);
                    // Route::delte('{producto}', [ProductoCategoriaController::class, 'destroy']);
                    Route::post('{producto}/logo', [ProductoCategoriaController::class, 'updateLogo']);
                });
            });
        });

        Route::get('proveedores/user/{id}', [ProveedorController::class, 'getProveedorByUserId']);
    });

    Route::middleware('role:' . UserRoleEnumerate::ADMINISTRADOR->value)->group(function () {
        Route::get('catalogos-resumen', [AdminHomeControler::class, 'getCatalogosCountItems']);

        Route::apiResource('users', UserController::class);
        Route::apiResource('proveedores', ProveedorController::class);
        Route::apiResource('sucursales', SucursalController::class);
        Route::apiResource('productos', ProductoController::class);
        Route::apiResource('imagenes', ProductoImagenController::class);
        Route::apiResource('unidades-medida', UnidadMedidaController::class);
        Route::apiResource('categorias', CategoriaController::class);
        Route::apiResource('lineas', LineaController::class);
        Route::apiResource('marcas', MarcaController::class);
        Route::apiResource('tipos-empresa', TipoEmpresaController::class);
    });
});
