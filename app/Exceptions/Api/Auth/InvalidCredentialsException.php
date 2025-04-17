<?php

namespace App\Exceptions\Api\Auth;

use App\Exceptions\Api\BaseApiException;

class InvalidCredentialsException extends BaseApiException
{
    protected string $errorType = 'invalid_credentials';
    protected int $statusCode = 401;

    public function __construct(string $message = 'Credenciales inválidas.')
    {
        parent::__construct($message);
    }
}