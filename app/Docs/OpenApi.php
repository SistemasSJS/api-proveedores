<?php

namespace App\Docs;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="API de Proveedores",
 *     description="Documentación de la API de autenticación y gestión de proveedores.",
 *
 *     @OA\Contact(
 *         name="Soporte",
 *         email="soporte@tuempresa.com"
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Servidor local: SQLite"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
class OpenApi {}
