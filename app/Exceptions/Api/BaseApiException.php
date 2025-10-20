<?php

namespace App\Exceptions\Api;

use App\Exceptions\Api\Traits\TracksRequestData;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

abstract class BaseApiException extends Exception
{
    use ApiResponse, TracksRequestData;

    protected int $statusCode = 500;

    protected string $errorType = 'error';

    protected array $additionalData = [];

    public function __construct(string $message = '', int $code = 0)
    {
        $this->log();
        parent::__construct($message ?: 'Error en la API', $code);
    }

    protected function log(): void
    {
        Log::error("{$this->errorType} ({$this->statusCode}): ".$this->getMessage(), array_merge([
            'exception' => static::class,
            'code' => $this->getCode(),
            'trace' => $this->getTraceAsString(),
            'additional' => $this->additionalData,
        ], $this->requestContext()));
    }

    public function render(Request $request): JsonResponse
    {
        return $this->error(
            message: $this->getMessage(),
            errors: array_merge([
                'success' => false,
                'error_type' => $this->errorType,
                'message' => $this->getMessage(),
                'code' => $this->statusCode,
            ], $this->additionalData),
            code: $this->statusCode,
        );
    }
}
