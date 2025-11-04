<?php

namespace App\Http\Swagger;

/**
 * Anotaciones genéricas para endpoints de Proveedor
 *
 * Nota: Los controladores individuales pueden tener anotaciones más específicas
 */
class ProveedorEndpoints
{
    /**
     * @OA\Get(
     *     path="/api/proveedor/categorias",
     *     summary="Listar categorías del proveedor",
     *     tags={"Proveedores"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Lista de categorías")
     * )
     *
     * @OA\Post(
     *     path="/api/proveedor/categorias",
     *     summary="Crear categoría del proveedor",
     *     tags={"Proveedores"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nombre"},
     *             @OA\Property(property="nombre", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Categoría creada")
     * )
     *
     * @OA\Get(
     *     path="/api/proveedor/productos",
     *     summary="Listar productos del proveedor",
     *     tags={"Proveedores"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="categoria_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Lista de productos")
     * )
     *
     * @OA\Post(
     *     path="/api/proveedor/productos",
     *     summary="Crear producto del proveedor",
     *     tags={"Proveedores"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nombre", "precio_unitario", "unidad_medida_id"},
     *             @OA\Property(property="nombre", type="string"),
     *             @OA\Property(property="descripcion", type="string", nullable=true),
     *             @OA\Property(property="precio_unitario", type="number"),
     *             @OA\Property(property="unidad_medida_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Producto creado")
     * )
     *
     * @OA\Get(
     *     path="/api/proveedor/sucursales",
     *     summary="Listar sucursales del proveedor",
     *     tags={"Proveedores"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Lista de sucursales")
     * )
     *
     * @OA\Post(
     *     path="/api/proveedor/sucursales",
     *     summary="Crear sucursal del proveedor",
     *     tags={"Proveedores"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nombre", "direccion"},
     *             @OA\Property(property="nombre", type="string"),
     *             @OA\Property(property="direccion", type="string"),
     *             @OA\Property(property="telefono", type="string"),
     *             @OA\Property(property="email", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Sucursal creada")
     * )
     *
     * @OA\Get(
     *     path="/api/proveedor/marcas",
     *     summary="Listar marcas del proveedor",
     *     tags={"Proveedores"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Lista de marcas")
     * )
     *
     * @OA\Post(
     *     path="/api/proveedor/marcas",
     *     summary="Crear marca del proveedor",
     *     tags={"Proveedores"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nombre"},
     *             @OA\Property(property="nombre", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Marca creada")
     * )
     *
     * @OA\Get(
     *     path="/api/proveedor/cotizaciones",
     *     summary="Listar cotizaciones del proveedor",
     *     tags={"Cotizaciones"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="estatus", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="fecha_desde", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="fecha_hasta", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Response(response=200, description="Lista de cotizaciones")
     * )
     *
     * @OA\Post(
     *     path="/api/proveedor/cotizaciones",
     *     summary="Crear cotización",
     *     tags={"Cotizaciones"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"requisicion_id", "detalles"},
     *             @OA\Property(property="requisicion_id", type="integer"),
     *             @OA\Property(
     *                 property="detalles",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="producto_id", type="integer"),
     *                     @OA\Property(property="cantidad", type="number"),
     *                     @OA\Property(property="precio_unitario", type="number")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Cotización creada")
     * )
     *
     * @OA\Get(
     *     path="/api/proveedor/pedidos",
     *     summary="Listar pedidos recibidos por el proveedor",
     *     tags={"Pedidos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="estatus", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Lista de pedidos")
     * )
     *
     * @OA\Get(
     *     path="/api/proveedor/ordenes-compra",
     *     summary="Listar órdenes de compra del proveedor",
     *     tags={"Órdenes de Compra"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="estatus", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="empresa_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Lista de órdenes de compra")
     * )
     *
     * @OA\Get(
     *     path="/api/proveedor/dashboard",
     *     summary="Obtener dashboard del proveedor",
     *     tags={"Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Estadísticas del dashboard",
     *         @OA\JsonContent(
     *             @OA\Property(property="total_pedidos", type="integer"),
     *             @OA\Property(property="total_cotizaciones", type="integer"),
     *             @OA\Property(property="total_productos", type="integer"),
     *             @OA\Property(property="ventas_mes", type="number")
     *         )
     *     )
     * )
     *
     * @OA\Get(
     *     path="/api/proveedor/usuarios",
     *     summary="Listar usuarios del proveedor",
     *     tags={"Proveedores"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Lista de usuarios")
     * )
     *
     * @OA\Post(
     *     path="/api/proveedor/usuarios",
     *     summary="Crear usuario del proveedor",
     *     tags={"Proveedores"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", format="password")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Usuario creado")
     * )
     *
     * @OA\Get(
     *     path="/api/proveedor/cuentas-bancarias",
     *     summary="Listar cuentas bancarias del proveedor",
     *     tags={"Proveedores"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Lista de cuentas bancarias")
     * )
     *
     * @OA\Post(
     *     path="/api/proveedor/cuentas-bancarias",
     *     summary="Crear cuenta bancaria del proveedor",
     *     tags={"Proveedores"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"banco", "numero_cuenta", "tipo_cuenta"},
     *             @OA\Property(property="banco", type="string"),
     *             @OA\Property(property="numero_cuenta", type="string"),
     *             @OA\Property(property="tipo_cuenta", type="string"),
     *             @OA\Property(property="clabe_interbancaria", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Cuenta bancaria creada")
     * )
     */
    // Este archivo solo contiene anotaciones para Swagger
}
