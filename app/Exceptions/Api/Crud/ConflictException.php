<?php

namespace App\Exceptions\Api\Crud;

use App\Exceptions\Api\BaseApiException;

/**
 * @OA\Schema(
 *     schema="ConflictException",
 *     title="Error de Conflicto (409)",
 *     description="Se lanza cuando hay un conflicto con el estado actual del recurso.",
 *     type="object",
 *     required={"message", "errorType"},
 *
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         example="Conflicto con el estado actual del recurso."
 *     ),
 *     @OA\Property(
 *         property="errorType",
 *         type="string",
 *         example="conflict"
 *     )
 * )
 */
class ConflictException extends BaseApiException
{
    protected string $errorType = 'conflict';

    protected int $statusCode = 409;

    public function __construct(string $message = 'Conflicto con el estado actual del recurso.')
    {
        parent::__construct($message);
    }
}
