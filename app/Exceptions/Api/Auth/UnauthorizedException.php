<?php

namespace App\Exceptions\Api\Auth;

use App\Exceptions\Api\BaseApiException;

class UnauthorizedException extends BaseApiException
{
    protected string $errorType = 'unauthorized';
    protected int $statusCode = 401;

    public function __construct(string $message = 'Acceso no autorizado.')
    {
        parent::__construct($message);
    }
}