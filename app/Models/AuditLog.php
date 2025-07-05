<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Modelo para la tabla de auditoría de acciones de la API
 *
 * @property int $id
 * @property string $request_id UUID único para cada request
 * @property int|null $user_id ID del usuario que realizó la acción
 * @property string|null $user_email Email del usuario
 * @property string|null $user_role Rol del usuario
 * @property string $action Tipo de acción: CREATE, UPDATE, DELETE, etc.
 * @property string|null $resource Recurso afectado: proveedores, productos, etc.
 * @property int|null $resource_id ID del recurso afectado
 * @property int|null $proveedor_context ID del proveedor en contexto
 * @property string $method Método HTTP: GET, POST, PUT, DELETE
 * @property string $path Ruta del endpoint
 * @property string $url URL completa del request
 * @property string $ip_address Dirección IP del cliente
 * @property string|null $user_agent User agent del navegador/cliente
 * @property array|null $request_headers Headers del request (sanitizado)
 * @property array|null $request_body Body del request (sanitizado)
 * @property int $response_status Código de estado HTTP de la respuesta
 * @property array|null $response_headers Headers de la respuesta
 * @property string|null $response_body Body de la respuesta (solo para errores)
 * @property array|null $error_details Detalles específicos del error si aplica
 * @property float|null $processing_time_ms Tiempo de procesamiento en milisegundos
 * @property \Carbon\Carbon $timestamp Timestamp exacto de la acción
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AuditLog extends BaseModel
{
    protected $table = 'api_audit_logs';

    protected $fillable = [
        'request_id',
        'user_id',
        'user_email',
        'user_role',
        'action',
        'resource',
        'resource_id',
        'proveedor_context',
        'method',
        'path',
        'url',
        'ip_address',
        'user_agent',
        'request_headers',
        'request_body',
        'response_status',
        'response_headers',
        'response_body',
        'error_details',
        'processing_time_ms',
        'timestamp',
    ];

    protected $casts = [
        'request_headers' => 'array',
        'request_body' => 'array',
        'response_headers' => 'array',
        'error_details' => 'array',
        'processing_time_ms' => 'decimal:2',
        'timestamp' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $dates = [
        'timestamp',
        'created_at',
        'updated_at',
    ];

    /**
     * Boot del modelo para auto-generar UUID
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->request_id)) {
                $model->request_id = Str::uuid()->toString();
            }

            if (empty($model->timestamp)) {
                $model->timestamp = now();
            }
        });
    }

    /**
     * Relación con el usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relación con el proveedor en contexto
     */
    public function proveedorContext(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_context');
    }

    /**
     * Scope para filtrar por usuario
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope para filtrar por acción
     */
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope para filtrar por recurso
     */
    public function scopeByResource($query, $resource, $resourceId = null)
    {
        $query = $query->where('resource', $resource);

        if ($resourceId) {
            $query->where('resource_id', $resourceId);
        }

        return $query;
    }

    /**
     * Scope para filtrar por proveedor
     */
    public function scopeByProveedor($query, $proveedorId)
    {
        return $query->where('proveedor_context', $proveedorId);
    }

    /**
     * Scope para filtrar por fecha
     */
    public function scopeByDateRange($query, $startDate, $endDate = null)
    {
        $query->whereDate('created_at', '>=', $startDate);

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        return $query;
    }

    /**
     * Scope para errores únicamente
     */
    public function scopeErrorsOnly($query)
    {
        return $query->where('response_status', '>=', 400);
    }

    /**
     * Scope para acciones exitosas únicamente
     */
    public function scopeSuccessOnly($query)
    {
        return $query->where('response_status', '<', 400);
    }

    /**
     * Obtiene el nombre del usuario de forma amigable
     */
    public function getUserNameAttribute(): string
    {
        if ($this->user) {
            return $this->user->nombre ?? $this->user->name ?? 'Usuario';
        }

        return $this->user_email ?? 'Usuario Desconocido';
    }

    /**
     * Obtiene la descripción de la acción de forma amigable
     */
    public function getActionDescriptionAttribute(): string
    {
        $descriptions = [
            'CREATE' => 'creó',
            'UPDATE' => 'actualizó',
            'DELETE' => 'eliminó',
            'VIEW' => 'consultó',
            'LOGIN' => 'inició sesión',
            'LOGOUT' => 'cerró sesión',
            'FORCE_STATUS' => 'forzó cambio de estado',
            'SYNC_BILLING' => 'sincronizó con facturación',
            'GENERATE_INVOICE' => 'generó factura',
            'PAYMENT_CONFIRMED' => 'confirmó pago',
        ];

        return $descriptions[$this->action] ?? strtolower($this->action);
    }

    /**
     * Obtiene el estado de la respuesta de forma amigable
     */
    public function getStatusTypeAttribute(): string
    {
        if ($this->response_status >= 200 && $this->response_status < 300) {
            return 'success';
        } elseif ($this->response_status >= 400 && $this->response_status < 500) {
            return 'client_error';
        } elseif ($this->response_status >= 500) {
            return 'server_error';
        } else {
            return 'other';
        }
    }

    /**
     * Verifica si la acción fue exitosa
     */
    public function isSuccessful(): bool
    {
        return $this->response_status >= 200 && $this->response_status < 400;
    }

    /**
     * Verifica si hubo un error
     */
    public function hasError(): bool
    {
        return $this->response_status >= 400;
    }

    /**
     * Obtiene el mensaje de error si existe
     */
    public function getErrorMessage(): ?string
    {
        if ($this->error_details && isset($this->error_details['message'])) {
            return $this->error_details['message'];
        }

        return null;
    }

    /**
     * Método estático para crear un log de auditoría
     */
    public static function createLog(array $data): self
    {
        // Sanitizar datos sensibles
        $data['request_headers'] = self::sanitizeHeaders($data['request_headers'] ?? []);
        $data['request_body'] = self::sanitizeRequestBody($data['request_body'] ?? []);

        return self::create($data);
    }

    /**
     * Sanitiza headers removiendo información sensible
     */
    private static function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = [
            'authorization',
            'cookie',
            'x-api-key',
            'x-auth-token',
        ];

        foreach ($sensitiveHeaders as $header) {
            if (isset($headers[$header])) {
                $headers[$header] = '***HIDDEN***';
            }
        }

        return $headers;
    }

    /**
     * Sanitiza el body del request removiendo información sensible
     */
    private static function sanitizeRequestBody(array $body): array
    {
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'token',
            'api_key',
            'secret',
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($body[$field])) {
                $body[$field] = '***HIDDEN***';
            }
        }

        return $body;
    }
}
