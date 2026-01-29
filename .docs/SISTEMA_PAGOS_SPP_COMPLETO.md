# Sistema de Pagos para Solicitudes de Pago (SPP) - Implementación Completa

**Fecha de implementación:** 28 de Enero de 2026

## Resumen Ejecutivo

Se ha implementado un sistema completo de gestión de pagos para Solicitudes de Pago (SPP) con las siguientes características:

✅ **Relación Muchos a Muchos:** Un pago puede aplicarse a varias SPP y una SPP puede recibir varios pagos
✅ **Comprobante Único:** Cada pago tiene su comprobante obligatorio y único  
✅ **Datos Bancarios Completos:** Información tanto del pago (origen) como del proveedor (destino)
✅ **Estados Granulares:** Sistema de estados para cada relación pago-SPP
✅ **Actualización Automática:** Los saldos de las SPP se actualizan automáticamente
✅ **API REST Completa:** 10 endpoints para gestión total del sistema

---

## Estructura de Base de Datos

### Tabla: `pagos_spp`

Almacena la información de los pagos realizados a proveedores.

```sql
CREATE TABLE `pagos_spp` (
    `id` BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    
    -- Comprobante y fechas
    `comprobante_pago` VARCHAR(500) UNIQUE NOT NULL,
    `fecha_pago` TIMESTAMP NOT NULL,
    `fecha_registro` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Referencia
    `referencia_pago` VARCHAR(100) NOT NULL,
    
    -- Datos bancarios origen (quien paga)
    `banco_pago` VARCHAR(100) NULL,
    `cuenta_origen` VARCHAR(50) NULL,
    `tipo_cuenta_origen` VARCHAR(50) NULL,
    `clabe_interbancaria_origen` VARCHAR(18) NULL,
    
    -- Datos bancarios destino (proveedor)
    `banco_destino` VARCHAR(100) NULL,
    `cuenta_destino` VARCHAR(50) NULL,
    `tipo_cuenta_destino` VARCHAR(50) NULL,
    `clabe_interbancaria_destino` VARCHAR(18) NULL,
    `titular_cuenta_destino` VARCHAR(255) NULL,
    
    -- Monto
    `monto_total` DECIMAL(15,2) NOT NULL,
    
    -- Metadatos
    `observaciones` TEXT NULL,
    `usuario_registro_id` BIGINT UNSIGNED NULL,
    `usuario_registro_nombre` VARCHAR(255) NULL,
    `empresa_construcc_id` BIGINT UNSIGNED NULL,
    
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    
    INDEX (`fecha_pago`),
    INDEX (`fecha_registro`),
    INDEX (`referencia_pago`),
    INDEX (`empresa_construcc_id`)
);
```

### Tabla: `pago_solicitud_pago` (Pivot/Intermedia)

Tabla intermedia para la relación muchos a muchos.

```sql
CREATE TABLE `pago_solicitud_pago` (
    `id` BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    
    -- Llaves foráneas
    `pago_spp_id` BIGINT UNSIGNED NOT NULL,
    `solicitud_pago_id` BIGINT UNSIGNED NOT NULL,
    
    -- Datos de la relación
    `monto_aplicado` DECIMAL(15,2) NOT NULL,
    `estado_pago` ENUM('aplicado', 'pendiente', 'rechazado', 'parcial', 'completado') DEFAULT 'aplicado',
    `notas` TEXT NULL,
    `fecha_aplicacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    
    FOREIGN KEY (`pago_spp_id`) REFERENCES `pagos_spp`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`solicitud_pago_id`) REFERENCES `solicitudes_pago`(`id`) ON DELETE CASCADE,
    
    UNIQUE KEY `pago_spp_unique` (`pago_spp_id`, `solicitud_pago_id`),
    INDEX (`estado_pago`),
    INDEX (`fecha_aplicacion`)
);
```

---

## Modelos Implementados

### 1. PagoSPP (`app/Models/PagoSPP.php`)

**Responsabilidades:**
- Gestionar pagos realizados a proveedores
- Relación muchos a muchos con SolicitudPago
- Métodos de negocio para cálculos de montos

**Propiedades:**
- `$connection = 'mysql5'`
- `$table = 'pagos_spp'`

**Relaciones:**
- `solicitudesPago()`: BelongsToMany - Solicitudes de pago asociadas
- `empresaConstrucc()`: BelongsTo - Empresa que realiza el pago

**Scopes:**
- `deEmpresa($empresaId)`: Filtrar por empresa
- `entreFechas($inicio, $fin)`: Filtrar por rango de fechas
- `porReferencia($referencia)`: Filtrar por referencia

**Métodos de Negocio:**
- `montoTotalAplicado()`: Calcula el monto total aplicado a SPPs
- `montoDisponible()`: Calcula el monto disponible del pago
- `estaCompletamenteAplicado()`: Verifica si está totalmente aplicado
- `aplicarASolicitudPago()`: Aplica el pago a una SPP
- `obtenerSolicitudesConDetalle()`: Obtiene SPPs con datos del pivot

**Filtros Disponibles:**
- `search`: Búsqueda global
- `referencia_pago`: Por referencia
- `fecha_pago`, `fecha_pago_desde`, `fecha_pago_hasta`: Por fecha de pago
- `fecha_registro`, `fecha_registro_desde`, `fecha_registro_hasta`: Por fecha de registro
- `banco_pago`, `banco_destino`: Por bancos
- `empresa_construcc_id`: Por empresa
- `usuario_registro_id`: Por usuario
- `monto_min`, `monto_max`: Por rango de montos

### 2. PagoSolicitudPago (`app/Models/PagoSolicitudPago.php`)

**Responsabilidades:**
- Modelo pivot para la relación muchos a muchos
- Gestionar estados de la relación pago-SPP

**Constantes de Estados:**
```php
const ESTADO_APLICADO = 'aplicado';
const ESTADO_PENDIENTE = 'pendiente';
const ESTADO_RECHAZADO = 'rechazado';
const ESTADO_PARCIAL = 'parcial';
const ESTADO_COMPLETADO = 'completado';
```

**Relaciones:**
- `pagoSPP()`: BelongsTo - El pago
- `solicitudPago()`: BelongsTo - La solicitud de pago

**Scopes:**
- `porEstado($estado)`: Filtrar por estado
- `aplicados()`: Solo aplicados
- `pendientes()`: Solo pendientes
- `completados()`: Solo completados

**Métodos Helper:**
- `estaAplicado()`: Verifica si está aplicado
- `estaPendiente()`: Verifica si está pendiente
- `fueRechazado()`: Verifica si fue rechazado
- `esParcial()`: Verifica si es parcial
- `estaCompletado()`: Verifica si está completado
- `cambiarEstado($estado, $notas)`: Cambia el estado
- `estadosValidos()`: Retorna array de estados válidos

### 3. SolicitudPago (Actualizado)

**Nueva Relación Agregada:**
```php
public function pagos(): BelongsToMany
{
    return $this->belongsToMany(
        PagoSPP::class,
        'pago_solicitud_pago',
        'solicitud_pago_id',
        'pago_spp_id'
    )
    ->withPivot(['monto_aplicado', 'estado_pago', 'notas', 'fecha_aplicacion'])
    ->withTimestamps()
    ->using(PagoSolicitudPago::class);
}
```

**Actualización en `eagerLodable()`:**
```php
return [
    'proveedor',
    'sucursal',
    'empresaConstrucc',
    'cotizacion',
    'cuentasBancarias',
    'ordenCompra',
    'pagos', // NUEVO
];
```

---

## Controlador: ConstruccPagosSPPController

**Ubicación:** `app/Http/Controllers/ConstruccPagosSPPController.php`

### Endpoints Implementados

#### 1. Listar Pagos
```http
GET /api/construcc/pagos-spp
```

**Query Parameters:**
- `page`: Número de página (default: 1)
- `per_page`: Elementos por página (default: 20)
- Cualquier filtro del modelo (ver sección de filtros)

**Respuesta:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "comprobante_pago": "comprobantes_pago/abc123.pdf",
      "fecha_pago": "2026-01-28 10:00:00",
      "referencia_pago": "REF-001",
      "monto_total": 50000.00,
      // ... más campos
    }
  ],
  "pagination": {
    "total": 100,
    "per_page": 20,
    "current_page": 1,
    "last_page": 5
  }
}
```

#### 2. Ver Pago Específico
```http
GET /api/construcc/pagos-spp/{id}
```

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "comprobante_pago": "comprobantes_pago/abc123.pdf",
    "fecha_pago": "2026-01-28 10:00:00",
    "referencia_pago": "REF-001",
    "monto_total": 50000.00,
    "solicitudes_pago": [
      {
        "id": 1,
        "numero_folio_solicitud": "SP-001",
        "pivot": {
          "monto_aplicado": 25000.00,
          "estado_pago": "aplicado",
          "notas": "Pago parcial",
          "fecha_aplicacion": "2026-01-28 10:00:00"
        }
      }
    ]
  }
}
```

#### 3. Crear Pago
```http
POST /api/construcc/pagos-spp
```

**Body (multipart/form-data):**
```json
{
  "comprobante_pago": "<archivo>",
  "fecha_pago": "2026-01-28",
  "referencia_pago": "REF-12345",
  "banco_pago": "BBVA",
  "cuenta_origen": "1234567890",
  "tipo_cuenta_origen": "Cheques",
  "banco_destino": "Santander",
  "cuenta_destino": "0987654321",
  "titular_cuenta_destino": "Proveedor ABC SA de CV",
  "monto_total": 50000.00,
  "observaciones": "Pago de facturas pendientes",
  "empresa_construcc_id": 1,
  "usuario_registro_id": 1,
  "usuario_registro_nombre": "Juan Pérez",
  "solicitudes_pago": [
    {
      "solicitud_pago_id": 1,
      "monto_aplicado": 25000.00,
      "estado_pago": "aplicado",
      "notas": "Pago parcial 50%"
    },
    {
      "solicitud_pago_id": 2,
      "monto_aplicado": 25000.00,
      "estado_pago": "completado",
      "notas": "Pago completo"
    }
  ]
}
```

**Validaciones:**
- ✅ `comprobante_pago` es obligatorio (PDF, JPG, JPEG, PNG, máx 10MB)
- ✅ `fecha_pago` es obligatoria
- ✅ `referencia_pago` es obligatoria
- ✅ `monto_total` debe ser mayor a 0
- ✅ Debe incluir al menos una solicitud de pago
- ✅ La suma de `montos_aplicados` no puede exceder `monto_total`
- ✅ Cada `solicitud_pago_id` debe existir

**Respuesta:**
```json
{
  "success": true,
  "message": "Pago registrado exitosamente",
  "data": { /* datos del pago creado */ }
}
```

#### 4. Actualizar Pago
```http
PUT /api/construcc/pagos-spp/{id}
```

**Body:** Mismos campos que crear, pero todos opcionales

#### 5. Eliminar Pago
```http
DELETE /api/construcc/pagos-spp/{id}
```

**Nota:** Elimina el pago y todas sus relaciones (cascade)

#### 6. Descargar Comprobante
```http
GET /api/construcc/pagos-spp/{id}/comprobante/download
```

Descarga el archivo del comprobante de pago.

#### 7. Agregar SPP a un Pago
```http
POST /api/construcc/pagos-spp/{id}/solicitudes-pago
```

**Body:**
```json
{
  "solicitud_pago_id": 3,
  "monto_aplicado": 10000.00,
  "estado_pago": "aplicado",
  "notas": "Aplicación adicional"
}
```

#### 8. Actualizar Relación Pago-SPP
```http
PUT /api/construcc/pagos-spp/{pagoId}/solicitudes-pago/{solicitudPagoId}
```

**Body:**
```json
{
  "monto_aplicado": 12000.00,
  "estado_pago": "completado",
  "notas": "Actualización de monto"
}
```

#### 9. Eliminar Relación Pago-SPP
```http
DELETE /api/construcc/pagos-spp/{pagoId}/solicitudes-pago/{solicitudPagoId}
```

**Nota:** Revierte el monto abonado en la SPP

#### 10. Estadísticas
```http
GET /api/construcc/pagos-spp/estadisticas?empresa_construcc_id=1
```

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "total_pagos": 150,
    "monto_total_pagado": 5000000.00,
    "pagos_por_empresa": [
      {
        "empresa_construcc_id": 1,
        "total_pagos": 50,
        "monto_total": 2000000.00,
        "empresa_construcc": { /* datos empresa */ }
      }
    ]
  }
}
```

---

## Rutas Registradas

Todas las rutas están en el grupo `construcc` con prefijo `/api/construcc/pagos-spp`:

```php
// CRUD básico
GET     /api/construcc/pagos-spp
GET     /api/construcc/pagos-spp/{pago}
POST    /api/construcc/pagos-spp
PUT     /api/construcc/pagos-spp/{pago}
DELETE  /api/construcc/pagos-spp/{pago}

// Descarga de comprobante
GET     /api/construcc/pagos-spp/{pago}/comprobante/download

// Gestión de relaciones
POST    /api/construcc/pagos-spp/{pago}/solicitudes-pago
PUT     /api/construcc/pagos-spp/{pago}/solicitudes-pago/{solicitudPago}
DELETE  /api/construcc/pagos-spp/{pago}/solicitudes-pago/{solicitudPago}

// Estadísticas
GET     /api/construcc/pagos-spp/estadisticas
```

**Middleware aplicado:** `audit` en todas las rutas

---

## Flujos de Uso

### Flujo 1: Registrar un Pago Completo

```javascript
// Preparar FormData
const formData = new FormData();
formData.append('comprobante_pago', archivoComprobante);
formData.append('fecha_pago', '2026-01-28');
formData.append('referencia_pago', 'REF-12345');
formData.append('monto_total', 100000.00);
formData.append('empresa_construcc_id', 1);

// Agregar solicitudes de pago
formData.append('solicitudes_pago[0][solicitud_pago_id]', 1);
formData.append('solicitudes_pago[0][monto_aplicado]', 50000.00);
formData.append('solicitudes_pago[0][estado_pago]', 'completado');

formData.append('solicitudes_pago[1][solicitud_pago_id]', 2);
formData.append('solicitudes_pago[1][monto_aplicado]', 50000.00);
formData.append('solicitudes_pago[1][estado_pago]', 'completado');

// Enviar petición
const response = await axios.post('/api/construcc/pagos-spp', formData, {
  headers: {
    'Content-Type': 'multipart/form-data',
    'X-API-Key': 'tu-api-key'
  }
});
```

### Flujo 2: Consultar Pagos con Filtros

```javascript
const response = await axios.get('/api/construcc/pagos-spp', {
  params: {
    empresa_construcc_id: 1,
    fecha_pago_desde: '2026-01-01',
    fecha_pago_hasta: '2026-01-31',
    monto_min: 10000,
    per_page: 20,
    page: 1
  },
  headers: {
    'X-API-Key': 'tu-api-key'
  }
});
```

### Flujo 3: Agregar SPP Adicional a un Pago

```javascript
const response = await axios.post('/api/construcc/pagos-spp/1/solicitudes-pago', {
  solicitud_pago_id: 3,
  monto_aplicado: 15000.00,
  estado_pago: 'aplicado',
  notas': 'Pago adicional'
}, {
  headers: {
    'X-API-Key': 'tu-api-key'
  }
});
```

---

## Archivos Creados

### Migraciones
- ✅ `database/migrations/2026_01_28_133352_create_pagos_spp_table.php`
- ✅ `database/migrations/2026_01_28_133358_create_pago_solicitud_pago_table.php`

### Modelos
- ✅ `app/Models/PagoSPP.php`
- ✅ `app/Models/PagoSolicitudPago.php`
- ✅ `app/Models/SolicitudPago.php` (actualizado)

### Controlador
- ✅ `app/Http/Controllers/ConstruccPagosSPPController.php`

### Rutas
- ✅ `routes/segmented/construcc.php` (actualizado)

---

## Validaciones Automáticas

El sistema incluye las siguientes validaciones automáticas:

1. ✅ **Monto disponible:** Verifica que haya monto disponible antes de aplicar
2. ✅ **Actualización de saldos:** Actualiza automáticamente `monto_abonado`, `saldo_pendiente` y `pago_completo`
3. ✅ **Duplicados:** No permite aplicar el mismo pago dos veces a la misma SPP
4. ✅ **Cascade delete:** Elimina relaciones automáticamente al eliminar un pago
5. ✅ **Comprobante único:** Constraint UNIQUE en `comprobante_pago`
6. ✅ **Relación obligatoria:** No permite crear un pago sin SPPs asociadas

---

## Estados del Pago

Los estados disponibles para la relación pago-solicitud son:

| Estado | Descripción |
|--------|-------------|
| `aplicado` | Pago aplicado correctamente |
| `pendiente` | Pago registrado pero pendiente de aplicar |
| `rechazado` | Pago rechazado |
| `parcial` | Pago parcial aplicado |
| `completado` | Pago completo de esta SPP |

---

## Verificación de Funcionamiento

Para verificar que todo está funcionando correctamente:

```bash
# Verificar que las tablas existen
php artisan tinker --execute="
  echo 'Pagos SPP: ' . DB::connection('mysql5')->table('pagos_spp')->count();
  echo PHP_EOL;
  echo 'Pago-Solicitud: ' . DB::connection('mysql5')->table('pago_solicitud_pago')->count();
"

# Probar un endpoint
curl -X GET "http://localhost/api/construcc/pagos-spp" \
  -H "X-API-Key: tu-api-key"
```

---

## Próximos Pasos Recomendados

1. ⭐ Crear notificaciones cuando se registre un pago
2. ⭐ Implementar reportes de pagos por período
3. ⭐ Agregar validación de saldos pendientes
4. ⭐ Crear dashboard con estadísticas de pagos
5. ⭐ Implementar exportación a Excel/PDF
6. ⭐ Agregar recursos (Resources) para formatear respuestas JSON
7. ⭐ Crear Request classes para validaciones reutilizables
8. ⭐ Implementar políticas de autorización (Policies)

---

## Notas Importantes

- 🔴 **Comprobante único:** Cada pago debe tener un comprobante único
- 🔴 **Relación obligatoria:** Al registrar un pago, es obligatorio asociarlo a al menos una SPP
- 🔴 **Conexión mysql5:** Todas las tablas usan la conexión `mysql5`
- 🔴 **Auditoría activa:** Todas las rutas tienen middleware `audit`
- 🔴 **Integridad referencial:** Las tablas tienen llaves foráneas con `onDelete('cascade')`

---

## Soporte y Mantenimiento

Para cualquier problema o duda sobre este sistema:

1. Revisar los logs en `storage/logs/laravel.log`
2. Verificar que las migraciones se ejecutaron correctamente
3. Comprobar que el modelo `SolicitudPago` tiene la relación `pagos()`
4. Asegurar que las rutas están registradas correctamente

---

**Implementado por:** IA Assistant  
**Fecha:** 28 de Enero de 2026  
**Versión:** 1.0.0
