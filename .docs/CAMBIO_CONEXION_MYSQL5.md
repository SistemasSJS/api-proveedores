# 🔄 Cambios para Forzar Conexión mysql5 en nuevaOrden()

## 📋 Objetivo

Todas las consultas del método `nuevaOrden()` deben usar la conexión **mysql5** explícitamente.

## ✅ Cambios Realizados

### **1. Consulta de Proveedor**

**Antes:**
```php
$proveedor = Proveedor::findOrFail($validated['proveedor_id']);
```

**Después:**
```php
$proveedor = Proveedor::on('mysql5')->findOrFail($validated['proveedor_id']);
```

### **2. Usuario Principal**

**Después de obtenerlo:**
```php
$usuarioPrincipal = $proveedor->usuarioPrincipal();

// Forzar conexión mysql5
$usuarioPrincipal->setConnection('mysql5');
```

### **3. Consulta de Notificaciones (Conteo Antes)**

**Antes:**
```php
$notificationsBefore = DB::table('notifications')
    ->where('notifiable_id', $usuarioPrincipal->id)
    ->count();
```

**Después:**
```php
$notificationsBefore = DB::connection('mysql5')->table('notifications')
    ->where('notifiable_id', $usuarioPrincipal->id)
    ->count();
```

### **4. Consulta de Notificaciones (Conteo Después)**

**Antes:**
```php
$notificationsAfter = DB::table('notifications')
    ->where('notifiable_id', $usuarioPrincipal->id)
    ->count();
```

**Después:**
```php
$notificationsAfter = DB::connection('mysql5')->table('notifications')
    ->where('notifiable_id', $usuarioPrincipal->id)
    ->count();
```

### **5. Última Notificación**

**Antes:**
```php
$lastNotification = DB::table('notifications')
    ->where('notifiable_id', $usuarioPrincipal->id)
    ->orderBy('created_at', 'desc')
    ->first();
```

**Después:**
```php
$lastNotification = DB::connection('mysql5')->table('notifications')
    ->where('notifiable_id', $usuarioPrincipal->id)
    ->orderBy('created_at', 'desc')
    ->first();
```

### **6. Inserción en oc_construcc**

**✅ Ya estaba correcto:**
```php
$inserted = DB::connection('mysql5')->table('oc_construcc')->insert([...]);
```

## ⚠️ Consideración Importante

### **Laravel Notifications y Conexión**

Cuando ejecutas:
```php
$usuarioPrincipal->notify(new NuevaOrdenCompra(...));
```

Laravel **usa la conexión del modelo** `$usuarioPrincipal`. Por eso es crítico hacer:
```php
$usuarioPrincipal->setConnection('mysql5');
```

**Esto garantiza que:**
1. ✅ La notificación se guarda en `notifications` de **mysql5**
2. ✅ El broadcast usa los datos de **mysql5**
3. ✅ Las relaciones del usuario usan **mysql5**

## 📊 Logs Actualizados

Los logs ahora muestran la conexión usada:

```log
[2025-10-24] INFO: 🔍 Buscando proveedor ID: 1 (mysql5)
[2025-10-24] INFO: ✅ Proveedor encontrado: {"id":1,"nombre":"Proveedor A","connection":"mysql5"}
[2025-10-24] INFO: 🔍 Buscando usuario principal del proveedor (mysql5)...
[2025-10-24] INFO: ✅ Usuario principal encontrado: {"id":5,"name":"Usuario X","email":"user@example.com","connection":"mysql5"}
[2025-10-24] INFO: 📊 Notificaciones antes (mysql5): 10
[2025-10-24] INFO: 📊 Notificaciones después (mysql5): 11
```

## 🔍 Verificación

### **Comprobar que todo usa mysql5:**

```php
// En el método nuevaOrden(), después de cada consulta:
Log::info('Conexión actual:', [
    'proveedor' => $proveedor->getConnectionName(),
    'usuario' => $usuarioPrincipal->getConnectionName(),
]);
```

### **Resultado esperado:**
```json
{
  "proveedor": "mysql5",
  "usuario": "mysql5"
}
```

## ✅ Checklist de Conexiones

- [x] `Proveedor::on('mysql5')` - Búsqueda de proveedor
- [x] `$usuarioPrincipal->setConnection('mysql5')` - Forzar conexión en usuario
- [x] `DB::connection('mysql5')->table('oc_construcc')` - Inserción OC
- [x] `DB::connection('mysql5')->table('notifications')` - Conteo antes
- [x] `DB::connection('mysql5')->table('notifications')` - Conteo después
- [x] `DB::connection('mysql5')->table('notifications')` - Última notificación

## 🎯 Resultado Final

**Todas las consultas en `nuevaOrden()` usan explícitamente `mysql5`:**

1. ✅ Proveedor
2. ✅ Usuario Principal
3. ✅ Tabla `oc_construcc`
4. ✅ Tabla `notifications` (conteos y consultas)
5. ✅ Laravel Notifications (a través de `setConnection`)

---

**Última actualización:** 2025-10-24
**Método afectado:** `NotificationController::nuevaOrden()`
