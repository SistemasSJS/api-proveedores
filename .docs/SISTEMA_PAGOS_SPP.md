# Sistema de Pagos para Solicitudes de Pago (SPP)

## Descripción General

Este sistema permite gestionar pagos realizados a proveedores con una relación **muchos a muchos** entre pagos y solicitudes de pago:

- **Un pago puede aplicar a múltiples SPP**
- **Una SPP puede recibir múltiples pagos**

## Estructura de la Base de Datos

### Tabla: `pagos_spp`

Almacena los pagos realizados a proveedores.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID del pago |
| `comprobante_pago` | string(500) | Ruta del archivo del comprobante (único) |
| `fecha_pago` | timestamp | Fecha en que se realizó el pago |
| `fecha_registro` | timestamp | Fecha de registro en el sistema |
| `referencia_pago` | string(100) | Referencia o número de transacción |
| `banco_pago` | string(100) | Banco desde donde se realizó el pago |
| `cuenta_origen` | string(50) | Número de cuenta origen |
| `tipo_cuenta_origen` | string(50) | Tipo de cuenta origen |
| `clabe_interbancaria_origen` | string(18) | CLABE de la cuenta origen |
| `banco_destino` | string(100) | Banco del proveedor (destino) |
| `cuenta_destino` | string(50) | Número de cuenta destino |
| `tipo_cuenta_destino` | string(50) | Tipo de cuenta destino |
| `clabe_interbancaria_destino` | string(18) | CLABE de la cuenta destino |
| `titular_cuenta_destino` | string(255) | Titular de la cuenta destino |
| `monto_total` | decimal(15,2) | Monto total del pago |
| `observaciones` | text | Observaciones adicionales |
| `usuario_registro_id` | bigint | ID del usuario que registró el pago |
| `usuario_registro_nombre` | string(255) | Nombre del usuario que registró |
| `empresa_construcc_id` | bigint | Empresa constructora que realiza el pago |

### Tabla: `pago_solicitud_pago` (Pivot)

Tabla intermedia para la relación muchos a muchos.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID de la relación |
| `pago_spp_id` | bigint | ID del pago (FK) |
| `solicitud_pago_id` | bigint | ID de la solicitud de pago (FK) |
| `monto_aplicado` | decimal(15,2) | Monto aplicado a esta SPP |
| `estado_pago` | enum | Estado: aplicado, pendiente, rechazado, parcial, completado |
| `notas` | text | Notas específicas de la aplicación |
| `fecha_aplicacion` | timestamp | Fecha de aplicación del pago |

### Índices

- `comprobante_pago` (UNIQUE)
- `fecha_pago`
- `fecha_registro`
- `referencia_pago`
- `empresa_construcc_id`
- `pago_spp_id` + `solicitud_pago_id` (UNIQUE)

## Modelos

### PagoSPP

**Ubicación:** `app/Models/PagoSPP.php`

**Relaciones:**
- `solicitudesPago()`: BelongsToMany con SolicitudPago
- `empresaConstrucc()`: BelongsTo con EmpresaConstrucc

**Scopes:**
- `deEmpresa($empresaId)`: Filtra pagos por empresa
- `entreFechas($fechaInicio, $fechaFin)`: Filtra por rango de fechas
- `porReferencia($referencia)`: Filtra por referencia

**Métodos de negocio:**
- `montoTotalAplicado()`: Calcula el monto total aplicado
- `montoDisponible()`: Calcula el monto disponible
- `estaCompletamenteAplicado()`: Verifica si está completamente aplicado
- `aplicarASolicitudPago($solicitudPago, $montoAplicar, $estadoPago, $notas)`: Aplica el pago a una SPP

**Filtros disponibles:**
- `search`: Búsqueda global
- `referencia_pago`: Por referencia
- `fecha_pago`, `fecha_pago_desde`, `fecha_pago_hasta`: Por fecha de pago
- `fecha_registro`, `fecha_registro_desde`, `fecha_registro_hasta`: Por fecha de registro
- `banco_pago`, `banco_destino`: Por banco
- `empresa_construcc_id`: Por empresa constructora
- `usuario_registro_id`: Por usuario
- `monto_min`, `monto_max`: Por rango de montos

### PagoSolicitudPago (Pivot)

**Ubicación:** `app/Models/PagoSolicitudPago.php`

**Constantes de estados:**
- `ESTADO_APLICADO = 'aplicado'`
- `ESTADO_PENDIENTE = 'pendiente'`
- `ESTADO_RECHAZADO = 'rechazado'`
- `ESTADO_PARCIAL = 'parcial'`
- `ESTADO_COMPLETADO = 'completado'`

**Métodos:**
- `estaAplicado()`: Verifica si está aplicado
- `estaPendiente()`: Verifica si está pendiente
- `fueRechazado()`: Verifica si fue rechazado
- `esParcial()`: Verifica si es parcial
- `estaCompletado()`: Verifica si está completado
- `cambiarEstado($nuevoEstado, $notas)`: Cambia el estado

### SolicitudPago (Actualizado)

**Nueva relación agregada:**
- `pagos()`: BelongsToMany con PagoSPP

## Controlador

### ConstruccPagosSPPController

**Ubicación:** `app/Http/Controllers/ConstruccPagosSPPController.php`

#### Endpoints disponibles:

##### 1. Listar pagos (con filtros y paginación)
```
GET /api/construcc/pagos-spp
```

**Query Params:**
- `page`: Número de página
- `per_page`: Elementos por página (default: 20)
- Filtros del modelo (ver sección de filtros)

**Respuesta:**
```json
{
  "success": true,
  "data": [...],
  "pagination": {
    "total": 100,
    "per_page": 20,
    "current_page": 1,
    "last_page": 5
  }
}
```

##### 2. Ver un pago específico
```
GET /api/construcc/pagos-spp/{id}
```

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "comprobante_pago": "path/to/file.pdf",
    "fecha_pago": "2026-01-21 10:00:00",
    "referencia_pago": "REF-001",
    "monto_total": 50000.00,
    "solicitudes_pago": [
      {
        "id": 1,
        "numero_folio_solicitud": "SP-001",
        "pivot": {
          "monto_aplicado": 25000.00,
          "estado_pago": "aplicado",
          "fecha_aplicacion": "2026-01-21 10:00:00"
        }
      }
    ]
  }
}
```

##### 3. Registrar un nuevo pago
```
POST /api/construcc/pagos-spp
```

**Body (multipart/form-data):**
```json
{
  "comprobante_pago": "<archivo>",
  "fecha_pago": "2026-01-21",
  "referencia_pago": "REF-001",
  "banco_pago": "BBVA",
  "cuenta_origen": "1234567890",
  "tipo_cuenta_origen": "Cheques",
  "banco_destino": "Santander",
  "cuenta_destino": "0987654321",
  "titular_cuenta_destino": "Proveedor XYZ",
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
      "notas": "Pago parcial"
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
- El `comprobante_pago` es obligatorio y debe ser un archivo PDF, JPG, JPEG o PNG (máx 10MB)
- La `fecha_pago` es obligatoria
- La `referencia_pago` es obligatoria
- El `monto_total` debe ser mayor a 0
- Debe incluir al menos una solicitud de pago
- La suma de montos aplicados no puede exceder el monto total del pago
- Cada solicitud de pago debe existir en la base de datos

##### 4. Actualizar un pago
```
PUT /api/construcc/pagos-spp/{id}
```

**Body (multipart/form-data):**
Mismos campos que en crear, pero todos opcionales.

##### 5. Eliminar un pago
```
DELETE /api/construcc/pagos-spp/{id}
```

**Nota:** Esto eliminará también las relaciones en la tabla pivot (cascade).

##### 6. Descargar comprobante
```
GET /api/construcc/pagos-spp/{id}/comprobante/download
```

Descarga el archivo del comprobante de pago.

##### 7. Agregar una solicitud de pago a un pago existente
```
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

##### 8. Actualizar una relación pago-solicitud
```
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

##### 9. Eliminar una relación pago-solicitud
```
DELETE /api/construcc/pagos-spp/{pagoId}/solicitudes-pago/{solicitudPagoId}
```

**Nota:** Esto revertirá el monto abonado en la solicitud de pago.

##### 10. Estadísticas de pagos
```
GET /api/construcc/pagos-spp/estadisticas
```

**Query Params:**
- `empresa_construcc_id`: Filtrar por empresa (opcional)

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
        "empresa_construcc": {...}
      }
    ]
  }
}
```

## Rutas Registradas

Todas las rutas están en el grupo `construcc` con el prefijo `/api/construcc/pagos-spp`:

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

## Flujo de Uso Típico

### 1. Registrar un pago completo

```javascript
// 1. Preparar los datos del formulario
const formData = new FormData();
formData.append('comprobante_pago', archivoComprobante);
formData.append('fecha_pago', '2026-01-21');
formData.append('referencia_pago', 'REF-12345');
formData.append('monto_total', 100000.00);
formData.append('empresa_construcc_id', 1);

// 2. Agregar las solicitudes de pago
formData.append('solicitudes_pago[0][solicitud_pago_id]', 1);
formData.append('solicitudes_pago[0][monto_aplicado]', 50000.00);
formData.append('solicitudes_pago[0][estado_pago]', 'completado');

formData.append('solicitudes_pago[1][solicitud_pago_id]', 2);
formData.append('solicitudes_pago[1][monto_aplicado]', 50000.00);
formData.append('solicitudes_pago[1][estado_pago]', 'completado');

// 3. Enviar la petición
const response = await axios.post('/api/construcc/pagos-spp', formData, {
  headers: {
    'Content-Type': 'multipart/form-data'
  }
});
```

### 2. Consultar pagos con filtros

```javascript
const response = await axios.get('/api/construcc/pagos-spp', {
  params: {
    empresa_construcc_id: 1,
    fecha_pago_desde: '2026-01-01',
    fecha_pago_hasta: '2026-01-31',
    per_page: 20,
    page: 1
  }
});
```

### 3. Agregar una SPP adicional a un pago existente

```javascript
const response = await axios.post('/api/construcc/pagos-spp/1/solicitudes-pago', {
  solicitud_pago_id: 3,
  monto_aplicado: 15000.00,
  estado_pago: 'aplicado',
  notas: 'Pago adicional'
});
```

## Validaciones Automáticas

El sistema incluye las siguientes validaciones automáticas:

1. **Validación de monto disponible:** Al aplicar un pago a una SPP, verifica que haya monto disponible.
2. **Actualización de saldos:** Al aplicar un pago, actualiza automáticamente los campos `monto_abonado`, `saldo_pendiente` y `pago_completo` de la SPP.
3. **Validación de duplicados:** No permite aplicar el mismo pago dos veces a la misma SPP (constraint UNIQUE).
4. **Cascade delete:** Al eliminar un pago, elimina automáticamente las relaciones en la tabla pivot.

## Estados del Pago en la Relación

Los estados disponibles para la relación pago-solicitud son:

- **aplicado**: Pago aplicado correctamente
- **pendiente**: Pago registrado pero pendiente de aplicar
- **rechazado**: Pago rechazado
- **parcial**: Pago parcial aplicado
- **completado**: Pago completo de esta SPP

## Archivos Creados

### Migraciones
- `database/migrations/2026_01_21_162718_create_pagos_spp_table.php`
- `database/migrations/2026_01_21_162720_create_pago_solicitud_pago_table.php`

### Modelos
- `app/Models/PagoSPP.php`
- `app/Models/PagoSolicitudPago.php`
- `app/Models/SolicitudPago.php` (actualizado con relación `pagos()`)

### Controlador
- `app/Http/Controllers/ConstruccPagosSPPController.php`

### Resources
- `app/Http/Resources/Construcc/PagoSPPResource.php`

### Rutas
- `routes/segmented/construcc.php` (actualizado con nuevas rutas)

## Notas Importantes

1. **Comprobante único:** Cada pago debe tener un comprobante único. El sistema valida esto a nivel de base de datos.

2. **Obligatoriedad de relación:** Al registrar un pago, es **obligatorio** relacionarlo con al menos una SPP.

3. **Integridad referencial:** Las tablas tienen llaves foráneas con `onDelete('cascade')` para mantener la integridad.

4. **Conexión MySQL5:** Todas las tablas usan la conexión `mysql5` para mantener consistencia con las demás tablas del sistema.

5. **Auditoría:** Todas las rutas tienen el middleware `audit` activado para registrar las acciones.

## Próximos Pasos Recomendados

1. Crear notificaciones para avisar cuando se registre un pago
2. Implementar reportes de pagos por período
3. Agregar validación de saldos pendientes antes de aplicar pagos
4. Crear dashboard con estadísticas de pagos
5. Implementar exportación de reportes a Excel/PDF
