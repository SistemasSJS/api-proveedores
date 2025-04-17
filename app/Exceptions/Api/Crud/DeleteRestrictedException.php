<?php

namespace App\Exceptions\Api\Crud;

use App\Exceptions\Api\BaseApiException;

class DeleteRestrictedException extends BaseApiException
{
    protected string $errorType = 'delete_restricted';
    protected int $statusCode = 403;

    public function __construct(string $message = 'Este recurso no puede eliminarse por restricciones.')
    {
        parent::__construct($message);
    }
}