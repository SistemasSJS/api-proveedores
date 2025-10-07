<?php

use App\Enums\UserRoleEnumerate;
use App\Http\Controllers\ConstruccController;
use App\Http\Controllers\ConstruccCotizacionController;
use App\Http\Controllers\ConstruccSolicitudPagoController;
use App\Http\Middleware\CheckApiKey;
use Illuminate\Support\Facades\Route;

/**
 *--------------------------------------------------------------------------
 * RUTAS DEL MÓDULO DE CONSTRUCCIÓN
 *--------------------------------------------------------------------------
 * Rutas específicas para el sistema de construcción con autenticación API token.
 * Incluye búsquedas avanzadas, filtros múltiples y recursos paginados/no paginados.
 *
 * Estructura:
 * - Proveedores paginados con filtros
 * - Productos por proveedor paginados 
 * - Búsquedas avanzadas paginadas
 * - Recursos sin paginación para dropdowns
 *
 * Autenticación: API Token (Sanctum)
 * Auditoría: Habilitada en todas las rutas
 */

Route::prefix('construcc')
    // ->middleware(['auth:sanctum', 'role:' . UserRoleEnumerate::CONSTUCC_APP->value])
    ->middleware(CheckApiKey::class) // FIXME: Revisar autenticación mediante API Key.
    ->name('construcc.')
    ->group(function () {

        /**
         *--------------------------------------------------------------------------
         * PROVEEDORES - Con Paginación
         *--------------------------------------------------------------------------
         * Lista de proveedores con filtros y búsqueda avanzada
         */
        Route::prefix('proveedores')->name('proveedores.')->group(function () {
            // Lista paginada de proveedores con filtros
            Route::get('/', [ConstruccController::class, 'proveedores'])
                ->middleware(['audit'])
                ->name('index');

            // Búsqueda avanzada de proveedores
            Route::get('buscar', [ConstruccController::class, 'buscarProveedores'])
                ->middleware(['audit'])
                ->name('buscar');

            // Productos de un proveedor específico (paginado)
            Route::get('{proveedor}/productos', [ConstruccController::class, 'productosPorProveedor'])
                ->middleware(['audit'])
                ->name('productos');

            // Búsqueda en productos de un proveedor específico
            Route::get('{proveedor}/productos/buscar', [ConstruccController::class, 'buscarProductosProveedor'])
                ->middleware(['audit'])
                ->name('productos.buscar');
        });

        /**
         *--------------------------------------------------------------------------
         * PRODUCTOS - Con Paginación
         *--------------------------------------------------------------------------
         * Búsqueda general de productos con filtros avanzados
         */
        Route::prefix('productos')->name('productos.')->group(function () {
            // Búsqueda general de productos con filtros múltiples
            Route::get('buscar', [ConstruccController::class, 'buscarProductos'])
                ->middleware(['audit'])
                ->name('buscar');

            // Filtros disponibles para productos
            Route::get('filtros', [ConstruccController::class, 'filtrosProductos'])
                ->middleware(['audit'])
                ->name('filtros');

            // Sugerencias de productos para autocompletado
            Route::get('sugerencias', [ConstruccController::class, 'sugerenciasProductos'])
                ->middleware(['audit'])
                ->name('sugerencias');
        });

        /**
         *--------------------------------------------------------------------------
         * CATÁLOGOS - Sin Paginación (Para Dropdowns)
         *--------------------------------------------------------------------------
         * Recursos de catálogo específicos por proveedor sin paginación
         */
        Route::prefix('catalogos')->name('catalogos.')->group(function () {
            // Marcas de un proveedor específico
            Route::get('proveedores/{proveedor}/marcas', [ConstruccController::class, 'marcasProveedor'])
                ->middleware(['audit'])
                ->name('marcas');

            // Categorías de un proveedor (con/sin subcategorías)
            Route::get('proveedores/{proveedor}/categorias', [ConstruccController::class, 'categoriasProveedor'])
                ->middleware(['audit'])
                ->name('categorias');

            // Unidades de medida de un proveedor específico
            Route::get('proveedores/{proveedor}/unidades', [ConstruccController::class, 'unidadesProveedor'])
                ->middleware(['audit'])
                ->name('unidades');
        });

        /**
         *--------------------------------------------------------------------------
         * ESTADÍSTICAS Y REPORTES
         *--------------------------------------------------------------------------
         * Información agregada para dashboard y reportes
         */
        Route::prefix('reportes')->name('reportes.')->group(function () {
            // Estadísticas generales del módulo
            Route::get('estadisticas', [ConstruccController::class, 'estadisticas'])
                ->middleware(['audit'])
                ->name('estadisticas');

            // Resumen por proveedor
            Route::get('proveedores/{proveedor}/resumen', [ConstruccController::class, 'resumenProveedor'])
                ->middleware(['audit'])
                ->name('resumen');
        });

        /**
         *--------------------------------------------------------------------------
         * CONFIGURACIÓN Y METADATOS
         *--------------------------------------------------------------------------
         * Endpoints para configuración del módulo
         */
        Route::prefix('config')->name('config.')->group(function () {
            // Filtros disponibles globalmente
            Route::get('filtros-disponibles', [ConstruccController::class, 'filtrosDisponibles'])
                ->middleware(['audit'])
                ->name('filtros');

            // Opciones de ordenamiento disponibles
            Route::get('opciones-ordenamiento', [ConstruccController::class, 'opcionesOrdenamiento'])
                ->middleware(['audit'])
                ->name('ordenamiento');
        });

        /**
         *--------------------------------------------------------------------------
         * COTIZACIONES - CRUD Completo
         *--------------------------------------------------------------------------
         * Gestión de cotizaciones con sus detalles
         */
        Route::prefix('cotizaciones')->group(function () {
            Route::get('/', [ConstruccCotizacionController::class, 'index'])->middleware(['audit']);
            Route::post('/', [ConstruccCotizacionController::class, 'store'])->middleware(['audit']);
            Route::middleware(['audit'])->group(function () {
                Route::get('{cotizacion}', [ConstruccCotizacionController::class, 'show']);
                Route::patch('{cotizacion}', [ConstruccCotizacionController::class, 'update']);
                Route::delete('{cotizacion}', [ConstruccCotizacionController::class, 'destroy']);
            });
        });

        /*** 
         *--------------------------------------------------------------------------
         * SOLICITUDES DE PAGO - CRUD Completo
         *--------------------------------------------------------------------------
         * Gestión de solicitudes de pago con cambios de estatus y fechas
         */
        Route::prefix('solicitudes-pago')->name('solicitudes-pago.')->group(function () {

            // Listado y detalle (solo lectura en ConstruccApp)
            Route::get('/', [ConstruccSolicitudPagoController::class, 'index'])->name('index');
            // Listados especializados por rol y estado
            Route::get('por-rol', [ConstruccSolicitudPagoController::class, 'listarPorRol'])->name('por-rol');
            Route::get('por-estado', [ConstruccSolicitudPagoController::class, 'listarPorEstado'])->name('por-estado');
            Route::get('estadisticas-rol', [ConstruccSolicitudPagoController::class, 'estadisticasPorRol'])->name('estadisticas-rol');

            // Endpoints auxiliares
            Route::get('empresas-constructoras/search', [ConstruccSolicitudPagoController::class, 'empresasConstructoras'])->name('empresas-search');
            Route::get('estadisticas', [ConstruccSolicitudPagoController::class, 'estadisticas'])->name('estadisticas');
            Route::get('{solicitudPago}', [ConstruccSolicitudPagoController::class, 'show'])->name('show');

            // Gestión de archivos - Descargas protegidas
            Route::get('{solicitudPago}/comprobante/download', [ConstruccSolicitudPagoController::class, 'descargarComprobante'])->name('descargar-comprobante');
            Route::get('{solicitudPago}/factura-pdf/download', [ConstruccSolicitudPagoController::class, 'descargarFacturaPdf'])->name('descargar-factura-pdf');
            Route::get('{solicitudPago}/factura-xml/download', [ConstruccSolicitudPagoController::class, 'descargarFacturaXml'])->name('descargar-factura-xml');
            Route::get('{solicitudPago}/cotizacion/download', [ConstruccSolicitudPagoController::class, 'descargarCotizacion'])->name('descargar-cotizacion');

            // Cambios de estatus con validaciones por rol
            Route::post('{solicitudPago}/autorizar', [ConstruccSolicitudPagoController::class, 'autorizar'])->name('autorizar');
            Route::post('{solicitudPago}/rechazar', [ConstruccSolicitudPagoController::class, 'rechazar'])->name('rechazar');
            Route::post('{solicitudPago}/confirmar-pago', [ConstruccSolicitudPagoController::class, 'confirmarPago'])->name('confirmar-pago');
        });

        /**
         *--------------------------------------------------------------------------
         * NOTAS DE IMPLEMENTACIÓN
         *--------------------------------------------------------------------------
         *
         * 1. FILTROS MÚLTIPLES:
         *    - Formato: ?categoria=1,2,3&marca=4,5
         *    - Se procesan como arrays en el controlador
         *
         * 2. PAGINACIÓN:
         *    - Parámetros: ?page=1&per_page=20
         *    - Máximo por página: 100
         *    - Por defecto: 20 elementos
         *
         * 3. ORDENAMIENTO:
         *    - Parámetros: ?sort_by=nombre&order=asc
         *    - Campos disponibles definidos en cada método
         *
         * 4. BÚSQUEDA:
         *    - Parámetro: ?buscar=termino
         *    - Busca en múltiples campos (nombre, descripción, etc.)
         *
         * 5. AUTENTICACIÓN:
         *    - Header: Authorization: Bearer {token}
         *    - Token generado con Sanctum
         *
         * 6. AUDITORÍA:
         *    - Todas las rutas registran actividad
         *    - Incluye usuario, acción y parámetros
         */
    });
