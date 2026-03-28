<?php

namespace App\Traits;

use Illuminate\Support\Str;

/**
 * UUID estable por instancia de notificación para alinear Reverb + FCM + database
 * y evitar doble entrega en el cliente cuando ambos canales disparan el mismo evento.
 */
trait NotificationCorrelationId
{
    protected ?string $notificationCorrelationIdCache = null;

    protected function notificationCorrelationId(): string
    {
        if ($this->notificationCorrelationIdCache === null) {
            $this->notificationCorrelationIdCache = (string) Str::uuid();
        }

        return $this->notificationCorrelationIdCache;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function withNotificationCorrelationId(array $data): array
    {
        return array_merge($data, [
            'notification_correlation_id' => $this->notificationCorrelationId(),
        ]);
    }
}
