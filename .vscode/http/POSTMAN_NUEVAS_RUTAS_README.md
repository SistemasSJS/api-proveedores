# 🆕 Colección Postman - Nuevas Rutas Construcción

## 📦 Archivo

**`Postman_Nuevas_Rutas_Construcc.json`**

Colección compacta con **ÚNICAMENTE las nuevas rutas** implementadas para el módulo de proveedores de construcción.

---

## 🚀 Importar en Postman

### Pasos:
1. Abre **Postman**
2. Click en **"Import"**
3. Arrastra `Postman_Nuevas_Rutas_Construcc.json`
4. ¡Listo! ✅

---

## 📂 Estructura (15 Requests)

```
🆕 Nuevas Rutas - Construcción Proveedores
│
├── 🆕 PROVEEDORES tipo_alta=2 (5 requests)
│   ├── GET  - Listar Proveedores Construcción
│   ├── GET  - Detalle de Proveedor + Estadísticas
│   ├── POST - Crear Proveedor + Cuenta
│   ├── PUT  - Actualizar Proveedor + Cuentas
│   └── DEL  - Dar de Baja Proveedor
│
├── 🆕 CUENTAS BANCARIAS (5 requests)
│   ├── GET  - Listar Cuentas del Proveedor
│   ├── POST - Crear Cuenta Bancaria
│   ├── PUT  - Actualizar Cuenta
│   ├── POST - Marcar como Favorita
│   └── DEL  - Eliminar Cuenta
│
├── 🆕 SPP - Proveedor Existente (3 requests)
│   ├── POST - SPP Residente (PENDIENTE)
│   ├── POST - SPP Director (AUTORIZADA)
│   └── POST - SPP Solo con Cotización ✨ NUEVO
│
├── 🆕 SPP - Crear Todo en Uno (2 requests)
│   ├── POST - Proveedor + Cuenta + SPP (Residente)
│   └── POST - Proveedor + Cuenta + SPP (Director)
│
└── 🆕 Registro y Validación (3 requests)
    ├── POST - Registro Nuevo Proveedor
    ├── POST - Registro con tipo_alta=2
    └── POST - Completar Registro
```

---

## ⚙️ Variables Configuradas

| Variable | Valor | Editable |
|----------|-------|----------|
| `baseUrl` | `http://localhost:80/api` | ✅ Sí |
| `apiKey` | `7f2wnCyn7ctmTE7B3mrtDPKCPVF9z8pYseihsHA6` | ✅ Sí |
| `proveedorId` | `1` | ✅ Sí |
| `cuentaId` | `1` | ✅ Sí |

---

## 🎯 Rutas Nuevas Implementadas

### **Módulo 1: Proveedores Construcción**
```
GET    /construcc/proveedor
GET    /construcc/proveedor/{id}
POST   /construcc/proveedor
PUT    /construcc/proveedor/{id}
DELETE /construcc/proveedor/{id}
```

### **Módulo 2: Cuentas Bancarias**
```
GET    /construcc/proveedor/{proveedorId}/cuentas
POST   /construcc/proveedor/{proveedorId}/cuentas
PUT    /construcc/proveedor/{proveedorId}/cuentas/{id}
DELETE /construcc/proveedor/{proveedorId}/cuentas/{id}
POST   /construcc/proveedor/{proveedorId}/cuentas/{id}/set-favorita
```

### **Módulo 3: Solicitudes de Pago**
```
POST /construcc/proveedor/{proveedorId}/solicitudes-pago
POST /construcc/solicitud-pago/generar-spp-construcc
```

### **Módulo 4: Registro**
```
POST /auth/register_basico
POST /auth/completar-registro-proveedor
```

---

## ✨ Novedades Implementadas

### 1. **Validación Condicional de Archivos**
```
✅ Con cotización → Factura opcional
❌ Sin cotización → Factura obligatoria
```

### 2. **Campos Adicionales en SPP**
Todos los requests de SPP incluyen:
```json
{
  "obra_id": 5,
  "tipo": "Material",
  "tipo_id": 3,
  "notas": "Descripción...",
  "utilizara": "Construcción",
  "equipo": "Cuadrilla A",
  "equipo_id": 1
}
```

### 3. **Actualización Completa de Proveedor**
El `PUT` de proveedor ahora permite:
- Actualizar datos del proveedor
- Actualizar cuentas bancarias existentes
- Crear nuevas cuentas bancarias
- Retorna TODAS las cuentas en la respuesta

### 4. **Estadísticas en Detalle**
El `GET` de detalle incluye:
```json
{
  "estadisticas": {
    "total_solicitudes_pago": 10,
    "total_sp_pendientes": 3,
    "total_sp_autorizadas": 5,
    "total_sp_pagadas": 2,
    "monto_total_solicitado": 150000.00,
    "monto_total_pagado": 50000.00
  }
}
```

---

## 📝 Campos Nuevos Agregados

### En Proveedor:
- ✅ `celular` (string, max:20, nullable)

### En SPP:
- ✅ `obra_id` (integer, nullable)
- ✅ `tipo` (string, max:255, nullable)
- ✅ `tipo_id` (integer, nullable)
- ✅ `notas` (string, max:1000, nullable)
- ✅ `utilizara` (string, max:255, nullable)
- ✅ `equipo` (string, max:255, nullable)
- ✅ `equipo_id` (integer, nullable)

---

## 👥 Niveles de Usuario

| ID | Rol | Comportamiento |
|----|-----|----------------|
| 1 | Director General (DG) | ✅ Auto-aprueba SPP |
| 2 | Director Técnico (DT) | ✅ Auto-aprueba SPP |
| 3 | Director Administrativo (DA) | ⭐ Directo a pago |
| 5 | Programación y Control (PC) | ✅ Auto-aprueba SPP |
| 6 | Residente de Obra (RO) | ⏳ Requiere aprobación |

---

## 🧪 Flujos de Prueba

### **Flujo 1: Proveedor Existente → SPP**
```
1. Crear proveedor (POST /construcc/proveedor)
2. Ver detalle con estadísticas (GET /construcc/proveedor/{id})
3. Crear cuenta adicional (POST /cuentas)
4. Generar SPP (POST /solicitudes-pago)
```

### **Flujo 2: Todo en Una Operación**
```
1. Crear proveedor + cuenta + SPP 
   (POST /construcc/solicitud-pago/generar-spp-construcc)
2. Verificar que todo se creó correctamente
```

### **Flujo 3: Validación de Registro**
```
1. Intentar registro con teléfono tipo_alta=2
2. Completar registro con token temporal
3. Verificar sesión creada
```

---

## ⚠️ Importante - Archivos en Postman

Para requests con archivos (form-data):

1. Ve a la pestaña **"Body"**
2. Verás los campos ya configurados
3. **Debes seleccionar archivos manualmente:**
   - Click en "Select Files"
   - Navega a tus archivos de prueba
   - Selecciona el archivo

**Nota**: La validación permite:
- ✅ Factura PDF + XML (sin cotización)
- ✅ Solo cotización (sin factura)
- ❌ Sin ningún archivo → Error 422

---

## 🎯 Casos de Uso Principales

### **Caso 1: SPP Normal con Factura**
```
POST /construcc/proveedor/1/solicitudes-pago

✅ factura_pdf: archivo.pdf
✅ factura_xml: archivo.xml
✅ cotizacion: archivo.pdf (opcional)

Resultado: tiene_factura = true
```

### **Caso 2: SPP Solo con Cotización (Anticipo)**
```
POST /construcc/proveedor/1/solicitudes-pago

❌ factura_pdf: (sin archivo)
❌ factura_xml: (sin archivo)
✅ cotizacion: cotizacion.pdf

Resultado: tiene_factura = false
```

### **Caso 3: Director Auto-aprueba**
```
POST /construcc/proveedor/1/solicitudes-pago

nivel_id: 1 (Director General)

Resultado: 
- estado_solicitud: "autorizada"
- verificada: true
- dg: "autorizada"
- dg_fecha: "2026-01-13..."
```

---

## 📊 Respuestas Esperadas

### **✅ Crear Proveedor (201)**
```json
{
  "status": "SUCCESS",
  "code": 201,
  "message": "Proveedor creado exitosamente.",
  "data": {
    "proveedor": {
      "id": 25,
      "razon_social": "Constructora SA",
      "rfc": "CEJ850101ABC",
      "tipo_alta": 2
    },
    "cuenta_bancaria": {
      "id": 50,
      "alias": "Cuenta Principal",
      "preferida": true
    }
  }
}
```

### **✅ SPP Generada (201)**
```json
{
  "status": "SUCCESS",
  "code": 201,
  "data": {
    "solicitud_pago": {
      "id": 150,
      "estado_solicitud": "pendiente",
      "verificada": true,
      "tiene_factura": true
    },
    "proveedor": {...},
    "cuenta_bancaria": {...}
  }
}
```

### **❌ RFC Duplicado (409)**
```json
{
  "status": "ERROR",
  "code": 409,
  "message": "El RFC ya está registrado."
}
```

---

## 🔧 Configuración Rápida

### Para Producción:
```json
"baseUrl": "https://apicons.ddns.net:8092/api"
```

### Para Local:
```json
"baseUrl": "http://localhost:80/api"
```

---

## 📋 Checklist de Pruebas

- [ ] Crear proveedor con cuenta
- [ ] Listar proveedores
- [ ] Ver detalle con estadísticas
- [ ] Actualizar proveedor y cuentas
- [ ] Crear cuenta adicional
- [ ] Generar SPP con factura
- [ ] Generar SPP solo con cotización
- [ ] Generar SPP auto-aprobada (director)
- [ ] Crear proveedor + cuenta + SPP completo
- [ ] Probar flujo de registro validación

---

## ✅ Verificación

- ✅ 15 requests configurados
- ✅ 4 variables de entorno
- ✅ Campos adicionales incluidos
- ✅ Validación condicional implementada
- ✅ Todos los niveles de usuario cubiertos

---

**Última actualización**: Enero 13, 2026  
**Versión**: 1.0  
**Archivo**: `Postman_Nuevas_Rutas_Construcc.json`
