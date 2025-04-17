<?php

namespace App\Exceptions\Api\Crud;

use App\Exceptions\Api\BaseApiException;

class InvalidInputException extends BaseApiException
{
    protected string $errorType = 'invalid_input';
    protected int $statusCode = 422;

    public function __construct(string $message = 'Entrada inválida.')
    {
        parent::__construct($message);
    }
}
