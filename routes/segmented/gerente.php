<?php

use App\Enums\UserRoleEnumerate;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CsvImportController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ProveedorSolicitudPagoController;
use App\Http\Controllers\ProveedorCotizacionController;
use App\Http\Controllers\ProveedorMarcaController;
use App\Http\Controllers\ProveedorPedidoController;
use App\Http\Controllers\ProveedorUsuarioController;
use App\Http\Controllers\SucursalProductoController;
use App\Http\Controllers\ProveedorSucursalController;
use App\Http\Controllers\ProveedorProductoController;
use App\Http\Controllers\ProveedorCategoriaController;
use App\Http\Controllers\ProveedorDashboardController;
use App\Http\Controllers\ProveedorUnidadMedidaController;
use App\Http\Controllers\ProveedorCuentaBancariaController;
use App\Http\Controllers\EmpresaConstruccController;
use App\Http\Controllers\OrdenCompraController;
use App\Http\Controllers\OrdenCompraRegistroController;
use App\Http\Controllers\OrdenCompraSolicitudPagoController;
use App\Http\Controllers\ProveedorOrdenCompraDashboardController;
use App\Http\Controllers\ProveedorOrdenCompraController;

/**
 * GESTIÓN DE PROVEEDORES
 */
Route::prefix('proveedores')
    ->middleware(['auth:sanctum', 'role:' . UserRoleEnumerate::GERENTE->value])
    ->group(function () {

        /**
         * CRUD BASICO
         */
        Route::post('/', [ProveedorController::class, 'store'])->middleware(['audit']);
        Route::get('{proveedor}', [ProveedorController::class, 'show'])->middleware(['api.access', 'audit']);
        Route::patch('{proveedor}', [ProveedorController::class, 'update'])->middleware(['api.access', 'audit']);
        Route::delete('{proveedor}', [ProveedorController::class, 'destroy'])->middleware(['api.access', 'audit']);
        Route::post('{proveedor}/logo', [ProveedorController::class, 'updateLogo'])->middleware(['api.access', 'audit']);

        // Consultas especiales
        Route::get('user/{id}', [ProveedorController::class, 'getProveedorByUserId'])->middleware(['audit']);

        /**
         * CUENTAS BANCARIAS
         */
        Route::prefix('{proveedor}/cuentas-bancarias')->middleware(['proveedor.access'])->group(function () {
            Route::get('/', [ProveedorCuentaBancariaController::class, 'index'])->middleware(['api.access', 'audit']);
            Route::post('/', [ProveedorCuentaBancariaController::class, 'store'])->middleware(['api.access', 'audit']);
            Route::middleware(['api.access', 'proveedor.cuenta', 'audit'])->group(function () {
                Route::get('{cuenta}', [ProveedorCuentaBancariaController::class, 'show']);
                Route::patch('{cuenta}', [ProveedorCuentaBancariaController::class, 'update']);
                Route::delete('{cuenta}', [ProveedorCuentaBancariaController::class, 'destroy']);
            });
        });

        Route::prefix('{proveedor}/constancia-fiscal')->middleware(['proveedor.access'])->group(function () {
            Route::post('/', [ProveedorController::class, 'updateConstanciaFiscal'])->middleware(['audit']);
            Route::get('/preview', [ProveedorController::class, 'previewConstanciaFiscal'])->middleware(['audit']);
            Route::get('/download', [ProveedorController::class, 'downloadConstanciaFiscal'])->middleware(['audit']);
        });

        /**
         * USUARIOS DEL PROVEEDOR
         */
        Route::prefix('{proveedor}/users')->middleware(['proveedor.access'])->group(function () {
            Route::get('/', [ProveedorUsuarioController::class, 'index'])->middleware(['audit']);
            Route::post('/', [ProveedorUsuarioController::class, 'store'])->middleware(['audit']);
            Route::middleware(['api.access', 'proveedor.user', 'audit'])->group(function () {
                Route::get('{user}', [ProveedorUsuarioController::class, 'show']);
                Route::patch('{user}', [ProveedorUsuarioController::class, 'update']);
                Route::delete('{user}', [ProveedorUsuarioController::class, 'destroy']);
                Route::post('{user}/logo', [ProveedorUsuarioController::class, 'updateLogo']);
            });
        });

        /**
         *
         * PRODUCTOS DEL PROVEEDOR
         */
        Route::prefix('{proveedor}/productos')->middleware(['proveedor.access'])->group(function () {
            Route::get('/', [ProveedorProductoController::class, 'index'])->middleware(['audit']);
            Route::post('/', [ProveedorProductoController::class, 'store'])->middleware(['audit']);
            Route::middleware(['proveedor.producto', 'audit'])->group(function () {
                Route::get('{producto}', [ProveedorProductoController::class, 'show']);
                Route::patch('{producto}', [ProveedorProductoController::class, 'update']);
                Route::delete('{producto}', [ProveedorProductoController::class, 'destroy']);
                Route::post('{producto}/logo', [ProveedorProductoController::class, 'updateLogo']);
            });
        });

        /**
         * CATEGORÍAS DEL PROVEEDOR
         */
        Route::prefix('{proveedor}/categorias')->middleware(['proveedor.access'])->group(function () {
            Route::get('/', [ProveedorCategoriaController::class, 'index'])->middleware(['audit']);
            Route::post('/', [ProveedorCategoriaController::class, 'store'])->middleware(['audit']);
            Route::get('/all', [ProveedorCategoriaController::class, 'all'])->middleware(['audit']);
            Route::get('/all/count-products', [ProveedorCategoriaController::class, 'categoriasConSubcatCountProductos'])->middleware(['audit']);

            Route::middleware(['proveedor.categoria', 'audit'])->group(function () {
                Route::get('{categoria}', [ProveedorCategoriaController::class, 'show']);
                Route::patch('{categoria}', [ProveedorCategoriaController::class, 'update']);
                Route::delete('{categoria}', [ProveedorCategoriaController::class, 'destroy']);
                Route::post('{categoria}/logo', [ProveedorCategoriaController::class, 'updateLogo']);
                Route::get('{categoria}/subcategorias', [ProveedorCategoriaController::class, 'index_sub_categorias']);
            });
        });

        /**
         * MARCAS DEL PROVEEDOR
         */
        Route::prefix('{proveedor}/marcas')->middleware(['proveedor.access'])->group(function () {
            Route::get('/', [ProveedorMarcaController::class, 'index'])->middleware(['audit']);
            Route::post('/', [ProveedorMarcaController::class, 'store'])->middleware(['audit']);
            Route::get('/all', [ProveedorMarcaController::class, 'all'])->middleware(['audit']);
            Route::middleware(['proveedor.marca', 'audit'])->group(function () {
                Route::get('{marca}', [ProveedorMarcaController::class, 'show']);
                Route::patch('{marca}', [ProveedorMarcaController::class, 'update']);
                Route::delete('{marca}', [ProveedorMarcaController::class, 'destroy']);
                Route::post('{marca}/logo', [ProveedorMarcaController::class, 'updateLogo']);
                // Route::get('{marca}/lineas', [ProveedorMarcaController::class, 'index_lineas_por_marca']);
            });
        });

        /**
         * UNIDA MEDIDA DEL PROVEEDOR
         */
        Route::prefix('{proveedor}/unidades')->middleware(['proveedor.access'])->group(function () {
            Route::get('/', [ProveedorUnidadMedidaController::class, 'index'])->middleware(['audit']);
            Route::post('/', [ProveedorUnidadMedidaController::class, 'store'])->middleware(['audit']);
            Route::get('/all', [ProveedorUnidadMedidaController::class, 'all'])->middleware(['audit']);
            Route::middleware(['proveedor.unidad', 'audit'])->group(function () {
                Route::get('{unidad}', [ProveedorUnidadMedidaController::class, 'show']);
                Route::patch('{unidad}', [ProveedorUnidadMedidaController::class, 'update']);
                Route::delete('{unidad}', [ProveedorUnidadMedidaController::class, 'destroy']);
            });
        });


        /**
         * SUCURSALES DEL PROVEEDOR
         */
        Route::prefix('{proveedor}/sucursales')->middleware(['proveedor.access'])->group(function () {
            Route::get('/', [ProveedorSucursalController::class, 'index'])->middleware(['audit']);
            Route::post('/', [ProveedorSucursalController::class, 'store'])->middleware(['audit']);
            Route::middleware(['proveedor.sucursal', 'audit'])->group(function () {
                Route::get('{sucursal}', [ProveedorSucursalController::class, 'show']);
                Route::delete('{sucursal}', [ProveedorSucursalController::class, 'destroy']);
                Route::patch('{sucursal}', [ProveedorSucursalController::class, 'update']);

                /**
                 * PRODUCTOS POR SUCURSAL
                 */
                Route::prefix('productos')->group(function () {
                    Route::get('/', [SucursalProductoController::class, 'index'])->middleware(['audit']);
                    Route::post('asignar', [SucursalProductoController::class, 'asignarProductos'])->middleware(['audit']);
                    Route::delete('desasignar', [SucursalProductoController::class, 'desasignarProductos'])->middleware(['audit']);
                    Route::patch('{producto}', [SucursalProductoController::class, 'updateStock'])->middleware(['audit']);
                });
            });
        });

        Route::get('{proveedor}/puede-generar-sp', [ProveedorController::class, 'puedeGenerarSP']);


        /**
         * CSV IMPORT ROUTES
         */
        Route::prefix('{proveedor}/csv-import')->middleware(['proveedor.access'])->group(function () {
            Route::post('/upload', [CsvImportController::class, 'upload'])->middleware(['audit']);
            // Route::post('/validate-producto', [CsvImportController::class, 'validateProducto'])->middleware(['audit']);
            Route::post('/confirm', [CsvImportController::class, 'confirm'])->middleware(['audit']);
            Route::get('/status/{auditId}', [CsvImportController::class, 'getImportStatus'])->middleware(['audit']);
            Route::get('/results/{auditId}', [CsvImportController::class, 'getImportResults'])->middleware(['audit']);
            Route::get('/results/{auditId}/export', [CsvImportController::class, 'export'])->middleware(['audit']);
        });

        /**
         * DASHBOARD PROVEEDOR
         */
        Route::prefix('{proveedor}/dashboard')->middleware(['proveedor.access'])->group(function () {
            Route::get('/stats', [ProveedorDashboardController::class, 'getStats'])->middleware(['audit']);
            Route::get('/cotizaciones', [ProveedorDashboardController::class, 'cotizacionesDashboard'])->middleware(['audit']);
        });

        /**
         * COTIZACIONES DEL PROVEEDOR
         */
        Route::prefix('{proveedor}/cotizaciones')->middleware(['proveedor.access'])->group(function () {
            // Listados
            Route::get('/', [ProveedorCotizacionController::class, 'index'])->middleware(['audit']);        // Paginado
            Route::get('/all', [ProveedorCotizacionController::class, 'uindex'])->middleware(['audit']);    // Sin paginación

            // CRUD
            Route::post('/', [ProveedorCotizacionController::class, 'store'])->middleware(['audit']);
            Route::get('/{cotizacion}', [ProveedorCotizacionController::class, 'show'])->middleware(['audit']);
            Route::put('/{cotizacion}', [ProveedorCotizacionController::class, 'update'])->middleware(['audit']);
            Route::delete('/{cotizacion}', [ProveedorCotizacionController::class, 'destroy'])->middleware(['audit']);

            // Descargar archivos
            Route::get('/{cotizacion}/descargar-pdf', [ProveedorCotizacionController::class, 'descargarPdf'])->middleware(['audit']);
        });

        // Route::get('imports/products/template', [ProductoImportController::class, 'downloadTemplate']);



        /**
         * GESTION DE SP POR PROVEEDOR
         */
        Route::prefix('{proveedor}/solicitudes-pago')->middleware(['proveedor.access'])->group(function () {

            // Listados
            Route::get('/', [ProveedorSolicitudPagoController::class, 'index'])->middleware(['audit']);        // Paginado
            Route::get('/all', [ProveedorSolicitudPagoController::class, 'uindex'])->middleware(['audit']);    // Sin paginación
            Route::get('/historico', [ProveedorSolicitudPagoController::class, 'historico'])->middleware(['audit']); // Histórico OC y SP
            Route::get('/conteo-por-estado', [ProveedorSolicitudPagoController::class, 'conteoPorEstado'])->middleware(['audit']); // Conteo por segmento
            Route::get('/dashboard/metricas', [ProveedorSolicitudPagoController::class, 'getDashboardMetrics'])->middleware(['audit']); // Métricas dashboard

            // Empresas de construcción para búsqueda
            Route::get('/empresas-constructoras', [ProveedorSolicitudPagoController::class, 'empresasConstructoras'])->middleware(['audit']);

            // Crear solicitud
            Route::post('/', [ProveedorSolicitudPagoController::class, 'store'])->middleware(['audit']);

            // Operaciones sobre una solicitud específica
            Route::get('/{solicitudPago}', [ProveedorSolicitudPagoController::class, 'show'])->middleware(['audit']);       // Detalle
            Route::put('/{solicitudPago}', [ProveedorSolicitudPagoController::class, 'update'])->middleware(['audit']);     // Actualizar
            Route::delete('/{solicitudPago}', [ProveedorSolicitudPagoController::class, 'destroy'])->middleware(['audit']); // Eliminar

            // Subir comprobante
            Route::post('/{solicitudPago}/subir-comprobante', [ProveedorSolicitudPagoController::class, 'subirComprobantePago'])->middleware(['audit']);

            // Descargar archivos (solo usuarios autorizados)
            Route::get('/{solicitudPago}/descargar-comprobante', [ProveedorSolicitudPagoController::class, 'descargarComprobantePago'])->middleware(['audit']);
            Route::get('/{solicitudPago}/descargar-factura-pdf', [ProveedorSolicitudPagoController::class, 'descargarFacturaPdf'])->middleware(['audit']);
            Route::get('/{solicitudPago}/descargar-factura-xml', [ProveedorSolicitudPagoController::class, 'descargarFacturaXml'])->middleware(['audit']);
            Route::get('/{solicitudPago}/descargar-cotizacion', [ProveedorSolicitudPagoController::class, 'descargarCotizacion'])->middleware(['audit']);

            // Cambiar estado
            Route::post('/{solicitudPago}/confirmar-pago', [ProveedorSolicitudPagoController::class, 'confirmarPagoSP'])->middleware(['audit']);
            Route::post('/{solicitudPago}/procesando', [ProveedorSolicitudPagoController::class, 'procesando'])->middleware(['audit']);
        });

        /**
         * GESTIÓN DE ÓRDENES DE COMPRA
         */
        Route::prefix('{proveedor}/ordenes-compra')
            ->middleware(['proveedor.access'])
            ->group(function () {

                // === RUTAS DE REGISTRO (desde frontend) ===
                /** ESTAS YA NO SON NECESARIAS */
                // Route::post('/registro', [OrdenCompraRegistroController::class, 'store'])->middleware(['audit']);
                // Route::put('/registro/upsert', [OrdenCompraRegistroController::class, 'upsert'])->middleware(['audit']);
                // Route::post('/registro/batch', [OrdenCompraRegistroController::class, 'storeBatch'])->middleware(['audit']);
                // Route::post('/registro/check-existence', [OrdenCompraRegistroController::class, 'checkExistence'])->middleware(['audit']);

                // === NUEVAS RUTAS: CONSULTA DESDE API CONSTRUCCIONES ===
                Route::get('/consultar', [ProveedorOrdenCompraController::class, 'index'])->middleware(['audit']); // Listado de OC del proveedor
                Route::get('/consultar/{ordenCompraId}', [ProveedorOrdenCompraController::class, 'show'])->middleware(['audit']); // Detalle de OC

                // === DASHBOARD Y ESTADÍSTICAS ===
                Route::get('/dashboard', [ProveedorOrdenCompraDashboardController::class, 'dashboard'])->middleware(['audit']); // Dashboard completo
                Route::get('/dashboard/estado-general', [ProveedorOrdenCompraDashboardController::class, 'estadoGeneral'])->middleware(['audit']); // Estado OC/SP
                Route::get('/dashboard/actividad-reciente', [ProveedorOrdenCompraDashboardController::class, 'actividadReciente'])->middleware(['audit']); // Actividad
                Route::get('/dashboard/metricas', [ProveedorOrdenCompraDashboardController::class, 'metricas'])->middleware(['audit']); // Métricas rendimiento
                Route::get('/dashboard/estadisticas', [ProveedorOrdenCompraDashboardController::class, 'estadisticas'])->middleware(['audit']); // Legacy (compatibilidad)
                Route::get('/alertas/sin-solicitudes', [ProveedorOrdenCompraDashboardController::class, 'ordenesSinSolicitudes'])->middleware(['audit']);
                Route::get('/contadores/sp', [ProveedorOrdenCompraDashboardController::class, 'contadores'])->middleware(['audit']);

                // === RUTAS PRINCIPALES DE CONSULTA ===
                Route::get('/', [ProveedorOrdenCompraDashboardController::class, 'index'])->middleware(['audit']); // Dashboard con listado y filtros
                Route::get('/disponibles/conversion', [ProveedorOrdenCompraDashboardController::class, 'getOrdenesDisponibles'])->middleware(['audit']);
                Route::get('/{ordenCompra}', [ProveedorOrdenCompraDashboardController::class, 'show'])->middleware(['audit']); // Detalle OC
                Route::get('/{ordenCompra}/solicitudes-pago', [ProveedorOrdenCompraDashboardController::class, 'getSolicitudesPago'])->middleware(['audit']); // SP enlazadas

                // === RUTAS DIRECTAS CON CONTEXTO DE PROVEEDOR ===
                Route::get('/id/{ordenCompra}', [ProveedorOrdenCompraDashboardController::class, 'showDirecto'])->middleware(['audit']); // Detalle OC por ID
                Route::get('/id/{ordenCompra}/solicitudes-pago', [ProveedorOrdenCompraDashboardController::class, 'getSolicitudesPagoDirecto'])->middleware(['audit']); // SP de OC por ID
                Route::get('/general', [ProveedorOrdenCompraDashboardController::class, 'indexGeneral'])->middleware(['audit']); // Listado general
            });

        /**
         * CONVERSIÓN DE ÓRDENES DE COMPRA A SOLICITUDES DE PAGO
         */
        Route::prefix('{proveedor}/ordenes-compra-sp')
            ->middleware(['proveedor.access'])
            ->group(function () {

                // === RUTAS DE CONVERSIÓN ===
                Route::post('/convert', [OrdenCompraSolicitudPagoController::class, 'store'])->middleware(['audit']); // Crear SP desde OC
                Route::post('/validate', [OrdenCompraSolicitudPagoController::class, 'validateConversion'])->middleware(['audit']); // Pre-validar conversión
                Route::get('/preview', [OrdenCompraSolicitudPagoController::class, 'getConversionPreview'])->middleware(['audit']); // Preview (datos pre-llenados)
                Route::delete('/unlink', [OrdenCompraSolicitudPagoController::class, 'unlinkSolicitudPago'])->middleware(['audit']); // Desasociar SP de OC

                // === RUTAS DE CONSULTA / MÉTRICAS ===
                Route::get('/{ordenCompra}/history', [OrdenCompraSolicitudPagoController::class, 'getConversionHistory'])->middleware(['audit']); // Historial de conversiones
                Route::get('/metricas', [OrdenCompraSolicitudPagoController::class, 'getMetricasConversion'])->middleware(['audit']); // Métricas de conversión
                Route::get('/recientes', [OrdenCompraSolicitudPagoController::class, 'getConversionesRecientes'])->middleware(['audit']); // Conversiones recientes
            });


        /**
         * GESTIÓN DE EMPRESAS DE CONSTRUCCIÓN
         */
        /**
         * GESTIÓN DE EMPRESAS DE CONSTRUCCIÓN
         */
        Route::prefix('{proveedor}/empresas-constructoras')
            ->middleware(['proveedor.access'])
            ->group(function () {

                // 🔍 Buscar empresas (por nombre, razón social o RFC)
                Route::get('/search', [EmpresaConstruccController::class, 'search'])->middleware(['audit']);

                // ✅ NUEVA RUTA: Obtener todas las empresas (sin paginación)
                Route::get('/all', [EmpresaConstruccController::class, 'all'])->middleware(['audit']);

                // 📋 Listado paginado de empresas
                Route::get('/', [EmpresaConstruccController::class, 'index'])->middleware(['audit']);

                // 🆕 Crear empresa y asociar a proveedor
                Route::post('/', [EmpresaConstruccController::class, 'store'])->middleware(['audit']);

                // 📝 Obtener detalle de una empresa
                Route::get('/{empresaConstrucc}', [EmpresaConstruccController::class, 'show'])->middleware(['audit']);
                
                // 👥 Obtener usuarios de una empresa
                Route::get('/{empresaConstrucc}/usuarios', [EmpresaConstruccController::class, 'usuarios'])->middleware(['audit']);

                // ✏️ Actualizar empresa existente
                Route::put('/{empresaConstrucc}', [EmpresaConstruccController::class, 'update'])->middleware(['audit']);

                // ❌ Desasociar o desactivar empresa
                Route::delete('/{empresaConstrucc}', [EmpresaConstruccController::class, 'destroy'])->middleware(['audit']);
            });
    });



/**
 * PEDIDOS DEL PROVEEDOR
 */
        // Route::prefix('{proveedor}/pedidos')->middleware(['proveedor.access'])->group(function () {
        //     Route::get('dashboard', [ProveedorPedidoController::class, 'dashboard'])->middleware(['audit'])->name('gerente.proveedor.pedidos.dashboard');
        //     Route::get('/', [ProveedorPedidoController::class, 'index'])->middleware(['audit'])->name('gerente.proveedor.pedidos.index');
        //     Route::get('{pedido}', [ProveedorPedidoController::class, 'show'])->middleware(['audit'])->name('gerente.proveedor.pedidos.show');
        //     Route::patch('{pedido}/status', [ProveedorPedidoController::class, 'updateStatus'])->middleware(['audit'])->name('gerente.proveedor.pedidos.update-status');
        //     Route::patch('{pedido}/prepare-shipment', [ProveedorPedidoController::class, 'prepareShipment'])->middleware(['audit'])->name('gerente.proveedor.pedidos.prepare-shipment');
        //     Route::patch('{pedido}/confirm-delivery', [ProveedorPedidoController::class, 'confirmDelivery'])->middleware(['audit'])->name('gerente.proveedor.pedidos.confirm-delivery');
        //     Route::patch('{pedido}/reject', [ProveedorPedidoController::class, 'rechazar'])->middleware(['audit'])->name('gerente.proveedor.pedidos.reject');
        //     Route::post('export', [ProveedorPedidoController::class, 'exportar'])->middleware(['audit'])->name('gerente.proveedor.pedidos.export');
        // });
