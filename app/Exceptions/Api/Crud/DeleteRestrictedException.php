<?php

namespace App\Exceptions\Api\Crud;

use App\Exceptions\Api\BaseApiException;

/**
 * @OA\Schema(
 *     schema="DeleteRestrictedException",
 *     title="Error por restricción de eliminación (403)",
 *     description="Se lanza cuando no se puede eliminar un recurso debido a restricciones.",
 *     type="object",
 *     required={"message", "errorType"},
 *
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         example="Este recurso no puede eliminarse por restricciones."
 *     ),
 *     @OA\Property(
 *         property="errorType",
 *         type="string",
 *         example="delete_restricted"
 *     )
 * )
 */
class DeleteRestrictedException extends BaseApiException
{
    protected string $errorType = 'delete_restricted';

    protected int $statusCode = 403;

    public function __construct(string $message = 'Este recurso no puede eliminarse por restricciones.')
    {
        parent::__construct($message);
    }
}
