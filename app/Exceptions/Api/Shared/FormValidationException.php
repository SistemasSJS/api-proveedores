<?php

namespace App\Exceptions\Api\Shared;

use Illuminate\Support\MessageBag;
use Illuminate\Http\JsonResponse;
use App\Exceptions\Api\BaseApiException;

class FormValidationException extends BaseApiException
{
    protected string $errorType = 'validation_error';
    protected int $statusCode = 422;

    public function __construct(string $message = 'Datos de formulario inválidos.', MessageBag $errors)
    {
        parent::__construct($message);

        $this->additionalData = [
            'errors' => $errors
        ];
    }
}
