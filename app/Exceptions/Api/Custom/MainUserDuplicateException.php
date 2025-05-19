<?php

namespace App\Exceptions\Api\Custom;

use App\Exceptions\Api\BaseApiException;


/**
 * @OA\Schema(
 *     schema="MainUserDuplicateException",
 *     title="Error por usuario principal duplicado (409)",
 *     description="Se lanza cuando se intenta asignar un usuario principal a un proveedor que ya tiene uno.",
 *    type="object",
 *    required={"message", "errorType"},
 *    @OA\Property(
 *        property="message",
 *        type="string",
 *        example="Usuario principal duplicado."
 *    ),
 *    @OA\Property(
 *        property="errorType",
 *        type="string",
 *        example="main_user_duplicate"
 *    )
 * )
 */
class MainUserDuplicateException extends BaseApiException
{
    protected string $errorType = 'main_user_duplicate';
    protected int $statusCode = 409;

    public function __construct(string $message = 'Usuario principal duplicado.')
    {
        parent::__construct($message);
    }
}
