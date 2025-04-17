<?php

namespace App\Exceptions\Api\Crud;

use App\Exceptions\Api\BaseApiException;

class ConflictException extends BaseApiException
{
    protected string $errorType = 'conflict';
    protected int $statusCode = 409;

    public function __construct(string $message = 'Conflicto con el estado actual del recurso.')
    {
        parent::__construct($message);
    }
}