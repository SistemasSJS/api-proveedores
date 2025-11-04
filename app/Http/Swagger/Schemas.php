<?php

namespace App\Http\Swagger;

/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     title="Usuario",
 *     description="Modelo de Usuario",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Juan Pérez"),
 *     @OA\Property(property="email", type="string", format="email", example="juan@example.com"),
 *     @OA\Property(property="foto_perfil_url", type="string", nullable=true, example="https://example.com/avatar.jpg"),
 *     @OA\Property(property="role_id", type="integer", example=2),
 *     @OA\Property(property="status", type="string", example="activo"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Proveedor",
 *     type="object",
 *     title="Proveedor",
 *     description="Modelo de Proveedor",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nombre_comercial", type="string", example="Constructora XYZ"),
 *     @OA\Property(property="razon_social", type="string", example="Constructora XYZ S.A. de C.V."),
 *     @OA\Property(property="rfc", type="string", example="CXY123456789"),
 *     @OA\Property(property="email", type="string", format="email", example="contacto@xyz.com"),
 *     @OA\Property(property="telefono", type="string", example="5512345678"),
 *     @OA\Property(property="direccion_fiscal", type="string", example="Av. Principal #123"),
 *     @OA\Property(property="estado", type="string", example="Ciudad de México"),
 *     @OA\Property(property="municipio", type="string", example="Benito Juárez"),
 *     @OA\Property(property="codigo_postal", type="string", example="03100"),
 *     @OA\Property(property="estatus", type="string", example="activo"),
 *     @OA\Property(property="pagina_web", type="string", nullable=true, example="https://www.xyz.com"),
 *     @OA\Property(property="logo", type="string", nullable=true, example="https://example.com/logo.png"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="OrdenCompra",
 *     type="object",
 *     title="Orden de Compra",
 *     description="Modelo de Orden de Compra",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="orden_compra_id", type="string", example="OC-2025-001"),
 *     @OA\Property(property="empresa_id", type="integer", example=1),
 *     @OA\Property(property="proveedor_id", type="integer", example=5),
 *     @OA\Property(property="estatus", type="string", example="pendiente", enum={"pendiente", "aprobada", "rechazada", "en_proceso", "completada", "cancelada"}),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(
 *         property="proveedor",
 *         ref="#/components/schemas/Proveedor"
 *     ),
 *     @OA\Property(
 *         property="empresa",
 *         type="object",
 *         @OA\Property(property="id", type="integer"),
 *         @OA\Property(property="nombre", type="string")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="Categoria",
 *     type="object",
 *     title="Categoría",
 *     description="Modelo de Categoría",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nombre", type="string", example="Materiales de Construcción"),
 *     @OA\Property(property="descripcion", type="string", nullable=true, example="Categoría para materiales de construcción"),
 *     @OA\Property(property="proveedor_id", type="integer", example=1),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="LoginRequest",
 *     type="object",
 *     title="Login Request",
 *     required={"email", "password"},
 *     @OA\Property(property="email", type="string", format="email", example="usuario@example.com"),
 *     @OA\Property(property="password", type="string", format="password", example="password123")
 * )
 *
 * @OA\Schema(
 *     schema="LoginResponse",
 *     type="object",
 *     title="Login Response",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Login exitoso."),
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="user", ref="#/components/schemas/User"),
 *         @OA\Property(property="token", type="string", example="1|abc123def456...")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *     title="Error Response",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Error al procesar la solicitud"),
 *     @OA\Property(property="errors", type="object", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="SuccessResponse",
 *     type="object",
 *     title="Success Response",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Operación exitosa"),
 *     @OA\Property(property="data", type="object")
 * )
 *
 * @OA\Schema(
 *     schema="PaginationMeta",
 *     type="object",
 *     title="Pagination Metadata",
 *     @OA\Property(property="current_page", type="integer", example=1),
 *     @OA\Property(property="last_page", type="integer", example=10),
 *     @OA\Property(property="per_page", type="integer", example=15),
 *     @OA\Property(property="total", type="integer", example=150),
 *     @OA\Property(property="from", type="integer", example=1),
 *     @OA\Property(property="to", type="integer", example=15)
 * )
 */
class Schemas
{
    // Este archivo solo contiene schemas para Swagger
}
