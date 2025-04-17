<?php

namespace App\Exceptions\Api\Auth;

use App\Exceptions\Api\BaseApiException;

class AccountDisabledException extends BaseApiException
{
    protected string $errorType = 'account_disabled';
    protected int $statusCode = 403;

    public function __construct(string $message = 'La cuenta está deshabilitada.')
    {
        parent::__construct($message);
    }
}