# Configuración de WebSockets en Laravel

## 📋 Resumen

Este sistema permite enviar notificaciones push en tiempo real a los usuarios conectados en la aplicación Angular/Ionic usando WebSockets con Soketi (compatible con Pusher).

## 🚀 Instalación Completada

✅ **Paquetes instalados:**
- `pusher/pusher-php-server` - Cliente PHP para Pusher/Soketi
- `soketi` (global) - Servidor WebSocket compatible con Pusher

## 🔧 Configuración

### Variables de Entorno (.env)
```env
BROADCAST_CONNECTION=pusher

PUSHER_APP_ID=app-id
PUSHER_APP_KEY=app-key
PUSHER_APP_SECRET=app-secret
PUSHER_HOST=127.0.0.1
PUSHER_PORT=80
PUSHER_SCHEME=http
PUSHER_APP_CLUSTER=mt1
```

### Archivos Configurados

1. **config/broadcasting.php** - Configuración de Pusher para usar Soketi local
2. **config/cors.php** - CORS habilitado para `/broadcasting/auth`
3. **routes/channels.php** - Canal privado configurado para usuarios
4. **soketi.json** - Configuración del servidor Soketi

## 📱 Uso

### 1. Iniciar el Servidor WebSocket

**Opción A: Con el script .bat (Windows)**
```bash
start-websocket.bat
```

**Opción B: Comando directo**
```bash
soketi start --config=soketi.json
```

El servidor estará escuchando en `http://localhost:80`

### 2. Enviar Notificaciones

#### Desde un Controlador
```php
use App\Models\User;
use App\Notifications\PushNotification;

// Buscar usuario
$user = User::find(3);

// Notificación simple
$user->notify(new PushNotification(
    'Título de la notificación',
    'Mensaje de la notificación',
    'success' // Tipo: success, warning, danger, info
));

// Notificación con deep-link
$user->notify(new PushNotification(
    'Nuevo Producto',
    'Se ha agregado un nuevo producto',
    'info',
    [
        'deepLink' => [
            'type' => 'product',
            'entityId' => 123,
            'action' => 'view'
        ]
    ]
));
```

#### Desde Artisan (Pruebas)
```bash
# Notificación por defecto al usuario ID 3
php artisan notification:test

# Especificar usuario
php artisan notification:test 5

# Personalizar mensaje y tipo
php artisan notification:test 3 --message="Mensaje personalizado" --type=success
```

## 🔍 Debugging

### Verificar Estado del Servidor

1. **Dashboard de Soketi**: http://localhost:80/metrics
2. **Logs de Soketi**: Visibles en la consola donde se ejecuta

### Verificar en Laravel

```bash
# Limpiar caché si hay problemas
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan optimize:clear

# Ver logs
tail -f storage/logs/laravel.log
```

### Probar Broadcasting

```php
// En tinker
php artisan tinker

$user = User::find(3);
$user->notify(new \App\Notifications\PushNotification('Test', 'Mensaje de prueba'));
```

## 📊 Monitoreo

### Soketi Metrics
Disponible en: `http://localhost:80/metrics`

Muestra:
- Conexiones activas
- Mensajes enviados
- Canales suscritos
- Estadísticas de rendimiento

## 🛠️ Troubleshooting

### Problema: "No se conecta el WebSocket"
**Solución:**
1. Verificar que Soketi esté corriendo (`start-websocket.bat`)
2. Verificar firewall no bloquee puerto 80
3. Revisar configuración en `.env`

### Problema: "Error de autenticación en canales privados"
**Solución:**
1. Verificar que el usuario esté autenticado en Laravel
2. Revisar el token JWT en los headers
3. Verificar CORS en `config/cors.php`

### Problema: "No llegan las notificaciones"
**Solución:**
1. Verificar en Soketi metrics si hay conexiones
2. Revisar logs de Laravel: `storage/logs/laravel.log`
3. Verificar que el canal sea correcto: `App.Models.User.{userId}`

## 📝 Estructura de una Notificación

```php
class PushNotification extends Notification implements ShouldBroadcast
{
    public function toBroadcast($notifiable)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'mensaje' => $this->message,
            'type' => $this->type,        // success, warning, danger, info
            'data' => $this->data,         // Datos adicionales (deep-links, etc)
            'timestamp' => now()->toIsoString(),
            'read_at' => null
        ];
    }
}
```

## 🔒 Seguridad

- Los canales privados requieren autenticación
- El token JWT se valida en cada petición a `/broadcasting/auth`
- Solo el usuario autenticado puede suscribirse a su propio canal
- En producción usar HTTPS/WSS

## 🚀 Comandos Útiles

```bash
# Iniciar WebSocket
start-websocket.bat

# Enviar notificación de prueba
php artisan notification:test 3

# Ver usuarios disponibles
php artisan tinker
>>> User::pluck('name', 'id');

# Limpiar todo el caché
php artisan cache:clear && php artisan config:clear && php artisan route:clear

# Ver logs en tiempo real
tail -f storage/logs/laravel.log
```

## 📦 Próximos Pasos

1. ✅ Configurar supervisor/pm2 para mantener Soketi corriendo en producción
2. ✅ Configurar SSL/TLS para conexiones seguras en producción
3. ✅ Implementar rate limiting para prevenir spam
4. ✅ Agregar métricas y monitoreo con Grafana/Prometheus

---

**Nota:** La aplicación Angular/Ionic ya está configurada para recibir estas notificaciones. Solo asegúrate de que el usuario esté autenticado y el WebSocket esté corriendo.
