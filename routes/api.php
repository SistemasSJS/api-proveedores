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
use App\Http\Controllers\ProveedorUsuarioController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TipoEmpresaController;

Route::post('import', [ImportProductoController::class, 'import']);


// Route::get('test', [ProveedorController::class, 'test']);

Route::post('upload', [FileUploadController::class, 'store'])->name('upload');
/**
 * Rutas Publicas. Que no necesitan procteccion
 * FIXME: Config Access Api Token on all routes, and settings CORDS. 
 * TODO: Verificar configuracion de origen para las petiones.  
 */
Route::post('auth/register', [AuthController::class, 'register']);
Route::post('auth/completar-registro', [AuthController::class, 'register_completar']);
Route::post('login', [AuthController::class, 'login']);

/**
 * 
 *  Registro de un proveedor:
 *      1. Primero se registra el proveedor.
 *      2. Envio de corrreo para confirmacion del email.
 *      3. El usuario ingresa la contraseña y la confgirmacion para crear el usuario.
 *      4. Se registra el usuario y se genera una sesion.
 * 
 */
Route::post('register_proveedor', [ProveedorController::class, 'register_proveedor']);
Route::post('register_proveedor_completar', [ProveedorController::class, 'register_proveedor_completar']);


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

    Route::get('me', [AuthController::class, 'me']);  // Obtener datos del proveedor autenticado
    Route::get('logout', [AuthController::class, 'logout']);  // Obtener datos del proveedor autenticado
    Route::post('update-img-perfil', [AuthController::class, 'update_foto_perfil']);  // Obtener datos del proveedor autenticado


    Route::middleware(
        'role:'
            . UserRoleEnumerate::PROVEEDOR->value . ','
            . UserRoleEnumerate::SUPER_ADMIN->value . ','
            . UserRoleEnumerate::ADMIN->value
    )->group(function () {

        Route::post('proveedor/update-logo', [ProveedorController::class, 'updateLogo']);
        Route::get('proveedor/user/{id}', [ProveedorController::class, 'getProveedorByUserId']);

        /**
         * Gestion de usarios de proveedor
         */
        Route::controller(ProveedorUsuarioController::class)->group(function () {
            Route::post('proveedores/{proveedor}/users', 'store');
            Route::get('proveedores/{proveedor}/users', 'index');
            Route::get('proveedores/{proveedor}/users/{user}', 'getById');
            Route::patch('proveedores/{proveedor}/users/{user}', 'update');
            Route::delete('proveedores/{proveedor}/users/{user}', 'destroy');
        });

        /**
         * Gestion de catalogos de proveedor
         */
        Route::apiResource('catalogos', CatalogoController::class);

        Route::prefix('proveedores/{id}')->group(function () {
            Route::get('productos', [ProveedorController::class, 'productosPorProveedor']);
            Route::get('sucursales', [ProveedorController::class, 'sucursalesPorProveedor']);
        });
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
