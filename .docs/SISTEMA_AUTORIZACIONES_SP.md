# Sistema de Autorizaciones para Solicitudes de Pago (SP)

## 📋 Resumen de Implementación

Se ha implementado un **sistema completo de autorizaciones por roles** para las Solicitudes de Pago, cumpliendo con todas las reglas de negocio especificadas, además de la funcionalidad para **manejo de archivos de cotización**.

---

## 🏗️ Estructura del Sistema de Autorizaciones

### Roles y Permisos

| Rol | Código | Puede Autorizar | Puede Rechazar | Puede Confirmar Pago |
|-----|--------|----------------|----------------|---------------------|
| **DG** (Director General) | `dg` | ✅ | ✅ (En cualquier momento antes de PAGADO) | ❌ |
| **DT** (Director Técnico) | `dt` | ✅ | ✅ (Solo si está PENDIENTE) | ❌ |
| **CO** (Coordinador) | `pc` | ✅ | ✅ (Solo si está PENDIENTE) | ❌ |
| **SI** (Sistema) | `si` | ✅ | ✅ (Solo si está PENDIENTE) | ❌ |
| **DA** (Departamento Administrativo) | `da` | ❌ | ✅ (Solo si está PENDIENTE) | ✅ |
| **RO** (Rol Operativo) | `ro` | ❌ | ❌ | ❌ |

### Estados de Solicitud

- **PENDIENTE**: Estado inicial al crear la SP por el proveedor
- **AUTORIZADA**: Cuando todos los roles [DG, DT, CO, SI] han autorizado la solicitud
- **RECHAZADA**: Cuando cualquier rol autorizado rechaza la solicitud
- **PAGADO**: Estado final cuando DA confirma el pago con comprobante

---

## 🔄 Flujo de Autorización

### 1. Estado PENDIENTE → Proceso de Autorización
```
PENDIENTE → [Autorizaciones individuales por rol] → AUTORIZADA
         ↘ [Cualquier rechazo] → RECHAZADA
```

### 2. Estado AUTORIZADA → Confirmación de Pago
```
AUTORIZADA → [DA confirma con comprobante] → PAGADO
```

### 3. Lógica de Autorización Compuesta

La SP se mantiene en estado **PENDIENTE** hasta que **TODOS** los roles [DG, DT, CO, SI] la autoricen:

- ✅ Si **DG** autoriza: SP ya no aparece en pendientes para otros roles
- ✅ Si **DT, CO, SI** autorizan: SP permanece PENDIENTE pero no se lista para ese rol
- ✅ Cuando **TODOS** [DG, DT, CO, SI] autorizan: Estado cambia a **AUTORIZADA**
- ✅ **DA** puede ver las SP **AUTORIZADAS** para confirmar pago

---

## 🛠️ Implementación Técnica

### Campos en Base de Datos

Se agregaron campos para tracking por rol:

```sql
-- Campos de estado por rol (0=PENDIENTE, 1=AUTORIZADA, 2=RECHAZADA, 3=PAGADO)
dg TINYINT DEFAULT 0
dt TINYINT DEFAULT 0  
pc TINYINT DEFAULT 0  -- Coordinador (CO)
si TINYINT DEFAULT 0
da TINYINT DEFAULT 0
ro TINYINT DEFAULT 0

-- Campos de fecha por rol
dg_fecha TIMESTAMP NULL
dt_fecha TIMESTAMP NULL
pc_fecha TIMESTAMP NULL 
si_fecha TIMESTAMP NULL
da_fecha TIMESTAMP NULL
ro_fecha TIMESTAMP NULL

-- Campos adicionales de cotización
ruta_archivo_cotizacion_pdf VARCHAR(500) NULL
ruta_archivo_cotizacion_xml VARCHAR(500) NULL

-- Campos de pagos parciales (NUEVOS)
monto_abonado DECIMAL(12,2) DEFAULT 0
saldo_pendiente DECIMAL(12,2) DEFAULT 0
pago_completo BOOLEAN DEFAULT FALSE
notas_abono TEXT NULL
```

### Controlador Principal: `ConstruccSolicitudPagoController`

#### Métodos Implementados:

1. **`autorizar()`** - POST `/construcc/solicitudes-pago/{id}/autorizar`
   - Valida rol en parámetro `rol`
   - Verifica estado PENDIENTE
   - Actualiza campo específico del rol
   - Verifica autorización completa para cambio de estado

2. **`rechazar()`** - POST `/construcc/solicitudes-pago/{id}/rechazar`
   - Valida rol y motivo de rechazo
   - Aplica reglas específicas por rol (DG vs otros)
   - Cambia estado a RECHAZADA

3. **`confirmarPago()`** - POST `/construcc/solicitudes-pago/{id}/confirmar-pago`
   - Solo para rol DA
   - Requiere estado AUTORIZADA
   - Requiere subida de comprobante
   - Cambia estado a PAGADO

#### Métodos de Descarga Protegidos:
- `descargarComprobante()`
- `descargarFacturaPdf()`
- `descargarFacturaXml()`
- `descargarCotizacionPdf()` ⭐ **NUEVO**
- `descargarCotizacionXml()` ⭐ **NUEVO**

---

## 📁 Funcionalidad de Cotizaciones

### Campos Agregados
- `ruta_archivo_cotizacion_pdf`: Almacena PDF de cotización
- `ruta_archivo_cotizacion_xml`: Almacena XML de cotización

### Validaciones en Request
```php
'cotizacion_pdf' => 'nullable|file|mimes:pdf|max:10240',
'cotizacion_xml' => 'nullable|file|mimes:xml|max:5120',
```

### Rutas de Descarga Protegidas
- GET `/construcc/solicitudes-pago/{id}/cotizacion-pdf/download`
- GET `/construcc/solicitudes-pago/{id}/cotizacion-xml/download`
- GET `/proveedores/{proveedor}/solicitudes-pago/{id}/descargar-cotizacion-pdf`
- GET `/proveedores/{proveedor}/solicitudes-pago/{id}/descargar-cotizacion-xml`

---

## 🔗 APIs y Endpoints

### Endpoints Principales para Construcción

```http
# Listar solicitudes (con filtros)
GET /api/construcc/solicitudes-pago

# Ver detalle de solicitud
GET /api/construcc/solicitudes-pago/{id}

# ===== NUEVOS LISTADOS ESPECIALIZADOS =====
# Listar por rol específico (solo las que necesitan acción de ese rol)
GET /api/construcc/solicitudes-pago/por-rol?rol=DG|DT|CO|SI|DA

# Listar por estado
GET /api/construcc/solicitudes-pago/por-estado?estado=PENDIENTE|AUTORIZADA|RECHAZADA|PAGADO

# Estadísticas por rol
GET /api/construcc/solicitudes-pago/estadisticas-rol?rol=DG (opcional)

# Autorizar solicitud
POST /api/construcc/solicitudes-pago/{id}/autorizar
Body: { "rol": "DG|DT|CO|SI" }

# Rechazar solicitud  
POST /api/construcc/solicitudes-pago/{id}/rechazar
Body: { "rol": "DG|DT|CO|SI|DA", "motivo_rechazo": "string" }

# ===== CONFIRMAR PAGO CON ABONOS PARCIALES =====
# Confirmar pago completo o parcial (solo DA)
POST /api/construcc/solicitudes-pago/{id}/confirmar-pago
Body: FormData {
  comprobante: archivo,
  monto_abono: 1500.50,
  notas_abono: "Primer pago parcial" (opcional)
}
```

### Endpoints para Proveedores

```http
# Crear solicitud (con cotización opcional)
POST /api/proveedores/{id}/solicitudes-pago
Body: FormData con 'cotizacion_pdf' y 'cotizacion_xml' opcionales

# Descargar archivos de cotización
GET /api/proveedores/{proveedor}/solicitudes-pago/{id}/descargar-cotizacion-pdf
GET /api/proveedores/{proveedor}/solicitudes-pago/{id}/descargar-cotizacion-xml
```

---

## 📊 Recursos JSON

### SolicitudPagoResource
```json
{
  "id": 1,
  "numero_folio_solicitud": "SP000001",
  "descripcion_concepto": "Concepto de pago",
  "estado_solicitud": "pendiente|autorizada|rechazada|pagado",
  
  // Archivos con URLs de descarga
  "url_factura_pdf": "https://api.../descargar-factura-pdf",
  "url_factura_xml": "https://api.../descargar-factura-xml", 
  "url_cotizacion_pdf": "https://api.../descargar-cotizacion-pdf", // NUEVO
  "url_cotizacion_xml": "https://api.../descargar-cotizacion-xml", // NUEVO
  "url_comprobante_pago": "https://api.../descargar-comprobante",
  
  // Estados por rol
  "dg": true/false,
  "dg_fecha": "2025-10-04 12:30:00",
  "dt": true/false, 
  "dt_fecha": "2025-10-04 13:15:00",
  "pc": true/false,
  "pc_fecha": "2025-10-04 14:00:00",
  "si": true/false,
  "si_fecha": "2025-10-04 14:30:00",
  "da": true/false,
  "da_fecha": "2025-10-04 15:00:00", // NUEVO
  "ro": true/false,
  "ro_fecha": "2025-10-04 15:30:00",
  
  // ===== CAMPOS DE PAGOS PARCIALES (NUEVOS) =====
  "monto_total": 5000.00,
  "monto_abonado": 2000.00,
  "saldo_pendiente": 3000.00,
  "pago_completo": false,
  "porcentaje_pagado": 40.00,
  "notas_abono": "Primer abono del 40%"
}
```

---

## 📋 Listados Especializados por Rol

### Lógica de Listado por Rol

Cada rol ve únicamente las SP que requieren su acción:

#### **DG (Director General)**
- Ve: Todas las SP en estado **PENDIENTE** (sin importar autorizaciones de otros roles)
- Razón: DG puede autorizar cualquier SP directamente

#### **DT, CO, SI (Director Técnico, Coordinador, Sistema)**  
- Ve: SP en estado **PENDIENTE** que:
  - No han sido autorizadas por su rol AÚN
  - Y que **DG no ha autorizado** (para evitar duplicados)
- Razón: Una vez que DG autoriza, ya no necesitan acción de otros roles

#### **DA (Departamento Administrativo)**
- Ve: SP en estado **AUTORIZADA** (listas para pago)
- Ve: SP en estado **PAGADO** con `pago_completo = false` (con saldos pendientes)
- Razón: DA solo puede confirmar pagos de SP autorizadas

### Endpoints de Listado

```http
# Listar para DG - Todas las pendientes
GET /api/construcc/solicitudes-pago/por-rol?rol=DG

# Listar para DT - Solo pendientes sin autorización de DG
GET /api/construcc/solicitudes-pago/por-rol?rol=DT

# Listar para DA - Autorizadas + Parcialmente pagadas
GET /api/construcc/solicitudes-pago/por-rol?rol=DA
```

### Respuesta de Estadísticas por Rol

```json
{
  "status": "SUCCESS",
  "data": {
    "pendientes": 15,           // Pendientes de acción para el rol
    "autorizadas": 8,           // Total autorizadas (general)
    "rechazadas": 2,            // Total rechazadas (general)
    "pagadas_completas": 12,    // Pagos completos
    "con_pagos_parciales": 3,   // Con saldos pendientes
    "monto_total_pendiente": 45000.00,
    "monto_total_autorizado": 32000.00,
    "monto_total_pagado": 28500.00
  }
}
```

---

## 💰 Sistema de Pagos Parciales

### Funcionalidad

- **Pagos completos**: El monto del abono = saldo pendiente
- **Pagos parciales**: El monto del abono < saldo pendiente
- **Seguimiento automático**: Cálculo de saldos y porcentajes
- **Múltiples abonos**: Se pueden hacer varios abonos hasta completar

### Validaciones

- ✅ Monto abono > 0
- ✅ Monto abono ≤ saldo pendiente
- ✅ SP debe estar AUTORIZADA o en PAGADO parcial
- ✅ Archivo comprobante obligatorio

### Estados y Transiciones

```
AUTORIZADA + Abono Parcial → AUTORIZADA (con saldo pendiente)
AUTORIZADA + Abono Total → PAGADO (pago_completo = true)
PAGADO (parcial) + Abono → PAGADO (completo o sigue parcial)
```

### Campos de Seguimiento

- `monto_total`: Monto original de la SP
- `monto_abonado`: Total acumulado de abonos
- `saldo_pendiente`: Cantidad que falta por pagar
- `pago_completo`: true cuando saldo = 0
- `porcentaje_pagado`: % del monto total pagado
- `notas_abono`: Notas del último abono

---

## ⚡ Características Principales

### ✅ Sistema de Autorizaciones Completo
- [x] Validación por roles específicos
- [x] Estados compuestos (PENDIENTE con autorizaciones parciales)
- [x] Cambio automático a AUTORIZADA cuando todos aprueban
- [x] Lógica de rechazo diferenciada por rol
- [x] Confirmación de pago con comprobante obligatorio

### ✅ Manejo de Archivos de Cotización  
- [x] Subida opcional de PDF y XML de cotización
- [x] Descarga protegida por autenticación
- [x] Validación de tipos y tamaños de archivo
- [x] Integración completa en recursos JSON

### ✅ Seguridad y Validaciones
- [x] Validación de estados antes de cada operación
- [x] Verificación de roles autorizados
- [x] Protección de descarga de archivos
- [x] Respuestas consistentes con ApiResponse trait
- [x] Manejo de errores comprehensivo

### ✅ Base de Datos
- [x] Migración ejecutada correctamente
- [x] Campos agregados al fillable del modelo
- [x] Casts apropiados para enums y fechas
- [x] Filtros implementados para búsquedas

---

## 🚀 Próximos Pasos Recomendados

1. **Testing**: Crear pruebas unitarias para validar todas las reglas de negocio
2. **Documentación API**: Generar documentación Swagger/OpenAPI
3. **Logs de Auditoría**: Implementar logging detallado de cambios de estado
4. **Notificaciones**: Sistema de notificaciones por cambio de estado
5. **Dashboard**: Métricas y reportes del flujo de autorizaciones

---

## 📝 Notas Técnicas

- **Framework**: Laravel con Sanctum para autenticación
- **Storage**: Archivos almacenados en disk 'private' para seguridad
- **Enums**: `EstadoSP` (string) para estado general, `EstadoSolicitud` (int) para roles
- **Middleware**: Protección por API Key en rutas de construcción
- **Resources**: Transformación consistente de datos con URLs seguras

---

*Sistema implementado el 04 de Octubre de 2025*
*Desarrollo completo siguiendo las especificaciones de negocio requeridas*