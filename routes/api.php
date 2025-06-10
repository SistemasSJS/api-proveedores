<?php

use App\Enums\UserRoleEnumerate;
use App\Http\Controllers\AdminHomeControler;
use Illuminate\Support\Facades\Route;
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
use App\Http\Controllers\ProductoCatalogoController;
use App\Http\Controllers\ProveedorUsuarioController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TipoEmpresaController;

// Route::get('test', [ProveedorController::class, 'test']);
// Route::post('upload', [FileUploadController::class, 'store'])->name('upload');



/**
 * RUTAS DE AUTENTICACION Y REGISTRO DE USUASIROS
 */
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('completar-registro', [AuthController::class, 'register_completar']);
    Route::post('register_proveedor', [AuthController::class, 'register_proveedor']);
    Route::post('register_proveedor_completar', [AuthController::class, 'register_proveedor_completar']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);  // Obtener datos del proveedor autenticado
        Route::get('logout', [AuthController::class, 'logout']);  // Obtener datos del proveedor autenticado
        Route::post('update-img-perfil', [AuthController::class, 'update_foto_perfil']);  // Obtener datos del proveedor autenticado
    });
});



/**
 * Rutas de listado de catalogos
 */
Route::get('roles-index', [RoleController::class, 'index']);
Route::get('categorias-index', [CategoriaController::class, 'index']);
Route::get('lineas-index', [LineaController::class, 'index']);
Route::get('marcas-index', [MarcaController::class, 'index']);
Route::get('unidades-medida-index', [UnidadMedidaController::class, 'index']);
Route::get('tipos-empresa-index', [TipoEmpresaController::class, 'index']);


/**
 * Rutas con proteccion con apitoken
 */
Route::middleware(
    'auth:sanctum'
)->group(function () {

    Route::middleware(
        'role:'
            . UserRoleEnumerate::PROVEEDOR->value . ','
            . UserRoleEnumerate::SUPER_ADMIN->value . ','
            . UserRoleEnumerate::ADMIN->value
    )->group(function () {

        /**
         * Gestión de proveedores
         */
        Route::prefix('proveedores')->group(function () {

            // CRUD de proveedores
            Route::controller(ProveedorController::class)->group(function () {
                Route::get('/', 'index');
                Route::post('/', 'store');
                Route::get('{proveedor}', 'show');
                Route::patch('{proveedor}', 'update');
                Route::delete('{proveedor}', 'destroy');

                // Extras RESTful
                Route::post('{proveedor}/logo', 'updateLogo');
                Route::post('{proveedor}/logo', 'updateLogo');
            });
            Route::post('{proveedor}/import', [ImportProductoController::class, 'import']);

            // Usuarios asociados al proveedor
            Route::prefix('{proveedor}/users')->controller(ProveedorUsuarioController::class)->group(function () {
                Route::get('/', 'index');
                Route::post('/', 'store');
                Route::get('{user}', 'getById');
                Route::patch('{user}', 'update');
                Route::delete('{user}', 'destroy');
                // Route::post('{user}', 'upload_perfil');
            });

            // Gestión de catálogos del proveedor
            Route::prefix('{proveedor}/catalogos')->group(function () {
                // Rutas sin middleware
                Route::get('/', [CatalogoController::class, 'index']);
                Route::post('/', [CatalogoController::class, 'store']);

                // Rutas protegidas por el middleware de catálogo
                Route::middleware('catalogo.proveedor')->group(function () {
                    Route::get('{catalogo}', [CatalogoController::class, 'show']);
                    Route::patch('{catalogo}', [CatalogoController::class, 'update']);
                    Route::delete('{catalogo}', [CatalogoController::class, 'destroy']);
                    Route::post('{catalogo}', [CatalogoController::class, 'upload_perfil']);


                    // Rutas productos por catalogo
                    Route::prefix('{catalogo}/productos')->group(function () {
                        Route::get('/', [ProductoCatalogoController::class, 'index']);
                        Route::post('/', [ProductoCatalogoController::class, 'store']);

                        Route::middleware('catalogo.producto')->group(function () {
                            Route::get('{producto}', [ProductoCatalogoController::class, 'show']);
                            Route::patch('{producto}', [ProductoCatalogoController::class, 'update']);
                            Route::post('{producto}/logo', [ProductoCatalogoController::class, 'updateLogo']);
                            // Route::delete('{producto}', [ProductoCatalogoController::class, 'destroy']);
                        });
                    });
                });
            });
        });

        /**
         * Extra: Obtener proveedor por ID de usuario
         */
        Route::get('proveedores/user/{id}', [ProveedorController::class, 'getProveedorByUserId']);
    });

    /**
     * Rutas para los CRUDS. Only admins
     */
    Route::middleware(
        'role:'
            . UserRoleEnumerate::SUPER_ADMIN->value . ','
            . UserRoleEnumerate::ADMIN->value
    )->group(function () {
        Route::get('catalogos-resumen', [AdminHomeControler::class, 'getCatalogosCountItems']);

        Route::apiResource('users', UserController::class);
        Route::apiResource('proveedores', ProveedorController::class);
        Route::apiResource('sucursales', SucursalController::class);
        Route::apiResource('productos', ProductoController::class);
        Route::apiResource('imagenes', ImagenController::class);
        Route::apiResource('unidades-medida', UnidadMedidaController::class);
        Route::apiResource('categorias', CategoriaController::class);
        Route::apiResource('lineas', LineaController::class);
        Route::apiResource('marcas', MarcaController::class);
        Route::apiResource('tipos-empresa', TipoEmpresaController::class);
    });
});
