<?php

namespace App\Exceptions\Api\Auth;

use App\Exceptions\Api\BaseApiException;

/**
 * @OA\Schema(
 *     schema="AccountDisabledException",
 *     title="Cuenta deshabilitada (403)",
 *     description="Se lanza cuando una cuenta está deshabilitada.",
 *     type="object",
 *     required={"message", "errorType"},
 *
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         example="La cuenta está deshabilitada."
 *     ),
 *     @OA\Property(
 *         property="errorType",
 *         type="string",
 *         example="account_disabled"
 *     )
 * )
 */
class AccountDisabledException extends BaseApiException
{
    protected string $errorType = 'account_disabled';

    protected int $statusCode = 403;

    public function __construct(string $message = 'La cuenta está deshabilitada.')
    {
        parent::__construct($message);
    }
}
