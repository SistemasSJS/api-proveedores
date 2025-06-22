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
use App\Http\Controllers\ProveedorProductoController;
use App\Http\Controllers\ProductoImagenController;
use App\Http\Controllers\ProductoImportController;
use App\Http\Controllers\ProveedorCategoriaController;
use App\Http\Controllers\ProveedorLineaController;
use App\Http\Controllers\ProveedorMarcaController;
use App\Http\Controllers\ProveedorTipoEmpresaController;
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
    Route::post('login', [AuthController::class, 'login'])->middleware(['audit']); // LogApiActions alias es 'audit'
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
// Route::get('categorias-index', [CategoriaController::class, 'index']);
// Route::get('subcategorias-index', [CategoriaController::class, 'index']);
// Route::get('lineas-index', [LineaController::class, 'index']);
// Route::get('marcas-index', [MarcaController::class, 'index']);
// Route::get('unidades-medida-index', [UnidadMedidaController::class, 'index']);
Route::get('tipos-empresa-index', [TipoEmpresaController::class, 'index']);
/**
 * Rutas protegidas con apitoken
 */
Route::middleware('auth:sanctum')->group(function () {
    Route::middleware('role:' . UserRoleEnumerate::GERENTE->value . ',' . UserRoleEnumerate::ADMINISTRADOR->value)->group(function () {

        /**
         * Gestión de proveedores
         */
        Route::prefix('proveedores')->group(function () {

            /**
             * PROVEDOR Y RUTAS DE GESTION DE PERFIL
             */
            Route::controller(ProveedorController::class)->group(function () {
                Route::get('/', 'index')->middleware(['audit']); // Auditar listado de proveedores
                Route::post('/', 'store')->middleware(['audit']); // Auditar creación de proveedor
                Route::get('{proveedor}', 'show')->middleware(['api.access', 'audit']); // Auditar consulta individual
                Route::patch('{proveedor}', 'update')->middleware(['api.access', 'audit']); // Auditar actualización
                Route::delete('{proveedor}', 'destroy')->middleware(['api.access', 'audit']); // Auditar eliminación
                Route::post('{proveedor}/logo', 'updateLogo')->middleware(['api.access', 'audit']); // Auditar actualización de logo
            });


            // routes/api.php
            Route::prefix('{proveedor}/imports')->middleware(['proveedor.access'])->group(function () {
                Route::post('products', [ProductoImportController::class, 'upload']);
                Route::get('products', [ProductoImportController::class, 'list']);
                Route::get('{audit}', [ProductoImportController::class, 'status']);
                Route::get('{audit}/logs', [ProductoImportController::class, 'status']);
                Route::post('{audit}/confirm',  [ProductoImportController::class, 'confirm']);
            });
            Route::get('/imports/products/template', [ProductoImportController::class, 'downloadTemplate']);



            // TODO: Move to routes of products
            // Route::post('{proveedor}/import', [ImportProductoController::class, 'import'])->middleware(['api.access', 'audit']); // Auditar importación

            /**
             * USUARIOS
             */
            Route::prefix('{proveedor}/users')->middleware(['proveedor.access'])->group(function () {
                Route::get('/', [ProveedorUsuarioController::class, 'index'])->middleware(['api.access']); // Validar proveedor accesible
                Route::post('/', [ProveedorUsuarioController::class, 'store'])->middleware(['api.access', 'audit']); // Auditar creación de usuario

                Route::middleware(['api.access', 'proveedor.user', 'audit'])->group(function () {
                    Route::get('{user}', [ProveedorUsuarioController::class, 'show']);
                    Route::patch('{user}', [ProveedorUsuarioController::class, 'update']);
                    Route::delete('{user}', [ProveedorUsuarioController::class, 'destroy']);
                    Route::post('{user}/logo', [ProveedorUsuarioController::class, 'updateLogo']);
                });
            });

            /**p
             * PRODUCTOS
             */
            Route::prefix('{proveedor}/productos')->middleware(['proveedor.access'])->group(function () {
                Route::get('/', [ProveedorProductoController::class, 'index'])->middleware(['audit']); // Auditar listado
                Route::post('/', [ProveedorProductoController::class, 'store'])->middleware(['audit']); // Auditar creación

                Route::middleware(['proveedor.producto', 'audit'])->group(function () {
                    Route::get('{producto}', [ProveedorProductoController::class, 'show']);
                    Route::patch('{producto}', [ProveedorProductoController::class, 'update']);
                    Route::delete('{producto}', [ProveedorProductoController::class, 'destroy']); // <-- Habilitado y auditado
                    Route::post('{producto}/logo', [ProveedorProductoController::class, 'updateLogo']);
                });
            });

            /**
             * CATEGORIAS
             */
            Route::prefix('{proveedor}/categorias')->middleware(['proveedor.access'])->group(function () {
                Route::get('/', [ProveedorCategoriaController::class, 'index'])->middleware(['audit']); // Auditar listado
                Route::post('/', [ProveedorCategoriaController::class, 'store'])->middleware(['audit']); // Auditar creación

                Route::middleware(['proveedor.categoria', 'audit'])->group(function () {
                    Route::get('{categoria}', [ProveedorCategoriaController::class, 'show']);
                    Route::patch('{categoria}', [ProveedorCategoriaController::class, 'update']);
                    Route::delete('{categoria}', [ProveedorCategoriaController::class, 'destroy']); // <-- Habilitado y auditado
                    Route::post('{categoria}/logo', [ProveedorCategoriaController::class, 'updateLogo']);
                    Route::get('{categoria}/subcategorias', [ProveedorCategoriaController::class, 'index_sub_categorias'])->middleware(['audit']); // Auditar listado
                });
            });

            /**
             * MARCAS
             */
            Route::prefix('{proveedor}/marcas')->middleware(['proveedor.access'])->group(function () {
                Route::get('/', [ProveedorMarcaController::class, 'index'])->middleware(['audit']); // Auditar listado
                Route::post('/', [ProveedorMarcaController::class, 'store'])->middleware(['audit']); // Auditar creación

                Route::middleware(['proveedor.marca', 'audit'])->group(function () {
                    Route::get('{marca}', [ProveedorMarcaController::class, 'show']);
                    Route::patch('{marca}', [ProveedorMarcaController::class, 'update']);
                    Route::delete('{marca}', [ProveedorMarcaController::class, 'destroy']); // <-- Habilitado y auditado
                    Route::post('{marca}/logo', [ProveedorMarcaController::class, 'updateLogo']);
                    Route::get('/{marca}/lineas', [ProveedorMarcaController::class, 'index_lineas_por_marca'])->middleware(['audit']); // Auditar listado
                });
            });

            /**
             * TIPOS DE EMRPESA
             */
            Route::prefix('{proveedor}/lineas')->middleware(['proveedor.access'])->group(function () {
                Route::get('/', [ProveedorLineaController::class, 'index'])->middleware(['audit']); // Auditar listado
                Route::post('/', [ProveedorLineaController::class, 'store'])->middleware(['audit']); // Auditar creación

                //     Route::middleware(['proveedor.linea', 'audit'])->group(function () {
                //         Route::get('{linea}', [ProveedorLineaController::class, 'show']);
                //         Route::patch('{linea}', [ProveedorLineaController::class, 'update']);
                //         Route::delete('{linea}', [ProveedorLineaController::class, 'destroy']); // <-- Habilitado y auditado
                // });
            });

            /**
             * RUTAS DE RECURSOS SELECT
             *
             *  - [ ] roles-index:----------:=> /api/proveedores/{proveedor_id}/roles
             *  - [x] categorias-index:-----:=> /api/proveedores/{proveedor_id}/categorias
             *  - [x] subcategorias-index:--:=> /api/proveedores/{proveedor_id}/categorias/{categoria_id}/subcategorias
             *  - [x] marcas-index:---------:=> /api/proveedores/{proveedor_id}/marcas
             *  - [x] lineas-index:---------:=> /api/proveedores/{proveedor_id},/marcas/{marca}/lineas
             *  - [ ] unidades-medida-index::=> /api/proveedores/{proveedor_id}/unidades-medida
             *   - [x] tipos-empresa-index:--:=> /api/proveedores/{proveedor_id}/tipos-empresa
             */
        });

        Route::get('proveedores/user/{id}', [ProveedorController::class, 'getProveedorByUserId'])->middleware(['audit']); // Auditar consulta especial
    });

    Route::middleware('role:' . UserRoleEnumerate::ADMINISTRADOR->value)->group(function () {
        Route::get('catalogos-resumen', [AdminHomeControler::class, 'getCatalogosCountItems'])->middleware(['audit']);

        Route::apiResource('users', UserController::class)->middleware(['audit']);
        Route::apiResource('proveedores', ProveedorController::class)->middleware(['audit']);
        Route::apiResource('sucursales', SucursalController::class)->middleware(['audit']);
        Route::apiResource('productos', ProductoController::class)->middleware(['audit']);
        Route::apiResource('imagenes', ProductoImagenController::class)->middleware(['audit']);
        Route::apiResource('unidades-medida', UnidadMedidaController::class)->middleware(['audit']);
        Route::apiResource('categorias', CategoriaController::class)->middleware(['audit']);
        Route::apiResource('lineas', LineaController::class)->middleware(['audit']);
        Route::apiResource('marcas', MarcaController::class)->middleware(['audit']);
        Route::apiResource('tipos-empresa', TipoEmpresaController::class)->middleware(['audit']);
    });
});
