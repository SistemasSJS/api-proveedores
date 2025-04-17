<?php

namespace App\Exceptions\Api\Auth;

use Exception;
use App\Exceptions\Api\BaseApiException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegistrationException extends BaseApiException
{
    protected $errors;

    public function __construct($message = "Error en el registro", $errors = [])
    {
        $this->errors = $errors;
        parent::__construct($message, 422); // El 422 es para "Unprocessable Entity" (datos inválidos)
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'errors' => $this->getErrors(),
            'code' => $this->getCode()
        ], $this->getCode());
    }
}
