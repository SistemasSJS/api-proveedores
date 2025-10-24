# Cambios en NotificationController - Notificar Solo Usuario Principal

## 📋 Problema Resuelto

**Antes:** Se notificaba a todos los usuarios activos del proveedor  
**Ahora:** Se notifica únicamente al usuario principal del proveedor

---

## ✅ Cambios Aplicados

### 1. Obtener Usuario Principal del Proveedor

**Antes:**
```php
$proveedor = Proveedor::with('usuariosActivos')->findOrFail($validated['proveedor_id']);
```

**Ahora:**
```php
$proveedor = Proveedor::findOrFail($validated['proveedor_id']);
$usuarioPrincipal = $proveedor->usuarioPrincipal();

// Validar que existe un usuario principal
if (!$usuarioPrincipal) {
    return response()->json([
        'success' => false,
        'message' => 'El proveedor no tiene un usuario principal asignado',
        'proveedor_id' => $validated['proveedor_id']
    ], 422);
}
```

---

### 2. Enviar Notificación Solo al Usuario Principal

**Antes:**
```php
foreach ($proveedor->usuariosActivos as $usuario) {
    try {
        $usuario->notify(new PushNotification(...));
    } catch (\Exception $e) {
        // Log error
    }
}
```

**Ahora:**
```php
try {
    $usuarioPrincipal->notify(new PushNotification(
        $notificacion->titulo,
        $notificacion->mensaje,
        'info',
        [
            'notificacion_id' => $notificacion->id,
            'num_orden' => $validated['num_orden'],
            'importe' => $validated['importe'],
            'tipo' => 'nueva_orden_compra'
        ]
    ));
    
    Log::channel('inter_api')->info('Notificación push enviada correctamente', [
        'usuario_id' => $usuarioPrincipal->id,
        'usuario_name' => $usuarioPrincipal->name,
        'notificacion_id' => $notificacion->id
    ]);
} catch (\Exception $e) {
    Log::channel('inter_api')->error('Error al enviar notificación push', [
        'usuario_id' => $usuarioPrincipal->id,
        'error' => $e->getMessage()
    ]);
}
```

---

### 3. Logs Actualizados

**Logs de creación de orden:**
```php
Log::channel('inter_api')->info('Orden de compra y notificación guardadas', [
    'orden_compra_id' => $ordenCompra->id,
    'notificacion_id' => $notificacion->id,
    'num_orden' => $validated['num_orden'],
    'proveedor_id' => $validated['proveedor_id'],
    'importe_total' => $validated['importe'],
    'usuario_notificado_id' => $usuarioPrincipal->id,      // ✅ Usuario principal
    'usuario_notificado_name' => $usuarioPrincipal->name   // ✅ Nombre del usuario
]);
```

---

## 🔍 Flujo Completo

```
1. API Construcciones envía orden de compra
   ↓
2. API Proveedores recibe la petición (método nuevaOrden)
   ↓
3. Valida proveedor_id en tabla proveedores (columna 'id')
   ↓
4. Obtiene el proveedor y su usuario principal
   ↓
5. Valida que existe usuario principal
   ↓
6. Guarda orden de compra en tabla ordenes_compra
   ↓
7. Guarda notificación en tabla notificaciones
   ↓
8. Broadcast evento NuevaOrdenCompraEvent (WebSocket)
   ↓
9. Envía notificación push al usuario principal
   ↓
10. Usuario principal recibe la notificación en tiempo real
```

---

## 📊 Modelo de Datos

### Usuario Principal del Proveedor

El método `usuarioPrincipal()` en el modelo `Proveedor` retorna:

```php
public function usuarioPrincipal()
{
    return $this->userProveedores()
        ->where('activo', true)
        ->where('tipo_relacion', 'PRINCIPAL')
        ->with('user')
        ->first()?->user;
}
```

**Requisitos:**
- ✅ Usuario activo (`activo = true`)
- ✅ Tipo de relación principal (`tipo_relacion = 'PRINCIPAL'`)

---

## ⚠️ Validaciones

### 1. Proveedor sin Usuario Principal

Si el proveedor no tiene un usuario principal asignado:

**Response:**
```json
{
  "success": false,
  "message": "El proveedor no tiene un usuario principal asignado",
  "proveedor_id": 1
}
```

**HTTP Status:** `422 Unprocessable Entity`

### 2. Error al Enviar Notificación

Si falla el envío de la notificación push:
- ✅ La orden se guarda correctamente
- ✅ La notificación se guarda correctamente
- ✅ El broadcast se envía correctamente
- ❌ Solo falla el push notification
- ✅ El error se registra en logs

---

## 🧪 Testing

### Test Manual con cURL

```bash
curl -X POST http://localhost:80/api/notificaciones/nueva-orden \
  -H "ApiKey: tu-apikey-compartida" \
  -H "Content-Type: application/json" \
  -d '{
    "num_orden": "OC-2025-001",
    "proveedor_id": 1,
    "fecha": "2025-10-24",
    "obra_id": 123,
    "empresa": 1,
    "usuario": 5,
    "tipo_orden": "Materiales",
    "requisicion_id": null,
    "tiene_requisicion": false,
    "subtotal": 10000.00,
    "iva": 1600.00,
    "tasa": 0.16,
    "importe": 11600.00,
    "estatus": "pendiente",
    "observaciones": "Entrega urgente"
  }'
```

### Verificar en Logs

```bash
tail -f storage/logs/inter-api.log
```

Deberías ver:
```
[2025-10-24] Orden de compra y notificación guardadas
  - orden_compra_id: 1
  - notificacion_id: 1
  - proveedor_id: 1
  - usuario_notificado_id: 3
  - usuario_notificado_name: "Juan Pérez"

[2025-10-24] Notificación push enviada correctamente
  - usuario_id: 3
  - usuario_name: "Juan Pérez"
  - notificacion_id: 1
```

---

## 🔧 Troubleshooting

### Problema: "El proveedor no tiene un usuario principal asignado"

**Solución 1:** Asignar un usuario principal al proveedor

```sql
-- Verificar usuarios del proveedor
SELECT * FROM user_proveedor WHERE proveedor_id = 1;

-- Actualizar un usuario como principal
UPDATE user_proveedor 
SET tipo_relacion = 'PRINCIPAL', activo = 1 
WHERE proveedor_id = 1 AND user_id = 3;
```

**Solución 2:** Crear relación usuario-proveedor

```php
// En tinker
$proveedor = Proveedor::find(1);
$usuario = User::find(3);

$proveedor->users()->attach($usuario->id, [
    'tipo_relacion' => 'PRINCIPAL',
    'activo' => true,
    'fecha_asignacion' => now(),
]);
```

### Problema: Notificación no llega al usuario

**Verificar:**

1. **Usuario principal existe:**
```php
$proveedor = Proveedor::find(1);
$usuario = $proveedor->usuarioPrincipal();
dd($usuario); // Debe retornar un User, no null
```

2. **Canal de broadcast:**
```php
// El usuario debe estar suscrito al canal
App.Models.User.{userId}
```

3. **Reverb corriendo:**
```bash
php artisan reverb:start
```

4. **Frontend conectado:**
- Verificar consola del navegador
- Debe mostrar "Suscrito al canal App.Models.User.3"

---

## 📝 Notas Finales

- ✅ Solo se notifica al usuario **principal** del proveedor
- ✅ El broadcast se envía al canal del **usuario**, no del proveedor
- ✅ La validación `exists:proveedores,id` verifica que existe el proveedor
- ✅ Los logs incluyen información del usuario notificado
- ✅ Se manejan errores sin afectar el guardado de la orden

---

**Fecha:** 2025-10-24  
**Versión:** 2.0  
**Status:** ✅ Cambios aplicados correctamente
