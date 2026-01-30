# 💰 Colección Postman - Solicitudes de Pago Construcción

## 📦 Archivo

**`Postman_SPP_Construcc.json`**

Colección enfocada **EXCLUSIVAMENTE** en las rutas de **Solicitudes de Pago (SPP)** del módulo de construcción.

---

## 🎯 ¿Qué Incluye Esta Colección?

✅ **10 requests** enfocados únicamente en SPP  
✅ Validación condicional de archivos (factura vs cotización)  
✅ Todos los niveles de usuario cubiertos  
✅ Campos adicionales implementados (obra_id, tipo, notas, etc.)  
✅ Dos flujos principales: proveedor existente y crear todo

---

## 🚀 Importar en Postman

### Método Rápido:
1. Abre **Postman**
2. **Arrastra** el archivo `Postman_SPP_Construcc.json`
3. ¡Listo! 🎉

### Método Manual:
1. Click en **"Import"**
2. Selecciona `Postman_SPP_Construcc.json`
3. Click en **"Import"**

---

## 📂 Estructura (10 Requests)

```
💰 SPP - Solicitudes de Pago Construcción
│
├── 📋 FLUJO 1: SPP con Proveedor Existente (6 requests)
│   ├── 1.1 SPP - Residente (PENDIENTE)
│   ├── 1.2 SPP - Director General (AUTORIZADA)
│   ├── 1.3 SPP - Director Técnico (AUTORIZADA)
│   ├── 1.4 SPP - Director Administrativo (DIRECTO A PAGO) ⭐
│   ├── 1.5 SPP - Programación y Control (AUTORIZADA)
│   └── 1.6 SPP - SOLO con Cotización (sin factura) ✨
│
└── 📦 FLUJO 2: Crear Proveedor + Cuenta + SPP (4 requests)
    ├── 2.1 Crear TODO - Residente (SPP PENDIENTE)
    ├── 2.2 Crear TODO - Director General (SPP AUTORIZADA)
    └── 2.3 Crear TODO - Solo con Cotización ✨
```

---

## 🔑 Endpoints Incluidos

### **FLUJO 1: Proveedor Existente**
```
POST /construcc/proveedor/{proveedor}/solicitudes-pago
```

**Requisito:** El proveedor DEBE existir con `tipo_alta = 2`

**Body (form-data):**
- Datos de la SPP (descripción, monto, observaciones)
- Archivos (factura PDF/XML o cotización)
- Cuenta bancaria del proveedor
- Datos de usuario y empresa construcción
- Campos adicionales (obra_id, tipo, notas, etc.)

---

### **FLUJO 2: Crear Todo en Uno**
```
POST /construcc/solicitud-pago/generar-spp-construcc
```

**Crea simultáneamente:**
1. ✅ Proveedor nuevo (`tipo_alta = 2`)
2. ✅ Cuenta bancaria (automáticamente preferida)
3. ✅ Solicitud de Pago (SPP)
4. ✅ Asociación proveedor-empresa construcción

**Body (form-data):**
- Datos del proveedor (rfc, razón social, email, etc.)
- Datos de cuenta bancaria
- Datos de la SPP
- Archivos
- Campos adicionales

---

## ✨ Validación Condicional de Archivos

### **Reglas:**
```
SI hay cotización → Factura PDF/XML son OPCIONALES
SI NO hay cotización → Factura PDF/XML son OBLIGATORIAS

Mínimo debe haber: Factura (PDF+XML) O Cotización
```

### **Ejemplos:**

#### ✅ **Caso 1: Con Factura Completa**
```
factura_pdf: ✅ archivo.pdf
factura_xml: ✅ archivo.xml
cotizacion: (opcional)

Resultado: tiene_factura = true
```

#### ✅ **Caso 2: Solo Cotización** (Anticipo)
```
factura_pdf: ❌ (sin archivo)
factura_xml: ❌ (sin archivo)
cotizacion: ✅ cotizacion.pdf

Resultado: tiene_factura = false
```

#### ❌ **Caso 3: Sin Archivos** (Error)
```
factura_pdf: ❌
factura_xml: ❌
cotizacion: ❌

Resultado: Error 422 - Archivos obligatorios
```

---

## 👥 Niveles de Usuario y Estados

| nivel_id | Rol | Estado SPP | Acción Adicional |
|----------|-----|------------|------------------|
| 0 | Admin | PENDIENTE | Requiere aprobación |
| 1 | Director General (DG) | **AUTORIZADA** | ✅ Auto-aprueba: dg=autorizada |
| 2 | Director Técnico (DT) | **AUTORIZADA** | ✅ Auto-aprueba: dt=autorizada |
| 3 | Director Administrativo (DA) | **AUTORIZADA** | ⭐ Directo a pago: da=autorizada, pc=autorizada |
| 4 | Superintendente (SI) | PENDIENTE | Solo verifica |
| 5 | Programación y Control (PC) | **AUTORIZADA** | ✅ Auto-aprueba: pc=autorizada |
| 6 | Residente de Obra (RO) | PENDIENTE | Requiere aprobación |

---

## 📝 Campos Adicionales en SPP

Todos los requests de SPP incluyen estos campos **opcionales**:

| Campo | Tipo | Ejemplo | Descripción |
|-------|------|---------|-------------|
| `obra_id` | integer | `5` | ID de la obra |
| `tipo` | string | `"Material"` / `"Servicio"` | Tipo de solicitud |
| `tipo_id` | integer | `3` | ID del tipo |
| `notas` | string | `"Para muros..."` | Notas adicionales (max 1000) |
| `utilizara` | string | `"Construcción"` | Para qué se usará |
| `equipo` | string | `"Cuadrilla A"` | Nombre del equipo |
| `equipo_id` | integer | `1` | ID del equipo |

---

## ⚙️ Variables de Colección

### Variables Pre-configuradas:
```json
{
  "baseUrl": "http://localhost:80/api",
  "apiKey": "7f2wnCyn7ctmTE7B3mrtDPKCPVF9z8pYseihsHA6",
  "proveedorId": "1"
}
```

### Cambiar Variables:
1. Click derecho en la colección
2. **"Edit"** → Pestaña **"Variables"**
3. Modificar según tu entorno

### Para Producción:
```json
"baseUrl": "https://apicons.ddns.net:8092/api"
```

---

## 📋 Guía de Uso Rápido

### **Escenario 1: Generar SPP para Proveedor Existente**

**Requisito:** El proveedor ya debe existir en el sistema

**Pasos:**
1. Abre carpeta: **"FLUJO 1: SPP con Proveedor Existente"**
2. Cambia `{{proveedorId}}` en variables (o edita el request)
3. Selecciona el request según nivel de usuario:
   - **1.1** → Residente (requiere aprobación)
   - **1.2** → Director General (auto-aprueba)
   - **1.4** → Director Administrativo (directo a pago)
4. Ve a **"Body"** → **"form-data"**
5. Adjunta archivos (factura PDF/XML o solo cotización)
6. Click en **"Send"**

---

### **Escenario 2: Crear Proveedor Nuevo + SPP**

**Requisito:** RFC, email y teléfono NO deben existir

**Pasos:**
1. Abre carpeta: **"FLUJO 2: Crear Proveedor + Cuenta + SPP"**
2. Selecciona request según nivel:
   - **2.1** → Residente (SPP pendiente)
   - **2.2** → Director (SPP auto-aprobada)
   - **2.3** → Solo cotización (sin factura)
3. Modifica los datos del proveedor en el body
4. Adjunta archivos
5. Click en **"Send"**

**Resultado:** Crea 3 cosas en una sola operación ✨

---

### **Escenario 3: SPP Solo con Cotización (Anticipo)**

**Caso de uso:** Anticipos donde aún no hay factura

**Pasos:**
1. Usa **1.6** (proveedor existente) o **2.3** (crear nuevo)
2. En **"Body"** → **form-data**:
   - ❌ **NO** adjuntes `factura_pdf`
   - ❌ **NO** adjuntes `factura_xml`
   - ✅ **SÍ** adjunta `cotizacion`
3. Click en **"Send"**

**Resultado:** `tiene_factura = false` ✅

---

## ⚠️ Notas Importantes

### **1. Archivos en Postman**
Los requests de SPP usan **form-data**. Los campos de archivo están pre-configurados pero necesitas:

1. Click en **"Select Files"** en cada campo
2. Navegar a tus archivos de prueba
3. Seleccionar el archivo

**Ruta sugerida de archivos de prueba:**
```
C:/repositorio/app/api-proveedores/.vscode/http/tests/facturas/
- Cfdi-4539.pdf
- Cfdi-4539.xml
```

### **2. Tipos de Archivos Aceptados**
- **factura_pdf:** `.pdf` (max 10MB)
- **factura_xml:** `.xml` (max 5MB)
- **cotizacion:** `.pdf, .jpg, .png, .doc, .docx, .xls, .xlsx` (max 10MB)

### **3. Autenticación**
Todos los requests incluyen automáticamente:
```
Header: X-API-KEY: {{apiKey}}
```

---

## 📊 Respuestas Esperadas

### **✅ SPP Creada - Residente (201)**
```json
{
  "status": "SUCCESS",
  "code": 201,
  "message": "Solicitud de pago generada exitosamente.",
  "data": {
    "solicitud_pago": {
      "id": 150,
      "numero_folio_solicitud": "SP-2026-001-ABC",
      "estado_solicitud": "pendiente",
      "verificada": true,
      "tiene_factura": true,
      "monto_total": 25000.50
    },
    "proveedor": {
      "id": 1,
      "nombre_comercial": "Constructora XYZ",
      "rfc": "ABC123456XYZ"
    },
    "cuenta_bancaria": {
      "id": 1,
      "alias": "Cuenta Principal",
      "banco_nombre": "BBVA"
    }
  }
}
```

### **✅ SPP Auto-aprobada - Director (201)**
```json
{
  "status": "SUCCESS",
  "code": 201,
  "data": {
    "solicitud_pago": {
      "estado_solicitud": "autorizada",
      "verificada": true,
      "dg": "autorizada",
      "dg_fecha": "2026-01-13 10:30:00",
      "fecha_aprobado": "2026-01-13 10:30:00"
    }
  }
}
```

### **✅ SPP Solo Cotización (201)**
```json
{
  "data": {
    "solicitud_pago": {
      "tiene_factura": false,
      "ruta_archivo_cotizacion": "cotizaciones/abc123.pdf",
      "ruta_archivo_factura_pdf": null,
      "ruta_archivo_factura_xml": null
    }
  }
}
```

### **❌ Error - Sin Archivos (422)**
```json
{
  "status": "ERROR",
  "code": 422,
  "message": "Error de validación",
  "errors": {
    "factura_pdf": ["El archivo PDF es obligatorio cuando no se adjunta cotización"]
  }
}
```

---

## 🧪 Casos de Prueba Cubiertos

### **Por Nivel de Usuario:**
- ✅ Residente (PENDIENTE)
- ✅ Director General (AUTORIZADA)
- ✅ Director Técnico (AUTORIZADA)
- ✅ Director Administrativo (DIRECTO A PAGO)
- ✅ Programación y Control (AUTORIZADA)

### **Por Tipo de Archivos:**
- ✅ Con factura PDF + XML
- ✅ Con factura PDF + XML + cotización
- ✅ Solo con cotización (sin factura)

### **Por Flujo:**
- ✅ Proveedor existente → SPP
- ✅ Crear proveedor + cuenta + SPP en una operación

---

## 🔄 Comparación de Flujos

| Aspecto | FLUJO 1 (Existente) | FLUJO 2 (Crear Todo) |
|---------|---------------------|----------------------|
| **Endpoint** | `/proveedor/{id}/solicitudes-pago` | `/solicitud-pago/generar-spp-construcc` |
| **Proveedor** | Debe existir (tipo_alta=2) | Se crea nuevo |
| **Cuenta** | Debe existir | Se crea nueva |
| **SPP** | Se crea | Se crea |
| **Validación** | Proveedor debe ser tipo_alta=2 | RFC/email/teléfono no deben existir |
| **Campos** | SPP + archivos | Proveedor + Cuenta + SPP + archivos |

---

## 📋 Campos Requeridos vs Opcionales

### **FLUJO 1: Proveedor Existente**

**Requeridos:**
- `descripcion_concepto` ✅
- `monto_total` ✅
- `cuenta_bancaria_id` ✅
- `empresa_construcc_id` ✅
- `usuario_id` ✅
- `usuario_nombre` ✅
- `nivel_id` ✅
- `factura_pdf` + `factura_xml` **O** `cotizacion` ✅

**Opcionales:**
- `observaciones`
- `obra_id`, `tipo`, `tipo_id`, `notas`, `utilizara`, `equipo`, `equipo_id`

---

### **FLUJO 2: Crear Todo**

**Requeridos - Proveedor:**
- `proveedor_rfc` ✅
- `proveedor_razon_social` ✅
- `proveedor_nombre_comercial` ✅
- `proveedor_email` ✅
- `proveedor_telefono` ✅

**Requeridos - Cuenta:**
- `cuenta_bancaria_alias` ✅
- `cuenta_bancaria_banco_clave` ✅
- `cuenta_bancaria_banco_nombre` ✅
- `cuenta_bancaria_tipo_cuenta` ✅
- `cuenta_bancaria_campo_dependiente` ✅
- `cuenta_bancaria_titular_cuenta` ✅

**Requeridos - SPP:**
- `descripcion_concepto` ✅
- `monto_total` ✅
- Archivos (misma regla condicional)

**Opcionales:**
- `proveedor_celular`
- `cuenta_bancaria_referencia`, `sucursal`, `swift`
- Todos los campos adicionales de SPP

---

## 🎯 Ejemplos de Uso

### **Ejemplo 1: SPP Normal con Factura**
```javascript
Request: POST /construcc/proveedor/1/solicitudes-pago

Body:
- descripcion_concepto: "Materiales de construcción"
- monto_total: 25000.50
- factura_pdf: [archivo] ✅
- factura_xml: [archivo] ✅
- cuenta_bancaria_id: 1
- nivel_id: 6

Resultado:
{
  "solicitud_pago": {
    "estado_solicitud": "pendiente",
    "tiene_factura": true,
    "verificada": true
  }
}
```

---

### **Ejemplo 2: SPP Solo Cotización** ✨
```javascript
Request: POST /construcc/proveedor/1/solicitudes-pago

Body:
- descripcion_concepto: "Anticipo materiales"
- monto_total: 12000.00
- cotizacion: [archivo] ✅
- (SIN factura_pdf)
- (SIN factura_xml)
- nivel_id: 6

Resultado:
{
  "solicitud_pago": {
    "tiene_factura": false,
    "ruta_archivo_cotizacion": "cotizaciones/abc.pdf"
  }
}
```

---

### **Ejemplo 3: Director Auto-aprueba** ⚡
```javascript
Request: POST /construcc/proveedor/1/solicitudes-pago

Body:
- nivel_id: 1 (Director General)
- (resto de campos...)

Resultado:
{
  "solicitud_pago": {
    "estado_solicitud": "autorizada", ✅
    "verificada": true,
    "dg": "autorizada",
    "dg_fecha": "2026-01-13 10:30:00",
    "fecha_aprobado": "2026-01-13 10:30:00"
  }
}
```

---

### **Ejemplo 4: Crear Proveedor + SPP** 🚀
```javascript
Request: POST /construcc/solicitud-pago/generar-spp-construcc

Body:
- proveedor_rfc: "NPR240101XYZ"
- proveedor_razon_social: "Nuevo Proveedor SA"
- proveedor_email: "nuevo@test.com"
- proveedor_telefono: "5599887766"
- cuenta_bancaria_alias: "Cuenta Principal"
- cuenta_bancaria_banco_nombre: "BBVA"
- (datos de cuenta bancaria...)
- (datos de SPP...)
- nivel_id: 6

Resultado:
{
  "proveedor": { "id": 25, "tipo_alta": 2 }, ← Creado
  "cuenta_bancaria": { "id": 50, "preferida": true }, ← Creada
  "solicitud_pago": { "id": 150, "estado": "pendiente" } ← Creada
}
```

---

## 🔧 Configuración de Archivos

### En Postman:
1. Abre el request
2. Pestaña **"Body"**
3. Ya está en modo **"form-data"**
4. Para cada campo de archivo:
   - Click en **"Select Files"**
   - Navega a tu carpeta de pruebas
   - Selecciona el archivo

### Archivos de Prueba:
```
📁 C:/repositorio/app/api-proveedores/.vscode/http/tests/facturas/
  ├── Cfdi-4539.pdf  ← Para factura_pdf
  ├── Cfdi-4539.xml  ← Para factura_xml
  └── [cualquier archivo válido] ← Para cotizacion
```

---

## 📊 Códigos de Respuesta

| Código | Significado | Cuándo Ocurre |
|--------|-------------|---------------|
| 201 | Created | ✅ SPP creada exitosamente |
| 403 | Forbidden | ❌ Proveedor no es tipo_alta=2 |
| 404 | Not Found | ❌ Proveedor no existe |
| 409 | Conflict | ❌ Proveedor duplicado (FLUJO 2) |
| 422 | Validation Error | ❌ Archivos faltantes, cuenta inválida, etc. |
| 500 | Server Error | ❌ Error interno |

---

## ✅ Checklist de Pruebas

### **Pruebas Básicas:**
- [ ] SPP con proveedor existente + factura completa
- [ ] SPP con proveedor existente + solo cotización
- [ ] SPP creando proveedor nuevo + factura
- [ ] SPP creando proveedor nuevo + solo cotización

### **Pruebas por Nivel:**
- [ ] Residente → Estado PENDIENTE
- [ ] Director General → Estado AUTORIZADA
- [ ] Director Administrativo → Directo a pago
- [ ] Programación y Control → Estado AUTORIZADA

### **Pruebas de Validación:**
- [ ] Sin archivos → Error 422
- [ ] Cuenta bancaria inválida → Error 422
- [ ] Proveedor no tipo_alta=2 → Error 403
- [ ] RFC duplicado (FLUJO 2) → Error 409

---

## 🆘 Resolución de Problemas

### **Error: "The API key is missing or invalid"**
✅ Verifica que la variable `apiKey` esté configurada correctamente

### **Error 403: "Proveedor no es tipo_alta=2"**
✅ Usa el FLUJO 2 para crear un proveedor nuevo con tipo_alta=2

### **Error 422: "Factura es obligatoria"**
✅ Adjunta factura_pdf + factura_xml **O** adjunta cotización

### **Error 422: "Cuenta bancaria no pertenece al proveedor"**
✅ Verifica que el `cuenta_bancaria_id` sea del proveedor correcto

### **Archivos no se adjuntan**
✅ Asegúrate de seleccionar manualmente los archivos en cada request

---

## 📈 Estadísticas de la Colección

- 📦 **10 requests** de SPP
- 🎯 **2 endpoints** diferentes
- 👥 **7 niveles** de usuario cubiertos
- 📁 **3 tipos** de validación de archivos
- ✨ **7 campos** adicionales incluidos

---

## 🚀 Listo para Usar

✅ Importa el archivo JSON  
✅ Configura las variables (si es necesario)  
✅ Selecciona los archivos en cada request  
✅ ¡Comienza a probar!

---

**Archivo:** `Postman_SPP_Construcc.json`  
**Versión:** 1.0  
**Fecha:** Enero 13, 2026  
**Rutas:** Solo Solicitudes de Pago 💰
