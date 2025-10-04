# Ejemplos Prácticos - Sistema de Autorizaciones SP

## 🎯 Caso de Uso Completo: Flujo de SP con Pagos Parciales

### Escenario
Proveedor "Materiales XYZ" crea una SP por $10,000.00 pesos para compra de cemento.

---

## 📝 Paso 1: Creación de SP por Proveedor

```http
POST /api/proveedores/123/solicitudes-pago
Content-Type: multipart/form-data

{
  "descripcion_concepto": "Suministro de cemento para construcción",
  "factura_pdf": [archivo],
  "factura_xml": [archivo], 
  "cotizacion_pdf": [archivo],    // NUEVO - Opcional
  "cotizacion_xml": [archivo],    // NUEVO - Opcional
  "monto_total": 10000.00,
  "residente": "Ing. Juan Pérez",
  "empresa": "Constructora ABC"
}
```

**Respuesta:**
```json
{
  "status": "SUCCESS",
  "message": "Solicitud de pago creada correctamente.",
  "data": {
    "id": 501,
    "numero_folio_solicitud": "SP000501",
    "estado_solicitud": "pendiente",
    "monto_total": 10000.00,
    "saldo_pendiente": 10000.00,
    "monto_abonado": 0.00,
    "pago_completo": false,
    "dg": false, "dt": false, "pc": false, "si": false, "da": false
  }
}
```

---

## 👨‍💼 Paso 2: Listado por Rol DG

```http
GET /api/construcc/solicitudes-pago/por-rol?rol=DG
```

**Respuesta:**
```json
{
  "status": "SUCCESS", 
  "message": "Solicitudes para rol DG",
  "data": [
    {
      "id": 501,
      "numero_folio_solicitud": "SP000501", 
      "estado_solicitud": "pendiente",
      "monto_total": 10000.00,
      "saldo_pendiente": 10000.00,
      // ... otros campos
    }
    // ... más SP pendientes
  ]
}
```

---

## ✅ Paso 3: Autorización por DG

```http
POST /api/construcc/solicitudes-pago/501/autorizar
Content-Type: application/json

{
  "rol": "DG"
}
```

**Respuesta:**
```json
{
  "status": "SUCCESS",
  "message": "Solicitud autorizada correctamente por DG.",
  "data": {
    "id": 501,
    "estado_solicitud": "autorizada",  // ✅ Cambió a AUTORIZADA
    "dg": true,                        // ✅ DG autorizó
    "dg_fecha": "2025-10-04 14:30:00",
    "dt": true, "pc": true, "si": true  // ✅ Auto-autorizadas por DG
  }
}
```

> **Nota:** Cuando DG autoriza, automáticamente se considera autorizada por todos los roles requeridos.

---

## 💰 Paso 4: DA Lista Solicitudes para Pago

```http
GET /api/construcc/solicitudes-pago/por-rol?rol=DA
```

**Respuesta:**
```json
{
  "status": "SUCCESS",
  "message": "Solicitudes para rol DA", 
  "data": [
    {
      "id": 501,
      "estado_solicitud": "autorizada",  // ✅ Lista para pago
      "saldo_pendiente": 10000.00
    }
  ]
}
```

---

## 💳 Paso 5: Primer Pago Parcial por DA

```http
POST /api/construcc/solicitudes-pago/501/confirmar-pago
Content-Type: multipart/form-data

{
  "comprobante": [archivo_comprobante.pdf],
  "monto_abono": 4000.00,
  "notas_abono": "Primer abono - 40% del total"
}
```

**Respuesta:**
```json
{
  "status": "SUCCESS",
  "message": "Abono registrado correctamente. Saldo pendiente: 6000.00",
  "data": {
    "id": 501,
    "estado_solicitud": "autorizada",    // ⚠️ Sigue AUTORIZADA (pago parcial)
    "monto_total": 10000.00,
    "monto_abonado": 4000.00,           // ✅ Acumulado
    "saldo_pendiente": 6000.00,         // ✅ Calculado automáticamente  
    "pago_completo": false,             // ❌ Aún no completo
    "porcentaje_pagado": 40.00,         // ✅ 40%
    "notas_abono": "Primer abono - 40% del total",
    "da": true,                         // ✅ DA procesó
    "da_fecha": "2025-10-04 15:15:00"
  }
}
```

---

## 💳 Paso 6: Segundo Pago Parcial 

Después de unos días, DA realiza otro abono:

```http
POST /api/construcc/solicitudes-pago/501/confirmar-pago
Content-Type: multipart/form-data

{
  "comprobante": [archivo_comprobante_2.pdf],
  "monto_abono": 3500.00,
  "notas_abono": "Segundo abono - Quedan $2,500"
}
```

**Respuesta:**
```json
{
  "status": "SUCCESS", 
  "message": "Abono registrado correctamente. Saldo pendiente: 2500.00",
  "data": {
    "id": 501,
    "monto_abonado": 7500.00,           // 4000 + 3500
    "saldo_pendiente": 2500.00,         // 10000 - 7500
    "porcentaje_pagado": 75.00,         // 75%
    "pago_completo": false              // Aún no completo
  }
}
```

---

## 💳 Paso 7: Pago Final Completo

```http
POST /api/construcc/solicitudes-pago/501/confirmar-pago
Content-Type: multipart/form-data

{
  "comprobante": [archivo_comprobante_final.pdf],
  "monto_abono": 2500.00,
  "notas_abono": "Pago final - Solicitud completada"
}
```

**Respuesta:**
```json
{
  "status": "SUCCESS",
  "message": "Pago completado correctamente. La solicitud ha sido pagada en su totalidad.",
  "data": {
    "id": 501,
    "estado_solicitud": "pagado",       // ✅ Ahora está PAGADO
    "monto_abonado": 10000.00,          // Total pagado
    "saldo_pendiente": 0.00,            // Sin saldo
    "pago_completo": true,              // ✅ Completado
    "porcentaje_pagado": 100.00,        // 100%
    "da": 3,                            // Estado DA = PAGADO (3)
    "da_fecha": "2025-10-04 16:45:00"
  }
}
```

---

## 📊 Estadísticas Durante el Proceso

### Para Rol DA después del primer abono:

```http
GET /api/construcc/solicitudes-pago/estadisticas-rol?rol=DA
```

**Respuesta:**
```json
{
  "status": "SUCCESS",
  "data": {
    "pendientes": 5,                    // SP autorizadas sin pagar
    "con_pagos_parciales": 1,           // SP con abonos parciales
    "pagadas_completas": 12,            // SP pagadas al 100%
    "monto_total_autorizado": 45000.00, // Total pendiente de pago
    "monto_total_pagado": 125000.00     // Total pagado histórico
  }
}
```

---

## 🎯 Otros Casos de Uso

### Caso 1: Autorización por Múltiples Roles

Si **DT** autoriza primero (en lugar de DG):

```http
POST /api/construcc/solicitudes-pago/502/autorizar
Body: { "rol": "DT" }

# Resultado: 
# - estado_solicitud = "pendiente" (sin cambio)
# - dt = true, dt_fecha = now()
# - Otros roles aún pueden ver la SP en sus listados
```

### Caso 2: Rechazo por Cualquier Rol

```http 
POST /api/construcc/solicitudes-pago/503/rechazar
Body: { 
  "rol": "SI", 
  "motivo_rechazo": "Documentación incompleta"
}

# Resultado:
# - estado_solicitud = "rechazada"
# - si = 2 (RECHAZADA)
# - SP ya no aparece en listados de ningún rol
```

### Caso 3: Error en Abono Excesivo

```http
POST /api/construcc/solicitudes-pago/501/confirmar-pago
Body: { 
  "monto_abono": 15000.00,  // Mayor al saldo pendiente
  "comprobante": [archivo]
}

# Respuesta ERROR:
{
  "status": "ERROR",
  "message": "El monto del abono (15000) no puede ser mayor al saldo pendiente (6000).",
  "code": 400
}
```

---

## 🎯 Ventajas del Sistema

### ✅ **Para los Usuarios:**
- **Listados inteligentes**: Cada rol ve solo lo que necesita procesar
- **Pagos flexibles**: Abonos parciales según disponibilidad
- **Seguimiento claro**: Porcentajes y saldos automáticos
- **Trazabilidad completa**: Historial de cada acción por rol

### ✅ **Para el Negocio:**
- **Flujo de efectivo controlado**: Pagos parciales planificados
- **Responsabilidades claras**: Cada rol tiene funciones específicas
- **Auditoría completa**: Registro de fechas y responsables
- **Reducción de errores**: Validaciones automáticas de montos

### ✅ **Para Desarrollo:**
- **APIs RESTful**: Endpoints claros y consistentes
- **Validaciones robustas**: Controles de negocio implementados
- **Extensible**: Fácil agregar nuevos roles o funcionalidades
- **Documentado**: Respuestas claras y códigos de error específicos

---

*Ejemplos actualizados el 04 de Octubre de 2025*