<?php

namespace App\Http\Swagger;

/**
 * Anotaciones para endpoints adicionales
 * 
 * @OA\Get(
 *     path="/api/admin/proveedores",
 *     summary="Listar proveedores (Admin)",
 *     tags={"Administración"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Lista de proveedores")
 * )
 *
 * @OA\Get(
 *     path="/api/admin/pedidos",
 *     summary="Listar pedidos (Admin)",
 *     tags={"Administración"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Lista de pedidos")
 * )
 *
 * @OA\Get(
 *     path="/api/cliente/dashboard",
 *     summary="Dashboard del cliente",
 *     tags={"Dashboard"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Estadísticas del cliente")
 * )
 *
 * @OA\Get(
 *     path="/api/construcc/cotizaciones",
 *     summary="Listar cotizaciones (Construcc)",
 *     tags={"Cotizaciones"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Lista de cotizaciones")
 * )
 *
 * @OA\Post(
 *     path="/api/construcc/cotizaciones",
 *     summary="Crear cotización (Construcc)",
 *     tags={"Cotizaciones"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="requisicion_id", type="integer"),
 *             @OA\Property(property="detalles", type="array", @OA\Items(type="object"))
 *         )
 *     ),
 *     @OA\Response(response=201, description="Cotización creada")
 * )
 *
 * @OA\Get(
 *     path="/api/construcc/solicitudes-pago",
 *     summary="Listar solicitudes de pago (Construcc)",
 *     tags={"Órdenes de Compra"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Lista de solicitudes de pago")
 * )
 *
 * @OA\Post(
 *     path="/api/construcc/solicitudes-pago",
 *     summary="Crear solicitud de pago (Construcc)",
 *     tags={"Órdenes de Compra"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"orden_compra_id", "monto"},
 *             @OA\Property(property="orden_compra_id", type="integer"),
 *             @OA\Property(property="monto", type="number")
 *         )
 *     ),
 *     @OA\Response(response=201, description="Solicitud de pago creada")
 * )
 *
 * @OA\Post(
 *     path="/api/import/csv",
 *     summary="Importar datos desde CSV",
 *     tags={"Archivos"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 @OA\Property(property="file", type="string", format="binary"),
 *                 @OA\Property(property="tipo", type="string", example="productos")
 *             )
 *         )
 *     ),
 *     @OA\Response(response=200, description="Importación exitosa")
 * )
 *
 * @OA\Get(
 *     path="/api/empresas-construcc",
 *     summary="Listar empresas constructoras",
 *     tags={"Administración"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Lista de empresas constructoras")
 * )
 *
 * @OA\Post(
 *     path="/api/empresas-construcc",
 *     summary="Crear empresa constructora",
 *     tags={"Administración"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"nombre", "rfc"},
 *             @OA\Property(property="nombre", type="string"),
 *             @OA\Property(property="rfc", type="string"),
 *             @OA\Property(property="direccion", type="string"),
 *             @OA\Property(property="telefono", type="string")
 *         )
 *     ),
 *     @OA\Response(response=201, description="Empresa creada")
 * )
 *
 * @OA\Get(
 *     path="/api/ordenes-compra",
 *     summary="Listar órdenes de compra",
 *     tags={"Órdenes de Compra"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="estatus", in="query", required=false, @OA\Schema(type="string")),
 *     @OA\Parameter(name="proveedor_id", in="query", required=false, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Lista de órdenes de compra")
 * )
 *
 * @OA\Post(
 *     path="/api/ordenes-compra",
 *     summary="Crear orden de compra",
 *     tags={"Órdenes de Compra"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"proveedor_id", "empresa_id"},
 *             @OA\Property(property="proveedor_id", type="integer"),
 *             @OA\Property(property="empresa_id", type="integer"),
 *             @OA\Property(property="orden_compra_id", type="string"),
 *             @OA\Property(property="detalles", type="array", @OA\Items(type="object"))
 *         )
 *     ),
 *     @OA\Response(response=201, description="Orden de compra creada")
 * )
 *
 * @OA\Get(
 *     path="/api/ordenes-compra/{id}/registro",
 *     summary="Obtener registro de orden de compra",
 *     tags={"Órdenes de Compra"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Registro de la orden")
 * )
 *
 * @OA\Get(
 *     path="/api/ordenes-compra/{id}/solicitudes-pago",
 *     summary="Listar solicitudes de pago de una orden",
 *     tags={"Órdenes de Compra"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Solicitudes de pago")
 * )
 *
 * @OA\Get(
 *     path="/api/productos/buscar",
 *     summary="Buscar productos",
 *     tags={"Productos"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="q", in="query", required=false, @OA\Schema(type="string")),
 *     @OA\Parameter(name="categoria_id", in="query", required=false, @OA\Schema(type="integer")),
 *     @OA\Parameter(name="proveedor_id", in="query", required=false, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Resultados de búsqueda")
 * )
 *
 * @OA\Post(
 *     path="/api/productos/{id}/imagenes",
 *     summary="Subir imágenes de producto",
 *     tags={"Productos"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 @OA\Property(
 *                     property="imagenes[]",
 *                     type="array",
 *                     @OA\Items(type="string", format="binary")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(response=201, description="Imágenes subidas")
 * )
 *
 * @OA\Delete(
 *     path="/api/productos/{productoId}/imagenes/{imagenId}",
 *     summary="Eliminar imagen de producto",
 *     tags={"Productos"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="productoId", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Parameter(name="imagenId", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=204, description="Imagen eliminada")
 * )
 *
 * @OA\Get(
 *     path="/api/sucursales/{id}/productos",
 *     summary="Listar productos de una sucursal",
 *     tags={"Sucursales"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Productos de la sucursal")
 * )
 *
 * @OA\Post(
 *     path="/api/sucursales/{id}/productos",
 *     summary="Asignar producto a sucursal",
 *     tags={"Sucursales"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"producto_id", "stock"},
 *             @OA\Property(property="producto_id", type="integer"),
 *             @OA\Property(property="stock", type="integer")
 *         )
 *     ),
 *     @OA\Response(response=201, description="Producto asignado")
 * )
 *
 * @OA\Get(
 *     path="/api/tienda",
 *     summary="Listar productos disponibles en tienda",
 *     tags={"Productos"},
 *     @OA\Parameter(name="categoria_id", in="query", required=false, @OA\Schema(type="integer")),
 *     @OA\Parameter(name="proveedor_id", in="query", required=false, @OA\Schema(type="integer")),
 *     @OA\Parameter(name="marca_id", in="query", required=false, @OA\Schema(type="integer")),
 *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Catálogo de productos")
 * )
 *
 * @OA\Get(
 *     path="/api/tienda/producto/{id}",
 *     summary="Ver detalle de producto en tienda",
 *     tags={"Productos"},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Detalle del producto")
 * )
 */
class AdditionalEndpoints
{
    // Este archivo solo contiene anotaciones para Swagger
}
