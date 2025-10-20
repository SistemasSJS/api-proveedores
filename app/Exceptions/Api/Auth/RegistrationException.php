<?php

namespace App\Exceptions\Api\Auth;

use App\Exceptions\Api\BaseApiException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *     schema="RegistrationException",
 *     title="Error en el registro (422)",
 *     description="Se lanza cuando hay un error en el proceso de registro.",
 *     type="object",
 *     required={"message", "errorType", "errors"},
 *
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         example="Error en el registro"
 *     ),
 *     @OA\Property(
 *         property="errorType",
 *         type="string",
 *         example="registration_error"
 *     ),
 *     @OA\Property(
 *         property="errors",
 *         type="array",
 *
 *         @OA\Items(type="string"),
 *         example={"El campo correo es obligatorio", "El campo contraseña debe tener al menos 8 caracteres"}
 *     )
 * )
 */
class RegistrationException extends BaseApiException
{
    protected $errors;

    public function __construct($message = 'Error en el registro', $errors = [])
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
            'code' => $this->getCode(),
        ], $this->getCode());
    }
}
