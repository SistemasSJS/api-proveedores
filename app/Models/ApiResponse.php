<?php

namespace App\Models;

/**
 * @OA\Schema(
 *     schema="ApiResponse",
 *     type="object",
 *     required={"status", "code", "message"},
 *     @OA\Property(property="status", type="string", example="SUCCESS"),
 *     @OA\Property(property="code", type="integer", example=200),
 *     @OA\Property(property="message", type="string", example="Operación exitosa."),
 *     @OA\Property(property="data", type="object", nullable=true),
 *     @OA\Property(property="errors", type="object", nullable=true)
 * )
 */
class ApiResponse {}


// class ApiResponse {}
/**
 * @OA\Schema(
 *     schema="ApiPaginatedResponse",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/ApiResponse"),
 *         @OA\Schema(
 *             @OA\Property(
 *                 property="data",
 *                 type="object" 
 *             ),
 *             @OA\Property(
 *                 property="meta",
 *                 type="object",
 *                 @OA\Property(
 *                     property="pagination",
 *                     type="object",
 *                     @OA\Property(property="current_page", type="integer", example=1),
 *                     @OA\Property(property="from", type="integer", example=1),
 *                     @OA\Property(property="last_page", type="integer", example=5),
 *                     @OA\Property(property="per_page", type="integer", example=15),
 *                     @OA\Property(property="to", type="integer", example=15),
 *                     @OA\Property(property="total", type="integer", example=70),
 *                 )
 *             )
 *         )
 *     }
 * )
 */
class ApiPaginatedResponse {}



/**
 * @OA\Schema(
 *     schema="ApiErrorResponse",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/ApiResponse"),
 *         @OA\Schema(
 *             @OA\Property(property="status", type="string", example="ERROR"),
 *             @OA\Property(property="code", type="integer", example=400),
 *             @OA\Property(property="message", type="string", example="Ha ocurrido un error."),
 *             @OA\Property(property="errors", type="object", example={"email": {"Este campo es requerido"}})
 *         )
 *     }
 * )
 */
class ApiErrordResponse {}
