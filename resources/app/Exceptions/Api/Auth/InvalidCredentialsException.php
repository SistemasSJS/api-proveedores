<?php

namespace App\Exceptions\Api\Auth;

use App\Exceptions\Api\BaseApiException;

/**
 * @OA\Schema(
 *     schema="InvalidCredentialsException",
 *     title="Credenciales inválidas (401)",
 *     description="Se lanza cuando las credenciales del usuario no son válidas.",
 *     type="object",
 *     required={"message", "errorType"},
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         example="Credenciales inválidas."
 *     ),
 *     @OA\Property(
 *         property="errorType",
 *         type="string",
 *         example="invalid_credentials"
 *     )
 * )
 */
class InvalidCredentialsException extends BaseApiException
{
    protected string $errorType = 'invalid_credentials';
    protected int $statusCode = 401;

    public function __construct(string $message = 'Credenciales inválidas.')
    {
        parent::__construct($message);
    }
}
