# 📦 Colección Postman - Módulo Pagos SPP

Colección completa para probar todas las rutas del módulo de gestión de pagos de solicitudes de pago (SPP) en el sistema de construcción.

## 📋 Contenido

La colección incluye **16 requests** organizados en **4 secciones**:

### 1. 🔄 **CRUD Pagos** (6 requests)
- **Listar Pagos** - GET con paginación y filtros
- **Ver Pago** - GET detalle de un pago específico
- **Crear Pago** - POST con comprobante y múltiples SPP
- **Actualizar Pago** - PUT para modificar datos
- **Eliminar Pago** - DELETE con eliminación de archivos
- **Descargar Comprobante** - GET archivo del comprobante

### 2. 🔗 **Relaciones Pago-Solicitud** (3 requests)
- **Agregar Solicitud a Pago** - POST vincular SPP adicional
- **Actualizar Relación** - PUT modificar monto/estado
- **Eliminar Relación** - DELETE desvincular SPP

### 3. 👤 **SPP por Proveedor** (6 requests)
- **Listar SPP del Proveedor** - GET paginado
- **Ver SPP del Proveedor** - GET detalle con pagos
- **Listar Pagos de una SPP** - GET pagos parciales
- **Ver Pago de una SPP** - GET detalle específico
- **Subir Comprobante** - POST actualizar archivo
- **Registrar Pago** - POST pago múltiple a proveedor

### 4. 📊 **Estadísticas** (1 request)
- **Estadísticas de Pagos** - GET métricas generales

---

## 🚀 Instalación

### Paso 1: Importar en Postman

1. Abre **Postman**
2. Click en **Import** (botón superior izquierdo)
3. Selecciona el archivo: `Postman_Pagos_SPP_Construcc.json`
4. Click en **Import**

### Paso 2: Configurar Variables

Después de importar, configura las variables de la colección:

1. Click derecho en la colección → **Edit**
2. Ve a la pestaña **Variables**
3. Actualiza los siguientes valores:

| Variable | Valor Ejemplo | Descripción |
|----------|---------------|-------------|
| `baseUrl` | `http://localhost:8000/api` | URL base de tu API |
| `apiKey` | `tu_api_key_aqui` | Tu API Key para autenticación |
| `proveedorId` | `1` | ID del proveedor para pruebas |
| `pagoId` | `1` | ID del pago para pruebas |
| `sppId` | `1` | ID de la solicitud de pago |
| `solicitudPagoId` | `1` | ID de solicitud en relaciones |

---

## 📝 Guía de Uso

### Flujo Completo de Prueba

#### 1️⃣ **Crear un Pago Nuevo**

**Request:** `CRUD Pagos > Crear Pago`

**Datos necesarios:**
- Archivo comprobante (PDF, JPG, PNG)
- Fecha de pago
- Referencia de pago
- Datos bancarios (origen y destino)
- Monto total
- Array de solicitudes de pago

**Ejemplo de body:**
```
comprobante_pago: [archivo]
fecha_pago: 2026-01-13
referencia_pago: REF-2026-001
monto_total: 25000.00
solicitudes_pago[0][solicitud_pago_id]: 1
solicitudes_pago[0][monto_aplicado]: 15000.00
solicitudes_pago[0][estado_pago]: aplicado
solicitudes_pago[1][solicitud_pago_id]: 2
solicitudes_pago[1][monto_aplicado]: 10000.00
solicitudes_pago[1][estado_pago]: aplicado
```

**Respuesta exitosa (201):**
```json
{
    "status": "OK",
    "code": 201,
    "message": "Pago registrado y aplicado exitosamente.",
    "data": {
        "id": 1,
        "referencia_pago": "REF-2026-001",
        "monto_total": "25000.00",
        "fecha_pago": "2026-01-13",
        "comprobante_pago": "comprobantes_pago/xxx.pdf",
        "solicitudesPago": [...]
    }
}
```

#### 2️⃣ **Listar Pagos**

**Request:** `CRUD Pagos > Listar Pagos`

**Query params opcionales:**
- `per_page`: Cantidad por página (default: 20)
- `page`: Número de página
- `fecha_pago`: Filtrar por fecha
- `empresa_construcc_id`: Filtrar por empresa

#### 3️⃣ **Agregar SPP Adicional a un Pago**

**Request:** `Relaciones Pago-Solicitud > Agregar Solicitud a Pago`

**Body (JSON):**
```json
{
    "solicitud_pago_id": 3,
    "monto_aplicado": 5000.00,
    "estado_pago": "aplicado",
    "notas": "Pago adicional"
}
```

#### 4️⃣ **Consultar SPP de un Proveedor**

**Request:** `SPP por Proveedor > Listar SPP del Proveedor`

**Path:** `/construcc/pagos-spp/proveedor/{proveedorId}/spp`

**Respuesta:** Lista paginada de todas las SPP del proveedor con sus pagos asociados.

#### 5️⃣ **Registrar Pago para Múltiples SPP del Mismo Proveedor**

**Request:** `SPP por Proveedor > Registrar Pago para SPPs del Proveedor`

**Especial:** Este endpoint valida que todas las SPP pertenezcan al mismo proveedor.

---

## 🔑 Autenticación

Todas las rutas requieren el header `X-API-KEY`:

```
X-API-KEY: tu_api_key_aqui
```

La API Key se configura automáticamente usando la variable `{{apiKey}}`.

---

## 📂 Estructura de Archivos

### Comprobantes de Pago

Los archivos se suben como `multipart/form-data`:

**Formatos aceptados:**
- PDF (`.pdf`)
- Imágenes (`.jpg`, `.jpeg`, `.png`)

**Tamaño máximo:** 10MB

**Validaciones:**
- Campo `comprobante_pago` obligatorio en creación
- Opcional en actualización (mantiene el anterior si no se envía)

---

## 🎯 Estados de Pago

Los pagos pueden tener los siguientes estados en la relación con SPP:

| Estado | Descripción |
|--------|-------------|
| `aplicado` | Pago completamente aplicado |
| `pendiente` | Pago registrado pero no aplicado |
| `rechazado` | Pago rechazado |
| `parcial` | Pago parcial aplicado |
| `completado` | Pago totalmente completado |

---

## 🧪 Casos de Prueba

### ✅ Caso 1: Pago Simple a Una SPP

```
1. Crear pago con 1 solicitud
2. Verificar que el monto coincida
3. Consultar la SPP y verificar actualización de saldos
```

### ✅ Caso 2: Pago Múltiple

```
1. Crear pago con 3 solicitudes diferentes
2. Verificar que la suma de montos no exceda el monto total
3. Consultar cada SPP y verificar estado
```

### ✅ Caso 3: Agregar SPP a Pago Existente

```
1. Crear pago con 1 solicitud
2. Agregar 2 solicitudes adicionales
3. Verificar que se respete el monto disponible
```

### ✅ Caso 4: Actualizar Comprobante

```
1. Crear pago con comprobante inicial
2. Subir nuevo comprobante (actualización)
3. Verificar que el anterior se eliminó
4. Descargar y verificar el nuevo comprobante
```

### ✅ Caso 5: Pago a Proveedor Específico

```
1. Listar SPP del proveedor
2. Seleccionar múltiples SPP pendientes
3. Crear pago único para todas
4. Verificar actualización en todas las SPP
```

---

## ⚠️ Validaciones Importantes

### Monto del Pago

```
✅ monto_total >= Σ(monto_aplicado de cada SPP)
❌ Error si el monto aplicado excede el monto total
```

### Proveedor en SPP

```
✅ Todas las SPP deben pertenecer al mismo proveedor
❌ Error 403 si la SPP no pertenece al proveedor especificado
```

### Archivos

```
✅ Comprobante obligatorio en creación
✅ Opcional en actualización
✅ Se elimina el anterior al actualizar
```

---

## 🐛 Manejo de Errores

### Respuestas de Error Comunes

**404 - No encontrado:**
```json
{
    "status": "ERROR",
    "code": 404,
    "message": "El pago no existe.",
    "data": null
}
```

**403 - Forbidden:**
```json
{
    "status": "ERROR",
    "code": 403,
    "message": "La solicitud de pago no pertenece a este proveedor.",
    "data": null
}
```

**422 - Validación:**
```json
{
    "status": "ERROR",
    "code": 422,
    "message": "Error de validación en los datos del pago.",
    "data": {
        "comprobante_pago": ["El comprobante de pago es obligatorio."],
        "monto_total": ["El monto debe ser mayor a cero."]
    }
}
```

**500 - Error del servidor:**
```json
{
    "status": "ERROR",
    "code": 500,
    "message": "No se pudo registrar el pago. Por favor, intente nuevamente.",
    "data": null
}
```

---

## 📊 Respuestas de Éxito

### Paginación

```json
{
    "status": "OK",
    "code": 200,
    "message": "Operación exitosa.",
    "data": [...],
    "pagination": {
        "total": 50,
        "per_page": 20,
        "current_page": 1,
        "last_page": 3,
        "from": 1,
        "to": 20
    }
}
```

### Recurso Individual

```json
{
    "status": "OK",
    "code": 200,
    "message": "Pago obtenido exitosamente.",
    "data": {
        "id": 1,
        "referencia_pago": "REF-2026-001",
        ...
    }
}
```

---

## 🔄 Flujos de Trabajo Recomendados

### Flujo 1: Registro de Pago Completo

```
1. GET /proveedor/{id}/spp → Listar SPP pendientes
2. POST /pagos-spp → Crear pago con múltiples SPP
3. GET /pagos-spp/{id} → Verificar pago creado
4. GET /proveedor/{id}/spp/{sppId} → Verificar actualización de SPP
```

### Flujo 2: Gestión de Comprobantes

```
1. POST /pagos-spp → Crear pago con comprobante
2. GET /pagos-spp/{id}/comprobante/download → Descargar comprobante
3. POST /proveedor/{id}/spp/{sppId}/pagos/{pagoId}/subir-comprobante → Actualizar
4. GET /pagos-spp/{id}/comprobante/download → Verificar nuevo archivo
```

### Flujo 3: Consulta y Reportes

```
1. GET /estadisticas → Ver métricas generales
2. GET /pagos-spp?empresa_construcc_id=1 → Filtrar por empresa
3. GET /proveedor/{id}/spp → Ver SPP por proveedor
```

---

## 📞 Soporte

Para reportar problemas o sugerencias sobre esta colección, contacta al equipo de desarrollo.

---

**Versión:** 1.0.0  
**Fecha:** Enero 2026  
**Módulo:** Construcción - Pagos SPP
