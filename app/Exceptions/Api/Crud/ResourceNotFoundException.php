<?php

namespace App\Exceptions\Api\Crud;

use App\Exceptions\Api\BaseApiException;

class ResourceNotFoundException extends BaseApiException
{
    protected string $errorType = 'resource_not_found';
    protected int $statusCode = 404;

    public function __construct(string $message = 'Recurso no encontrado.')
    {
        parent::__construct($message);
    }
}