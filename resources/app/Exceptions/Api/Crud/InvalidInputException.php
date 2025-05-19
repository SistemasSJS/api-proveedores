<?php

namespace App\Exceptions\Api\Crud;

use App\Exceptions\Api\BaseApiException;


/**
 * @OA\Schema(
 *     schema="InvalidInputException",
 *     title="Entrada inválida (422)",
 *     description="Se lanza cuando la entrada del usuario no cumple con los requisitos.",
 *     type="object",
 *     required={"message", "errorType"},
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         example="Entrada inválida."
 *     ),
 *     @OA\Property(
 *         property="errorType",
 *         type="string",
 *         example="invalid_input"
 *     )
 * )
 */
class InvalidInputException extends BaseApiException
{
    protected string $errorType = 'invalid_input';
    protected int $statusCode = 422;

    public function __construct(string $message = 'Entrada inválida.')
    {
        parent::__construct($message);
    }
}
