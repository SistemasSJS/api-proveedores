<?php

namespace App\Exceptions\Api\Custom;

use App\Exceptions\Api\BaseApiException;

/**
 * @OA\Schema(
 *     schema="NotFoundRelationException",
 *     title="Error de relación no encontrada (404)",
 *     description="Se lanza cuando no se encuentra la relación solicitada.",
 *     type="object",
 *     required={"message", "errorType"},
 *
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         example="Relación no encontrada."
 *     ),
 *     @OA\Property(
 *         property="errorType",
 *         type="string",
 *         example="not_found_relation"
 *     )
 * )
 */
class NotFoundRelationException extends BaseApiException
{
    protected string $errorType = 'not_found_relation';

    protected int $statusCode = 404;

    public function __construct(string $message = 'Relación no encontrada.')
    {
        parent::__construct($message);
    }
}
