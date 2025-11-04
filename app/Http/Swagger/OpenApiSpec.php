<?php

namespace App\Http\Swagger;

/**
 * @OA\Info(
 *     title="API Proveedores",
 *     version="1.0.0",
 *     description="API para la gestión de proveedores, productos, órdenes de compra y cotizaciones",
 *     @OA\Contact(
 *         email="soporte@api-proveedores.com"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://localhost:80",
 *     description="Servidor Local"
 * )
 *
 * @OA\Server(
 *     url="https://api-proveedores.com",
 *     description="Servidor Producción"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Ingresa el token Bearer obtenido al hacer login"
 * )
 *
 * @OA\Tag(
 *     name="Autenticación",
 *     description="Endpoints para autenticación y registro de usuarios"
 * )
 *
 * @OA\Tag(
 *     name="Proveedores",
 *     description="Gestión de proveedores"
 * )
 *
 * @OA\Tag(
 *     name="Órdenes de Compra",
 *     description="Gestión de órdenes de compra"
 * )
 *
 * @OA\Tag(
 *     name="Cotizaciones",
 *     description="Gestión de cotizaciones"
 * )
 *
 * @OA\Tag(
 *     name="Productos",
 *     description="Gestión de productos y catálogos"
 * )
 *
 * @OA\Tag(
 *     name="Categorías",
 *     description="Gestión de categorías"
 * )
 *
 * @OA\Tag(
 *     name="Marcas",
 *     description="Gestión de marcas"
 * )
 *
 * @OA\Tag(
 *     name="Pedidos",
 *     description="Gestión de pedidos"
 * )
 *
 * @OA\Tag(
 *     name="Usuarios",
 *     description="Gestión de usuarios"
 * )
 *
 * @OA\Tag(
 *     name="Sucursales",
 *     description="Gestión de sucursales"
 * )
 *
 * @OA\Tag(
 *     name="Dashboard",
 *     description="Información de dashboards y estadísticas"
 * )
 *
 * @OA\Tag(
 *     name="Administración",
 *     description="Endpoints de administración"
 * )
 *
 * @OA\Tag(
 *     name="Archivos",
 *     description="Subida y gestión de archivos"
 * )
 *
 * @OA\Tag(
 *     name="Notificaciones",
 *     description="Gestión de notificaciones push y tokens de dispositivos"
 * )
 */
class OpenApiSpec
{
    // Este archivo solo contiene anotaciones para Swagger
}
