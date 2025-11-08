# Uso de PushNotification

La clase `PushNotification` ha sido actualizada para funcionar de manera similar a las notificaciones de Solicitudes de Pago y Órdenes de Compra.

## Características

- ✅ Canal privado por usuario (`App.Models.User.{id}`)
- ✅ Soporte para FCM (Firebase Cloud Messaging)
- ✅ Soporte para WebSocket (Reverb/Broadcasting)
- ✅ Almacenamiento en base de datos
- ✅ Action URL para navegación
- ✅ Íconos automáticos según el tipo de notificación

## Constructor

```php
public function __construct(
    string $title,          // Título de la notificación
    string $message,        // Mensaje/cuerpo de la notificación
    string $type = 'info',  // Tipo: info, success, error, warning
    array $data = [],       // Datos adicionales personalizados
    ?string $actionUrl = null,  // URL para acción (ej: /ordenes-compra/123)
    ?int $userId = null     // ID del usuario para el canal privado
)
```

## Tipos de notificación

| Tipo | Ícono | Uso |
|------|-------|-----|
| `info` | 🔔 | Notificaciones informativas generales |
| `success` | ✅ | Acciones exitosas, confirmaciones |
| `error` / `danger` | ❌ | Errores, rechazos |
| `warning` | ⚠️ | Advertencias, alertas |

## Ejemplos de uso

### 1. Notificación básica

```php
use App\Notifications\PushNotification;

$user = User::find(1);

$user->notify(new PushNotification(
    title: 'Nueva actualización',
    message: 'Se ha actualizado tu perfil correctamente',
    type: 'success',
    userId: $user->id
));
```

### 2. Notificación con action URL

```php
$user->notify(new PushNotification(
    title: 'Nueva Orden de Compra',
    message: 'Tienes una nueva orden de compra #OC-2025-001',
    type: 'info',
    data: [
        'orden_compra_id' => 'OC-2025-001',
        'monto' => 15000.00,
    ],
    actionUrl: '/ordenes-compra/OC-2025-001',
    userId: $user->id
));
```

### 3. Notificación de error

```php
$user->notify(new PushNotification(
    title: 'Error en validación',
    message: 'No se pudo procesar tu solicitud. Por favor intenta nuevamente.',
    type: 'error',
    data: [
        'error_code' => 'VALIDATION_FAILED',
        'fields' => ['email', 'telefono'],
    ],
    actionUrl: '/perfil/editar',
    userId: $user->id
));
```

### 4. Notificación con datos personalizados

```php
$user->notify(new PushNotification(
    title: 'Recordatorio',
    message: 'Tu solicitud de pago está pendiente de revisión',
    type: 'warning',
    data: [
        'solicitud_id' => 123,
        'dias_pendiente' => 5,
        'prioridad' => 'alta',
    ],
    actionUrl: '/solicitudes-pago/123',
    userId: $user->id
));
```

### 5. Notificación a múltiples usuarios

```php
$usuarios = User::whereIn('id', [1, 2, 3])->get();

foreach ($usuarios as $usuario) {
    $usuario->notify(new PushNotification(
        title: 'Mantenimiento programado',
        message: 'El sistema estará en mantenimiento el próximo sábado',
        type: 'info',
        data: [
            'fecha_inicio' => '2025-11-10 00:00:00',
            'fecha_fin' => '2025-11-10 06:00:00',
        ],
        actionUrl: null,
        userId: $usuario->id
    ));
}
```

## Canales de notificación

La notificación se envía automáticamente por:

1. **Broadcast (WebSocket/Reverb)** - Canal privado `App.Models.User.{id}`
2. **Database** - Se guarda en la tabla `notifications`
3. **FCM** - Solo si el usuario tiene tokens de dispositivo activos

## Estructura de datos

### Broadcast/Database
```json
{
  "tipo": "success",
  "titulo": "Orden confirmada",
  "mensaje": "Tu orden #123 ha sido confirmada",
  "action_url": "/ordenes/123",
  "data": {
    "orden_id": 123,
    "monto": 5000
  },
  "timestamp": "2025-11-07T17:00:00.000Z"
}
```

### FCM
```json
{
  "notification": {
    "title": "✅ Orden confirmada",
    "body": "Tu orden #123 ha sido confirmada"
  },
  "data": {
    "orden_id": "123",
    "monto": "5000",
    "tipo": "success",
    "action_url": "/ordenes/123",
    "timestamp": "2025-11-07T17:00:00.000Z"
  }
}
```

## Migración desde la versión anterior

### Antes:
```php
$user->notify(new PushNotification('Título', 'Mensaje', 'info', ['key' => 'value']));
```

### Ahora (compatible):
```php
$user->notify(new PushNotification('Título', 'Mensaje', 'info', ['key' => 'value']));
```

### Ahora (recomendado):
```php
$user->notify(new PushNotification(
    title: 'Título',
    message: 'Mensaje',
    type: 'info',
    data: ['key' => 'value'],
    actionUrl: '/ruta/destino',
    userId: $user->id
));
```

## Notas importantes

- ⚠️ **Siempre pasa el `userId`** para garantizar que la notificación se envíe al canal privado correcto
- ⚠️ El `actionUrl` es opcional pero recomendado para mejorar la experiencia de usuario
- ⚠️ Los datos en FCM se convierten a strings automáticamente
- ✅ La notificación es compatible hacia atrás con código existente
