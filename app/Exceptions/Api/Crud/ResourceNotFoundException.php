<?php

namespace App\Exceptions\Api\Crud;

use App\Exceptions\Api\BaseApiException;


/**
 * @OA\Schema(
 *     schema="ResourceNotFoundException",
 *     title="Recurso no encontrado (404)",
 *     description="Se lanza cuando el recurso solicitado no existe en el sistema.",
 *     type="object",
 *     required={"message", "errorType"},
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         example="Recurso no encontrado."
 *     ),
 *     @OA\Property(
 *         property="errorType",
 *         type="string",
 *         example="resource_not_found"
 *     )
 * )
 */
class ResourceNotFoundException extends BaseApiException
{
    protected string $errorType = 'resource_not_found';
    protected int $statusCode = 404;

    public function __construct(string $message = 'Recurso no encontrado.')
    {
        parent::__construct($message);
    }
}
