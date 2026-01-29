# Sistema de Pagos SPP por Proveedor

**Fecha de implementación:** 28 de Enero de 2026

## Descripción General

Este sistema permite gestionar las Solicitudes de Pago (SPP) y sus pagos parciales desde la perspectiva del proveedor. La estructura de rutas está organizada jerárquicamente:

```
Proveedor
  └── SPP (Solicitudes de Pago)
       └── Pagos Parciales
```

---

## Estructura de Rutas

Todas las rutas están bajo el prefijo `/api/construcc/proveedor/{id_proveedor}/`

### 1. Listar SPP del Proveedor
```http
GET /api/construcc/proveedor/{id_proveedor}/spp
```

**Descripción:** Lista todas las solicitudes de pago de un proveedor específico.

**Parámetros de URL:**
- `id_proveedor`: ID del proveedor

**Query Parameters:**
- `page`: Número de página (default: 1)
- `per_page`: Elementos por página (default: 20)
- Filtros de SolicitudPago (search, estado_solicitud, etc.)

**Respuesta:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "numero_folio_solicitud": "SP-001",
      "descripcion_concepto": "Materiales de construcción",
      "monto_total": 50000.00,
      "monto_abonado": 25000.00,
      "saldo_pendiente": 25000.00,
      "pago_completo": false,
      "pagos": [
        {
          "id": 1,
          "referencia_pago": "REF-001",
          "monto_total": 25000.00,
          "pivot": {
            "monto_aplicado": 25000.00,
            "estado_pago": "aplicado",
            "notas": "Pago parcial 50%",
            "fecha_aplicacion": "2026-01-28 10:00:00"
          }
        }
      ]
    }
  ],
  "pagination": {
    "total": 100,
    "per_page": 20,
    "current_page": 1,
    "last_page": 5
  },
  "proveedor": {
    "id": 1,
    "nombre_comercial": "Proveedor ABC",
    "rfc": "ABC123456"
  }
}
```

**Controlador:** `ConstruccSPPSolicitudPagoController@index`

---

### 2. Ver SPP Específica con sus Pagos
```http
GET /api/construcc/proveedor/{id_proveedor}/spp/{id_spp}
```

**Descripción:** Muestra una solicitud de pago específica con todos sus pagos parciales y un resumen.

**Parámetros de URL:**
- `id_proveedor`: ID del proveedor
- `id_spp`: ID de la solicitud de pago

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "numero_folio_solicitud": "SP-001",
    "descripcion_concepto": "Materiales de construcción",
    "monto_total": 50000.00,
    "monto_abonado": 25000.00,
    "saldo_pendiente": 25000.00,
    "pago_completo": false,
    "proveedor": { /* datos del proveedor */ },
    "empresaConstrucc": { /* datos de la empresa */ },
    "cuentasBancarias": [ /* cuentas bancarias */ ],
    "pagos": [
      {
        "id": 1,
        "comprobante_pago": "comprobantes_pago/abc123.pdf",
        "fecha_pago": "2026-01-28 10:00:00",
        "referencia_pago": "REF-001",
        "monto_total": 25000.00,
        "pivot": {
          "monto_aplicado": 25000.00,
          "estado_pago": "aplicado",
          "notas": "Pago parcial 50%",
          "fecha_aplicacion": "2026-01-28 10:00:00"
        }
      }
    ]
  },
  "resumen_pagos": {
    "total_pagado": 25000.00,
    "cantidad_pagos": 1,
    "monto_total": 50000.00,
    "saldo_pendiente": 25000.00,
    "pago_completo": false
  }
}
```

**Controlador:** `ConstruccSPPSolicitudPagoController@show`

---

### 3. Listar Pagos de una SPP
```http
GET /api/construcc/proveedor/{id_proveedor}/spp/{id_spp}/pagos
```

**Descripción:** Lista todos los pagos parciales aplicados a una solicitud de pago específica.

**Parámetros de URL:**
- `id_proveedor`: ID del proveedor
- `id_spp`: ID de la solicitud de pago

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
      "monto_total": 25000.00,
      "banco_pago": "BBVA",
      "banco_destino": "Santander",
      "pivot": {
        "monto_aplicado": 25000.00,
        "estado_pago": "aplicado",
        "notas": "Pago parcial 50%",
        "fecha_aplicacion": "2026-01-28 10:00:00"
      }
    },
    {
      "id": 2,
      "comprobante_pago": "comprobantes_pago/def456.pdf",
      "fecha_pago": "2026-01-29 14:00:00",
      "referencia_pago": "REF-002",
      "monto_total": 25000.00,
      "banco_pago": "BBVA",
      "banco_destino": "Santander",
      "pivot": {
        "monto_aplicado": 25000.00,
        "estado_pago": "completado",
        "notas": "Pago final 50%",
        "fecha_aplicacion": "2026-01-29 14:00:00"
      }
    }
  ],
  "solicitud_pago": {
    "id": 1,
    "numero_folio_solicitud": "SP-001",
    "monto_total": 50000.00,
    "monto_abonado": 50000.00,
    "saldo_pendiente": 0.00,
    "pago_completo": true
  }
}
```

**Controlador:** `ConstruccSPPSolicitudPagoController@pagos`

---

### 4. Ver Pago Específico de una SPP
```http
GET /api/construcc/proveedor/{id_proveedor}/spp/{id_spp}/pagos/{id_pago}
```

**Descripción:** Muestra los detalles de un pago específico aplicado a una SPP.

**Parámetros de URL:**
- `id_proveedor`: ID del proveedor
- `id_spp`: ID de la solicitud de pago
- `id_pago`: ID del pago

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "pago": {
      "id": 1,
      "comprobante_pago": "comprobantes_pago/abc123.pdf",
      "fecha_pago": "2026-01-28 10:00:00",
      "fecha_registro": "2026-01-28 09:00:00",
      "referencia_pago": "REF-001",
      "monto_total": 25000.00,
      "banco_pago": "BBVA",
      "cuenta_origen": "1234567890",
      "tipo_cuenta_origen": "Cheques",
      "banco_destino": "Santander",
      "cuenta_destino": "0987654321",
      "titular_cuenta_destino": "Proveedor ABC SA de CV",
      "observaciones": "Pago parcial de factura",
      "empresaConstrucc": { /* datos empresa */ }
    },
    "relacion": {
      "monto_aplicado": 25000.00,
      "estado_pago": "aplicado",
      "notas": "Pago parcial 50%",
      "fecha_aplicacion": "2026-01-28 10:00:00"
    }
  },
  "solicitud_pago": {
    "id": 1,
    "numero_folio_solicitud": "SP-001",
    "monto_total": 50000.00,
    "monto_abonado": 25000.00,
    "saldo_pendiente": 25000.00
  }
}
```

**Controlador:** `ConstruccSPPSolicitudPagoController@showPago`

---

### 5. Subir Comprobante de Pago
```http
POST /api/construcc/proveedor/{id_proveedor}/spp/{id_spp}/pagos/{id_pago}/subir-comprobante
```

**Descripción:** Sube o actualiza el comprobante de pago de un pago específico.

**Parámetros de URL:**
- `id_proveedor`: ID del proveedor
- `id_spp`: ID de la solicitud de pago
- `id_pago`: ID del pago

**Body (multipart/form-data):**
```json
{
  "comprobante_pago": "<archivo PDF, JPG, JPEG o PNG>"
}
```

**Validaciones:**
- ✅ Archivo obligatorio
- ✅ Formatos permitidos: PDF, JPG, JPEG, PNG
- ✅ Tamaño máximo: 10MB

**Respuesta:**
```json
{
  "success": true,
  "message": "Comprobante de pago subido exitosamente",
  "data": {
    "comprobante_pago": "comprobantes_pago/nuevo_comprobante.pdf",
    "comprobante_pago_url": "http://localhost/storage/comprobantes_pago/nuevo_comprobante.pdf"
  }
}
```

**Controlador:** `ConstruccSPPSolicitudPagoController@subirComprobante`

---

### 6. Registrar Pago para Múltiples SPP
```http
POST /api/construcc/proveedor/{id_proveedor}/pagos
```

**Descripción:** Registra un pago y lo aplica a una o varias solicitudes de pago del proveedor.

**Parámetros de URL:**
- `id_proveedor`: ID del proveedor

**Body (multipart/form-data):**
```json
{
  "comprobante_pago": "<archivo>",
  "fecha_pago": "2026-01-28",
  "referencia_pago": "REF-12345",
  "banco_pago": "BBVA",
  "cuenta_origen": "1234567890",
  "tipo_cuenta_origen": "Cheques",
  "clabe_interbancaria_origen": "012345678901234567",
  "banco_destino": "Santander",
  "cuenta_destino": "0987654321",
  "tipo_cuenta_destino": "Cheques",
  "clabe_interbancaria_destino": "098765432109876543",
  "titular_cuenta_destino": "Proveedor ABC SA de CV",
  "monto_total": 50000.00,
  "observaciones": "Pago de múltiples facturas",
  "empresa_construcc_id": 1,
  "usuario_registro_id": 1,
  "usuario_registro_nombre": "Juan Pérez",
  "solicitudes_pago": [
    {
      "solicitud_pago_id": 1,
      "monto_aplicado": 25000.00,
      "estado_pago": "aplicado",
      "notas": "Pago parcial SPP-001"
    },
    {
      "solicitud_pago_id": 2,
      "monto_aplicado": 25000.00,
      "estado_pago": "completado",
      "notas": "Pago completo SPP-002"
    }
  ]
}
```

**Validaciones:**
- ✅ `comprobante_pago`: Obligatorio (PDF, JPG, JPEG, PNG, máx 10MB)
- ✅ `fecha_pago`: Obligatoria
- ✅ `referencia_pago`: Obligatoria
- ✅ `monto_total`: Mayor a 0
- ✅ `solicitudes_pago`: Mínimo 1 SPP
- ✅ `solicitudes_pago.*.solicitud_pago_id`: Debe pertenecer al proveedor
- ✅ Suma de `montos_aplicados` no puede exceder `monto_total`
- ✅ Estados válidos: aplicado, pendiente, rechazado, parcial, completado

**Respuesta:**
```json
{
  "success": true,
  "message": "Pago registrado exitosamente",
  "data": {
    "id": 3,
    "comprobante_pago": "comprobantes_pago/xyz789.pdf",
    "fecha_pago": "2026-01-28 10:00:00",
    "referencia_pago": "REF-12345",
    "monto_total": 50000.00,
    "solicitudes_pago": [
      {
        "id": 1,
        "numero_folio_solicitud": "SP-001",
        "pivot": {
          "monto_aplicado": 25000.00,
          "estado_pago": "aplicado",
          "notas": "Pago parcial SPP-001"
        }
      },
      {
        "id": 2,
        "numero_folio_solicitud": "SP-002",
        "pivot": {
          "monto_aplicado": 25000.00,
          "estado_pago": "completado",
          "notas": "Pago completo SPP-002"
        }
      }
    ]
  }
}
```

**Controlador:** `ConstruccSPPSolicitudPagoController@registrarPago`

---

## Flujos de Uso

### Flujo 1: Consultar SPP de un Proveedor

```javascript
// 1. Listar todas las SPP del proveedor
const response = await axios.get('/api/construcc/proveedor/1/spp', {
  params: {
    per_page: 20,
    page: 1,
    estado_solicitud: 'PENDIENTE'
  },
  headers: {
    'X-API-Key': 'tu-api-key'
  }
});

// 2. Ver detalles de una SPP específica
const sppDetalle = await axios.get('/api/construcc/proveedor/1/spp/5', {
  headers: {
    'X-API-Key': 'tu-api-key'
  }
});

// 3. Ver los pagos de esa SPP
const pagos = await axios.get('/api/construcc/proveedor/1/spp/5/pagos', {
  headers: {
    'X-API-Key': 'tu-api-key'
  }
});
```

### Flujo 2: Registrar un Pago para Múltiples SPP

```javascript
const formData = new FormData();

// Datos del pago
formData.append('comprobante_pago', archivoComprobante);
formData.append('fecha_pago', '2026-01-28');
formData.append('referencia_pago', 'REF-12345');
formData.append('monto_total', 50000);
formData.append('banco_pago', 'BBVA');
formData.append('banco_destino', 'Santander');

// Primera SPP
formData.append('solicitudes_pago[0][solicitud_pago_id]', 1);
formData.append('solicitudes_pago[0][monto_aplicado]', 25000);
formData.append('solicitudes_pago[0][estado_pago]', 'aplicado');
formData.append('solicitudes_pago[0][notas]', 'Pago parcial');

// Segunda SPP
formData.append('solicitudes_pago[1][solicitud_pago_id]', 2);
formData.append('solicitudes_pago[1][monto_aplicado]', 25000);
formData.append('solicitudes_pago[1][estado_pago]', 'completado');
formData.append('solicitudes_pago[1][notas]', 'Pago completo');

const response = await axios.post('/api/construcc/proveedor/1/pagos', formData, {
  headers: {
    'Content-Type': 'multipart/form-data',
    'X-API-Key': 'tu-api-key'
  }
});
```

### Flujo 3: Actualizar Comprobante de un Pago

```javascript
const formData = new FormData();
formData.append('comprobante_pago', nuevoComprobante);

const response = await axios.post(
  '/api/construcc/proveedor/1/spp/5/pagos/3/subir-comprobante',
  formData,
  {
    headers: {
      'Content-Type': 'multipart/form-data',
      'X-API-Key': 'tu-api-key'
    }
  }
);
```

---

## Resumen de Rutas

| Método | Ruta | Función | Descripción |
|--------|------|---------|-------------|
| GET | `/api/construcc/proveedor/{id}/spp` | `index` | Lista SPP del proveedor |
| GET | `/api/construcc/proveedor/{id}/spp/{spp}` | `show` | Ver SPP con pagos |
| GET | `/api/construcc/proveedor/{id}/spp/{spp}/pagos` | `pagos` | Lista pagos de SPP |
| GET | `/api/construcc/proveedor/{id}/spp/{spp}/pagos/{pago}` | `showPago` | Ver pago específico |
| POST | `/api/construcc/proveedor/{id}/spp/{spp}/pagos/{pago}/subir-comprobante` | `subirComprobante` | Subir comprobante |
| POST | `/api/construcc/proveedor/{id}/pagos` | `registrarPago` | Registrar pago múltiple |

---

## Controlador

**Archivo:** `app/Http/Controllers/ConstruccSPPSolicitudPagoController.php`

**Métodos implementados:**
- ✅ `index($proveedorId, $request)` - Lista SPP del proveedor
- ✅ `show($proveedorId, $sppId)` - Muestra SPP con pagos
- ✅ `pagos($proveedorId, $sppId)` - Lista pagos de SPP
- ✅ `showPago($proveedorId, $sppId, $pagoId)` - Muestra pago específico
- ✅ `subirComprobante($request, $proveedorId, $sppId, $pagoId)` - Sube comprobante
- ✅ `registrarPago($request, $proveedorId)` - Registra pago para múltiples SPP

---

## Ventajas de esta Estructura

1. ✅ **Organización Jerárquica:** Las rutas reflejan la relación proveedor → SPP → pagos
2. ✅ **Seguridad:** Todas las consultas verifican que la SPP pertenezca al proveedor
3. ✅ **Flexibilidad:** Un pago puede aplicarse a múltiples SPP
4. ✅ **Trazabilidad:** Cada pago tiene su comprobante y relación detallada
5. ✅ **Resumen Automático:** Calcula automáticamente totales y saldos
6. ✅ **Auditoría:** Todas las rutas tienen middleware `audit`

---

## Notas Importantes

- 🔴 **Validación de Proveedor:** Todas las SPP deben pertenecer al proveedor especificado
- 🔴 **Comprobantes Únicos:** Cada pago tiene un comprobante único
- 🔴 **Actualización Automática:** Los saldos de las SPP se actualizan automáticamente
- 🔴 **Estados del Pago:** aplicado, pendiente, rechazado, parcial, completado
- 🔴 **Middleware Audit:** Todas las operaciones quedan registradas

---

**Implementado por:** IA Assistant  
**Fecha:** 28 de Enero de 2026  
**Versión:** 1.0.0
