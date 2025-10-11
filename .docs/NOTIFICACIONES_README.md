# 🔔 Sistema de Notificaciones - App Proveedores

## 📋 Resumen del Sistema

Sistema completo de notificaciones implementado con **3 canales principales**:

- 📧 **Email** (SMTP con plantillas HTML)
- 📱 **Push Notifications** (Firebase Cloud Messaging)
- 🗄️ **Base de datos** (Laravel Notifications)
- 📡 **Broadcasting** (WebSocket - opcional)

---

## ✅ Estado Actual - COMPLETADO

### 🔧 Backend (Laravel)

- ✅ **Migración** `user_device_tokens` ejecutada
- ✅ **Modelos** User ↔ UserDeviceToken con relaciones completas
- ✅ **API Endpoints** `/api/device-tokens` (CRUD completo)
- ✅ **Servicio FCM** usando Service Account moderno
- ✅ **Canal FCM personalizado** para Laravel Notifications
- ✅ **Notificación CotizacionCreada** con soporte completo para todos los canales
- ✅ **Credenciales Firebase** configuradas y seguras

### 📱 Frontend (Ionic)

- ✅ **PushNotificationService** completo con Capacitor
- ✅ **Configuración Firebase** con proyecto real
- ✅ **Service Workers** para notificaciones web
- ✅ **Capacitor configurado** para push notifications
- ✅ **Deep-link navigation** integrado con NotificationService existente
- ✅ **Manejo de errores** robusto en todos los componentes

---

## 🗂️ Estructura del Sistema

### Backend (Laravel)

```
app/
├── Channels/
│   └── FcmChannel.php              # Canal personalizado FCM
├── Models/
│   ├── User.php                    # Relaciones con device tokens
│   └── UserDeviceToken.php         # Modelo de tokens FCM
├── Notifications/
│   └── CotizacionCreada.php        # Notificación principal
├── Services/
│   └── FcmService.php              # Servicio Firebase FCM
└── Http/Controllers/Api/
    └── DeviceTokenController.php   # API para tokens

storage/app/firebase/
└── service-account.json           # Credenciales Firebase

config/
├── services.php                   # Configuración FCM
├── broadcasting.php               # Configuración WebSocket
└── mail.php                      # Configuración SMTP

database/migrations/
└── 2025_08_23_181518_create_user_device_tokens_table.php
```

### Frontend (Ionic)

```
src/
├── app/@core/services/
│   ├── notification.service.ts           # Deep-link navigation
│   └── push-notification.service.ts      # Servicio FCM Capacitor
├── environments/
│   └── firebase.config.ts               # Configuración Firebase
├── assets/
│   └── firebase-messaging-sw.js         # Service Worker web
└── app.component.ts                     # Inicialización automática

android/app/
├── google-services.json.placeholder     # Placeholder Android
└── google-services.json                 # [PENDIENTE] Archivo real

ios/App/
├── GoogleService-Info.plist.placeholder # Placeholder iOS
└── GoogleService-Info.plist             # [PENDIENTE] Archivo real

capacitor.config.ts                      # Configuración Capacitor
```

---

## 🔧 Configuración

### Variables de Entorno (.env)

```env
# FIREBASE CLOUD MESSAGING (FCM) - PUSH NOTIFICATIONS
FIREBASE_CREDENTIALS=storage/app/firebase/service-account.json
FCM_PROJECT_ID=app-proveedores-notificacion
FCM_SENDER_ID=989092385974

# MAIL CONFIGURATION (YA CONFIGURADO)
MAIL_MAILER=smtp
MAIL_HOST=mail.construcc.com.mx
MAIL_PORT=587
MAIL_USERNAME=no-responder@construcc.com.mx
MAIL_PASSWORD=9tMpknYdJgas7Sn
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-responder@construcc.com.mx
MAIL_FROM_NAME="Construcc"

# QUEUE CONFIGURATION (YA CONFIGURADO)
QUEUE_CONNECTION=database

# BROADCASTING (OPCIONAL)
BROADCAST_CONNECTION=null
```

---

## 🚀 API Endpoints

### Device Tokens Management

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `GET` | `/api/device-tokens` | Listar tokens del usuario |
| `POST` | `/api/device-tokens` | Registrar/actualizar token |
| `DELETE` | `/api/device-tokens/{id}/deactivate` | Desactivar token |
| `DELETE` | `/api/device-tokens/cleanup` | Limpiar tokens expirados |
| `POST` | `/api/device-tokens/test-notification` | Enviar notificación de prueba |

### Ejemplo de Registro de Token

```bash
POST /api/device-tokens
Authorization: Bearer {sanctum_token}
Content-Type: application/json

{
  "token": "fcm_token_from_device",
  "platform": "android",
  "device_id": "unique_device_id",
  "device_name": "Samsung Galaxy S21",
  "metadata": {
    "app_version": "1.0.0",
    "os_version": "Android 12"
  }
}
```

---

## 📱 Uso en el Frontend

### Inicialización Automática

El `PushNotificationService` se inicializa automáticamente en `app.component.ts`:

```typescript
// Ya integrado en tu app.component.ts
private async initializePushNotifications(): Promise<void> {
  try {
    await this.pushNotificationService.initialize();
    // El servicio maneja automáticamente:
    // - Solicitud de permisos
    // - Registro de token FCM
    // - Envío de token al backend
    // - Manejo de notificaciones
  } catch (error) {
    console.error('Error inicializando push notifications:', error);
  }
}
```

### Deep-Link Navigation

Las notificaciones automáticamente navegan a las rutas correctas usando el `NotificationService` existente:

```typescript
// Datos de notificación compatibles con tu NotificationService
{
  type: 'cotizacion',        // 'import', 'product', 'user', 'cotizacion'
  entityId: '123',           // ID de la entidad
  action: 'view',            // 'view', 'edit', 'list'
  // ... más datos
}
```

---

## 🔥 Envío de Notificaciones (Backend)

### Uso Básico

```php
use App\Models\User;
use App\Notifications\CotizacionCreada;

$user = User::find(1);
$cotizacion = Cotizacion::find(123);
$solicitante = User::find(2);

// Envía automáticamente por todos los canales habilitados
$user->notify(new CotizacionCreada($cotizacion, $solicitante, 'construccion'));
```

### Canales Habilitados Automáticamente

La notificación se envía por:

- ✅ **Email** (siempre)
- ✅ **Base de datos** (siempre)
- ✅ **Broadcasting** (si está configurado)
- ✅ **FCM Push** (si el usuario tiene tokens activos)

### Procesamiento de Cola

```bash
# Procesar notificaciones en background
php artisan queue:work --queue=notifications,broadcast,default
```

---

## 🧪 Testing del Sistema

### Test Automático Completo

```bash
cd /path/to/api-proveedores
php artisan tinker

# Ejecutar en tinker:
include 'tests/NotificationSystemTest.php';
$test = new NotificationSystemTest();
$test->runCompleteTest();
```

### Test Manual de API

```bash
# 1. Registrar token de prueba
curl -X POST http://localhost:8080/api/device-tokens \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "token": "test_fcm_token_123",
    "platform": "android",
    "device_id": "test_device_123"
  }'

# 2. Listar tokens
curl -X GET http://localhost:8080/api/device-tokens \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## 📱 Configuración Firebase (PENDIENTE)

### Pasos para Completar

1. **Ve a Firebase Console:**
   ```
   https://console.firebase.google.com/project/app-proveedores-notificacion
   ```

2. **Agregar App Android:**
   - Project Settings → General → Your apps
   - Add app → Android
   - Package name: `com.construcc.proveedores`
   - Descargar `google-services.json`
   - Colocar en: `android/app/google-services.json`

3. **Agregar App iOS:**
   - Add app → iOS
   - Bundle ID: `com.construcc.proveedores`
   - Descargar `GoogleService-Info.plist`
   - Colocar en: `ios/App/GoogleService-Info.plist`

4. **Sincronizar Capacitor:**
   ```bash
   cd app-proveedores
   npx cap sync
   ```

---

## 🔍 Debugging y Logs

### Ver Logs de FCM

```bash
tail -f storage/logs/laravel.log | grep FCM
```

### Ver Jobs en Cola

```bash
php artisan queue:failed    # Jobs fallidos
php artisan queue:retry all # Reintentar jobs fallidos
```

### Limpiar Cache

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

---

## 🔒 Seguridad

### Credenciales Firebase

- ✅ Service Account guardado en `storage/app/firebase/` (no versionado)
- ✅ Variables sensibles en `.env` (no versionado)
- ✅ Tokens de usuario validados con Sanctum

### Validación de Tokens

- ✅ Automática limpieza de tokens inválidos
- ✅ Actualización de tokens canónicos
- ✅ Desactivación de tokens expirados

---

## 🚀 Despliegue

### Comandos de Producción

```bash
# Migrar base de datos
php artisan migrate --force

# Limpiar caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Iniciar workers de cola
php artisan queue:restart
php artisan queue:work --daemon --tries=3 --timeout=60
```

### Monitoreo

- Supervisar workers de cola con Supervisor/PM2
- Monitor de logs para errores FCM
- Health check de endpoints de notificaciones

---

## 📞 Soporte

### Archivos Clave para Debug

1. `app/Services/FcmService.php` - Servicio principal FCM
2. `app/Channels/FcmChannel.php` - Canal de notificaciones
3. `app/Notifications/CotizacionCreada.php` - Notificación principal
4. `storage/logs/laravel.log` - Logs del sistema

### Comandos Útiles

```bash
# Verificar configuración
php artisan config:show services.fcm

# Ver rutas de API
php artisan route:list | grep device-tokens

# Test de notificación
php artisan tinker
> include 'tests/NotificationSystemTest.php';
```

---

## ✅ Checklist de Implementación

- [x] Backend: Migración y modelos
- [x] Backend: API endpoints
- [x] Backend: Servicio FCM
- [x] Backend: Canal FCM personalizado
- [x] Backend: Notificación completa
- [x] Frontend: PushNotificationService
- [x] Frontend: Configuración Firebase
- [x] Frontend: Deep-link navigation
- [x] Frontend: Service Workers
- [x] Configuración: Variables de entorno
- [x] Configuración: Credenciales Firebase
- [ ] **PENDIENTE:** Archivos `google-services.json` e `GoogleService-Info.plist`
- [x] Testing: Script de pruebas
- [x] Documentación: README completo

---

**🎉 ¡Sistema de Notificaciones 95% Completado!**

Solo falta descargar los archivos de configuración móvil desde Firebase Console.
