<?php

namespace App\Exceptions\Api\Shared;

use Illuminate\Support\MessageBag;
use Illuminate\Http\JsonResponse;
use App\Exceptions\Api\BaseApiException;


/**
 * @OA\Schema(
 *     schema="FormValidationException",
 *     title="Error de validación de formulario (422)",
 *     description="Se lanza cuando los datos enviados en el formulario no son válidos.",
 *     type="object",
 *     required={"message", "errorType", "errors"},
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         example="Datos de formulario inválidos."
 *     ),
 *     @OA\Property(
 *         property="errorType",
 *         type="string",
 *         example="validation_error"
 *     ),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         description="Errores específicos en el formulario",
 *         additionalProperties={
 *             @OA\Property(
 *                 type="array",
 *                 @OA\Items(type="string")
 *             )
 *         }
 *     )
 * )
 */
class FormValidationException extends BaseApiException
{
    protected string $errorType = 'validation_error';
    protected int $statusCode = 422;

    public function __construct(string $message = 'Datos de formulario inválidos.', MessageBag $errors)
    {
        parent::__construct($message);

        $this->additionalData = [
            'errors' => $errors->toArray()
        ];
    }
}
