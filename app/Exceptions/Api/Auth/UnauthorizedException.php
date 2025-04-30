<?php

namespace App\Exceptions\Api\Auth;

use App\Exceptions\Api\BaseApiException;

/**
 * @OA\Schema(
 *     schema="UnauthorizedException",
 *     title="Acceso no autorizado (401)",
 *     description="Se lanza cuando el acceso está prohibido por no tener los permisos necesarios.",
 *     type="object",
 *     required={"message", "errorType"},
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         example="Acceso no autorizado."
 *     ),
 *     @OA\Property(
 *         property="errorType",
 *         type="string",
 *         example="unauthorized"
 *     )
 * )
 */
class UnauthorizedException extends BaseApiException
{
    protected string $errorType = 'unauthorized';
    protected int $statusCode = 401;

    public function __construct(string $message = 'Acceso no autorizado.')
    {
        parent::__construct($message);
    }
}
