# 📋 PASOS PARA COMPLETAR IMPLEMENTACIÓN DE NOTIFICACIONES

## ✅ YA COMPLETADO EN API-PROVEEDORES

1. ✅ Middleware `ValidateApiKey` creado y registrado
2. ✅ Modelo `Notificacion` creado
3. ✅ Migración de tabla `notificaciones` ejecutada
4. ✅ Evento `NuevaOrdenCompraEvent` creado
5. ✅ Controlador `NotificationController` actualizado con método `nuevaOrden()`
6. ✅ Rutas configuradas con middleware `apikey:api_construcciones`
7. ✅ Configuración en `config/services.php` lista

---

## 🔧 PASOS PENDIENTES

### **PASO 1: Configurar variables de entorno**

Edita tu archivo `.env` y agrega:

```env
# Intercomunicación con API Construcciones
API_CONSTRUCCIONES_URL=http://localhost:8091
API_CONSTRUCCIONES_APIKEY=clave_secreta_compartida_entre_apis
```

⚠️ **IMPORTANTE**: Usa la misma `APIKEY` en ambas APIs (construcciones y proveedores)

---

### **PASO 2: En API-CONSTRUCCIONES - Llamar a la API cuando se crea una orden**

Después de `$orden->save()`, agrega este código:

```php
// Después de guardar la orden
$orden->save();

// Notificar a API Proveedores
try {
    $response = Http::withHeaders([
        'X-API-KEY' => config('services.api_proveedores.apikey'),
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ])->post(config('services.api_proveedores.url') . '/api/notificaciones/nueva-orden', [
        'num_orden' => $orden->NumOrden,
        'proveedor_id' => $orden->ProvID,
        'fecha' => $orden->Fecha,
        'obra_id' => $orden->obraid,
        'empresa' => $orden->empresa,
        'usuario' => $orden->usuario,
        'tipo_orden' => $orden->tipo_orden,
        'requisicion_id' => $orden->requisicion_id,
        'tiene_requisicion' => (bool) $orden->tiene_requisicion,
        'subtotal' => $orden->Subtotal,
        'iva' => $orden->Iva,
        'tasa' => $orden->tasa,
        'importe' => $orden->importe,
        'estatus' => $orden->estatus,
        'observaciones' => $orden->observaciones,
    ]);

    if ($response->successful()) {
        Log::channel('inter_api')->info('Notificación enviada a API Proveedores', [
            'num_orden' => $orden->NumOrden,
            'proveedor_id' => $orden->ProvID
        ]);
    }
} catch (\Exception $e) {
    // No fallar la orden si falla la notificación
    Log::channel('inter_api')->error('Error al notificar a API Proveedores', [
        'num_orden' => $orden->NumOrden,
        'error' => $e->getMessage()
    ]);
}
```

---

### **PASO 3: En API-CONSTRUCCIONES - Configurar services.php**

En `config/services.php`:

```php
'api_proveedores' => [
    'url' => env('API_PROVEEDORES_URL', 'http://localhost:8000'),
    'apikey' => env('API_PROVEEDORES_APIKEY'),
],
```

---

### **PASO 4: En API-CONSTRUCCIONES - Variables de entorno**

En `.env` de API-CONSTRUCCIONES:

```env
API_PROVEEDORES_URL=http://localhost:8000
API_PROVEEDORES_APIKEY=clave_secreta_compartida_entre_apis
```

---

### **PASO 5: Configurar Broadcasting (Reverb o Pusher)**

Si aún no tienes configurado, en `.env`:

```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=tu_app_id
REVERB_APP_KEY=tu_app_key
REVERB_APP_SECRET=tu_app_secret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
```

Ejecuta Reverb:
```bash
php artisan reverb:start
```

---

## 🧪 PRUEBAS

### Test 1: Verificar que la ruta está protegida

```bash
# Sin ApiKey (debe fallar)
curl -X POST http://localhost:8000/api/notificaciones/nueva-orden \
  -H "Content-Type: application/json" \
  -d '{}'

# Respuesta esperada: 401 Unauthorized
```

### Test 2: Con ApiKey correcta

```bash
curl -X POST http://localhost:8000/api/notificaciones/nueva-orden \
  -H "X-API-KEY: clave_secreta_compartida_entre_apis" \
  -H "Content-Type: application/json" \
  -d '{
    "num_orden": "OC-2025-001",
    "proveedor_id": 1,
    "fecha": "2025-10-23",
    "obra_id": 5,
    "empresa": 1,
    "usuario": 10,
    "tipo_orden": "REQUISICION",
    "requisicion_id": 25,
    "tiene_requisicion": true,
    "subtotal": 10000.00,
    "iva": 1600.00,
    "tasa": 16,
    "importe": 11600.00,
    "estatus": "BO",
    "observaciones": "Entrega urgente"
  }'

# Respuesta esperada: 201 Created con la notificación
```

---

## 📊 VERIFICAR EN BASE DE DATOS

```sql
-- Ver notificaciones creadas
SELECT * FROM notificaciones ORDER BY created_at DESC LIMIT 5;

-- Ver notificaciones de un proveedor
SELECT * FROM notificaciones WHERE proveedor_id = 1;
```

---

## 🎯 ENDPOINTS DISPONIBLES

### Para API Construcciones (con ApiKey)
```
POST /api/notificaciones/nueva-orden
Header: X-API-KEY: tu_clave
```

### Para Frontend (con auth:sanctum)
```
GET    /api/notifications                    - Listar notificaciones
PATCH  /api/notifications/{id}/read          - Marcar como leída
PATCH  /api/notifications/mark-all-read      - Marcar todas como leídas
```

---

## 📡 CANAL DE BROADCASTING

Las notificaciones se transmiten por el canal:
```
proveedor.{proveedor_id}
```

Evento:
```
NuevaOrdenCompra
```

---

## ⚠️ TROUBLESHOOTING

### Error: "Invalid or missing ApiKey"
- Verifica que `X-API-KEY` esté en el header
- Verifica que la clave coincida en ambas APIs

### No se reciben broadcasts
- Verifica que Reverb esté corriendo: `php artisan reverb:start`
- Verifica BROADCAST_CONNECTION=reverb en .env

### Error al guardar notificación
- Verifica que el proveedor_id exista en la tabla proveedores
- Verifica que la migración se haya ejecutado correctamente

---

## 📝 LOGS

Los logs se guardan en:
```
storage/logs/inter_api.log
```

Para ver logs en tiempo real:
```bash
tail -f storage/logs/inter_api.log
```
