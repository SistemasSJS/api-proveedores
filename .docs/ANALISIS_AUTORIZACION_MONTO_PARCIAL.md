# 📊 ANÁLISIS: Autorización de Monto Parcial en Solicitudes de Pago

**Fecha de Análisis:** 21 de enero de 2026  
**Autor:** Equipo de Desarrollo Backend  
**Estado:** Implementación Parcial - Requiere Completar

---

## 📑 Tabla de Contenidos

1. [Estado Actual de la Implementación](#estado-actual-de-la-implementación)
2. [Campos en Base de Datos](#1-campos-en-base-de-datos)
3. [Sistema de Pagos Actual](#2-sistema-de-pagos-actual)
4. [Endpoints Existentes](#3-endpoint-de-autorización)
5. [Modelo SolicitudPago](#4-modelo-solicitudpago)
6. [Tabla de Pagos - Análisis](#tabla-de-pagos---análisis)
7. [Checklist de Implementación](#checklist-de-implementación-requerida)
8. [Conclusiones](#conclusiones)
9. [Recomendaciones](#recomendaciones)

---

## 🔍 ESTADO ACTUAL DE LA IMPLEMENTACIÓN

### 1. Campos en Base de Datos

#### ✅ YA IMPLEMENTADO (Parcialmente)

**Migración existente:** `2026_01_21_122753_update_table_sp_add_columns_monto_autorizado_notas_autorizacion.php`

```php
Schema::table('solicitudes_pago', function (Blueprint $table) {
    $table
        ->decimal('monto_autorizado', 10, 2)
        ->nullable()
        ->after('equipo_id');

    $table
        ->text('notas_autorizacion')
        ->nullable()
        ->after('monto_autorizado');
});
```

**Campos agregados:**
- ✅ `monto_autorizado` - DECIMAL(10,2) NULL
- ✅ `notas_autorizacion` - TEXT NULL

#### ❌ CAMPOS FALTANTES (Según Documento de Requerimientos)

Según el documento de especificación, se requieren los siguientes campos adicionales:

| Campo Requerido | Tipo | Estado | Observación |
|-----------------|------|--------|-------------|
| `monto_autorizado` | DECIMAL(10,2) | ✅ Existe | Ya implementado |
| `usuario_autorizo_parcial_id` | INT | ❌ Falta | Para auditoría |
| `usuario_autorizo_parcial_nombre` | VARCHAR(255) | ❌ Falta | Para auditoría |
| `motivo_autorizacion_parcial` | TEXT | ⚠️ Parcial | Existe como `notas_autorizacion` |
| `fecha_autorizacion_parcial` | DATETIME | ❌ Falta | Para trazabilidad |

#### 📝 Diferencias Clave

1. **Nombre del campo:** El documento requiere `motivo_autorizacion_parcial` pero se implementó como `notas_autorizacion`
2. **Trazabilidad incompleta:** Faltan los campos de auditoría (usuario, fecha)
3. **Impacto:** No se puede rastrear quién, cuándo y por qué autorizó un monto parcial

---

### 2. Sistema de Pagos Actual

#### 🔄 Implementación Actual en `solicitudes_pago`

El sistema maneja pagos con los siguientes campos:

```sql
-- Campos principales de pago
monto_total                         -- DECIMAL(10,2) - Monto total de la SP
monto_autorizado                    -- DECIMAL(10,2) - Monto autorizado (nuevo)
monto_pagado                        -- DECIMAL(10,2) - Último monto pagado
monto_abonado                       -- DECIMAL(10,2) - Suma acumulada de abonos
saldo_pendiente                     -- DECIMAL(10,2) - Monto restante
pago_completo                       -- BOOLEAN - Si está totalmente pagado

-- Campos de tracking del pago
fecha_pago                          -- DATETIME
ruta_archivo_comprobante_pago       -- VARCHAR
nombre_beneficiario_pago            -- VARCHAR
clave_rastreo_pago                  -- VARCHAR(50)
banco_pago                          -- VARCHAR(50)
fecha_comprobante_pago              -- DATETIME
notas_abono                         -- TEXT
```

#### ⚠️ PROBLEMA CRÍTICO ENCONTRADO

**Ubicación:** `ConstruccSolicitudPagoController.php` - Método `confirmarPago()` (línea 532-680)

**El código actual NO valida contra `monto_autorizado`:**

```php
// ❌ VALIDACIÓN FALTANTE
public function confirmarPago(SolicitudPagoConfirmarPagoRequest $request, SolicitudPago $solicitudPago)
{
    // ... código existente ...
    
    // Actualizar solicitud (línea 626-643)
    $solicitudPago->update([
        'monto_pagado' => $request->monto_pagado,  // ⚠️ No valida contra monto_autorizado
        'estado_solicitud' => $estadoFinal,
        // ...
    ]);
}
```

**Validación requerida:**

```php
// ✅ CÓDIGO NECESARIO
// Obtener el monto máximo permitido
$montoMaximoPermitido = $solicitudPago->monto_autorizado ?? $solicitudPago->monto_total;
$montoYaPagado = $solicitudPago->monto_abonado ?? 0;
$montoDisponible = $montoMaximoPermitido - $montoYaPagado;

// Validar que no se exceda el monto autorizado
if ($request->monto_pagado > $montoDisponible) {
    return $this->error(
        "La solicitud {$solicitudPago->numero_folio_solicitud} solo tiene autorizado " .
        "$$montoDisponible. No se pueden pagar $" . $request->monto_pagado,
        null,
        400
    );
}
```

#### 🎯 Lógica de Negocio del Sistema Actual

**Método `actualizarSaldos()` en modelo `SolicitudPago`:**

```php
public function actualizarSaldos($montoAbono)
{
    $nuevoMontoAbonado = $this->monto_abonado + $montoAbono;
    $nuevoSaldoPendiente = $this->monto_total - $nuevoMontoAbonado;  // ⚠️ Usa monto_total, no monto_autorizado
    $pagoCompleto = $nuevoSaldoPendiente <= 0;

    $this->update([
        'monto_abonado' => $nuevoMontoAbonado,
        'saldo_pendiente' => max(0, $nuevoSaldoPendiente),
        'pago_completo' => $pagoCompleto,
    ]);

    return $pagoCompleto;
}
```

**⚠️ Problema:** El cálculo de `saldo_pendiente` usa `monto_total` en lugar de `monto_autorizado`

**✅ Lógica corregida necesaria:**

```php
public function actualizarSaldos($montoAbono)
{
    $montoAutorizado = $this->monto_autorizado ?? $this->monto_total; // ✅ Usar monto autorizado
    $nuevoMontoAbonado = $this->monto_abonado + $montoAbono;
    $nuevoSaldoPendiente = $montoAutorizado - $nuevoMontoAbonado;  // ✅ Calcular contra autorizado
    $pagoCompleto = $nuevoSaldoPendiente <= 0;

    $this->update([
        'monto_abonado' => $nuevoMontoAbonado,
        'saldo_pendiente' => max(0, $nuevoSaldoPendiente),
        'pago_completo' => $pagoCompleto,
    ]);

    return $pagoCompleto;
}
```

---

### 3. Endpoint de Autorización

#### ✅ Endpoint Actual: Autorización Normal

**Ruta:** `POST /api/solicitudes-pago/{id}/autorizar`

**Código actual:**

```php
public function autorizar(SolicitudPagoAutorizarRequest $request, SolicitudPago $solicitudPago): JsonResponse
{
    $data = $request->validated();
    $rol = strtoupper(trim($data['rol']));

    // Mapeo de roles
    $rolMap = [
        'DG' => 'dg',
        'DT' => 'dt',
        'PC' => 'pc',
        'SI' => 'si',
    ];

    // Validaciones...
    
    // Autorizar
    $solicitudPago->update([
        $rolField => EstadoSolicitud::AUTORIZADA->value,
        $fechaField => now(),
        'estado_solicitud' => EstadoSP::AUTORIZADA->value,
    ]);

    return $this->success(
        new ConstruccSolicitudPagoResource($solicitudPago->fresh()),
        "Solicitud autorizada correctamente por {$rol}."
    );
}
```

**Request actual:** `SolicitudPagoAutorizarRequest`

```php
public function rules(): array
{
    return [
        'rol' => ['required', 'string', Rule::in(['DG', 'DT', 'PC', 'SI'])],
    ];
}
```

**Recibe:**
```json
{
  "rol": "DG"
}
```

#### ❌ ENDPOINT FALTANTE: Autorización Parcial

**Ruta requerida:** `POST /api/solicitudes-pago/{id}/autorizar-parcial`

**Request necesario:**
```json
{
  "rol": "DG",
  "monto_autorizado": 20000.00,
  "motivo": "Flujo de caja limitado en este período fiscal",
  "usuario_id": 123,
  "usuario_nombre": "Juan Pérez"
}
```

**Validaciones requeridas:**
1. ✅ `rol` - Debe ser uno de: DG, DT, PC, SI
2. ✅ `monto_autorizado` - Requerido, numérico, > 0
3. ✅ `monto_autorizado` - Debe ser <= `monto_total` de la SP
4. ✅ `motivo` - Requerido, string, mínimo 10 caracteres
5. ✅ `usuario_id` - Requerido, numérico
6. ✅ `usuario_nombre` - Requerido, string

**Lógica esperada:**
```php
public function autorizarParcial(SolicitudPagoAutorizarParcialRequest $request, SolicitudPago $solicitudPago)
{
    $data = $request->validated();
    
    // Validar que el monto autorizado sea menor o igual al total
    if ($data['monto_autorizado'] > $solicitudPago->monto_total) {
        return $this->error(
            'El monto autorizado no puede ser mayor al monto total de la solicitud.',
            null,
            400
        );
    }
    
    // Actualizar con autorización parcial
    $solicitudPago->update([
        $rolField => EstadoSolicitud::AUTORIZADA->value,
        $fechaField => now(),
        'estado_solicitud' => EstadoSP::AUTORIZADA->value,
        
        // Campos de autorización parcial
        'monto_autorizado' => $data['monto_autorizado'],
        'usuario_autorizo_parcial_id' => $data['usuario_id'],
        'usuario_autorizo_parcial_nombre' => $data['usuario_nombre'],
        'motivo_autorizacion_parcial' => $data['motivo'],
        'fecha_autorizacion_parcial' => now(),
    ]);
    
    return $this->success(
        new ConstruccSolicitudPagoResource($solicitudPago->fresh()),
        "Solicitud autorizada parcialmente por {$rol} con monto de $" . number_format($data['monto_autorizado'], 2)
    );
}
```

---

### 4. Modelo `SolicitudPago`

#### ⚠️ Campos NO Incluidos en `$fillable`

Los nuevos campos agregados en la migración **NO están** en el array `$fillable`:

```php
protected $fillable = [
    'numero_folio_solicitud',
    'folio_sp_consecutivo',
    // ... muchos campos ...
    'equipo_id',
    // ❌ FALTAN:
    // 'monto_autorizado',
    // 'notas_autorizacion',
    // 'usuario_autorizo_parcial_id',
    // 'usuario_autorizo_parcial_nombre',
    // 'motivo_autorizacion_parcial',
    // 'fecha_autorizacion_parcial',
];
```

**Acción requerida:** Agregar los campos al `$fillable` para poder actualizarlos mediante `update()` o `create()`

---

## 📋 TABLA DE PAGOS - ANÁLISIS

### ❌ NO EXISTE Tabla Separada de `pagos`

El sistema actual registra los pagos **directamente en la tabla `solicitudes_pago`** con los siguientes campos:

| Campo | Tipo | Propósito |
|-------|------|-----------|
| `monto_pagado` | DECIMAL(10,2) | Último monto pagado |
| `monto_abonado` | DECIMAL(10,2) | Suma acumulada de todos los pagos |
| `saldo_pendiente` | DECIMAL(10,2) | Monto restante por pagar |
| `pago_completo` | BOOLEAN | Indica si está completamente pagado |
| `fecha_pago` | DATETIME | Fecha del último pago |
| `ruta_archivo_comprobante_pago` | VARCHAR | Comprobante del pago |
| `nombre_beneficiario_pago` | VARCHAR | Beneficiario del pago |
| `clave_rastreo_pago` | VARCHAR(50) | Clave de rastreo bancario |
| `banco_pago` | VARCHAR(50) | Banco utilizado |
| `fecha_comprobante_pago` | DATETIME | Fecha en el comprobante |
| `notas_abono` | TEXT | Observaciones del pago |

### ⚠️ LIMITACIÓN IMPORTANTE

**Problema:** Este diseño solo permite registrar la información del **último pago realizado**. 

Si una SP tiene múltiples abonos:
- ✅ Se acumula el `monto_abonado` correctamente
- ❌ Se **sobrescribe** la información del comprobante anterior
- ❌ Se **pierde** el historial de transacciones
- ❌ No se puede auditar quién hizo cada pago
- ❌ No se puede consultar el detalle de cada abono

### 📊 Ejemplo del Problema

**Escenario:**
1. SP de $100,000 autorizada con $30,000
2. Primer pago: $15,000 (comprobante A)
3. Segundo pago: $15,000 (comprobante B)

**Estado en BD:**
```sql
monto_total = 100000
monto_autorizado = 30000
monto_abonado = 30000  ✅ Correcto
saldo_pendiente = 0    ✅ Correcto
monto_pagado = 15000   ⚠️ Solo del último pago
ruta_archivo_comprobante_pago = "comprobantes/B.pdf"  ⚠️ Se perdió el comprobante A
nombre_beneficiario_pago = "Beneficiario 2"  ⚠️ Se perdió info del primer pago
```

### ✅ SOLUCIÓN PROPUESTA: Tabla `pagos`

Si se requiere **historial completo** de transacciones, se debe crear:

```sql
CREATE TABLE pagos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    solicitud_pago_id BIGINT UNSIGNED NOT NULL,
    
    -- Datos del pago
    monto_pago DECIMAL(10,2) NOT NULL,
    fecha_pago DATETIME NOT NULL,
    
    -- Usuario que registra el pago
    usuario_id INT NOT NULL,
    usuario_nombre VARCHAR(255) NOT NULL,
    
    -- Comprobante
    comprobante_path VARCHAR(255),
    nombre_beneficiario VARCHAR(255),
    clave_rastreo VARCHAR(50),
    banco VARCHAR(50),
    fecha_comprobante DATETIME,
    
    -- Observaciones
    notas TEXT,
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (solicitud_pago_id) REFERENCES solicitudes_pago(id) ON DELETE CASCADE,
    INDEX idx_solicitud_pago (solicitud_pago_id),
    INDEX idx_fecha_pago (fecha_pago)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Modelo Laravel:**

```php
// app/Models/Pago.php
class Pago extends BaseModel
{
    protected $connection = 'mysql5';
    protected $table = 'pagos';
    
    protected $fillable = [
        'solicitud_pago_id',
        'monto_pago',
        'fecha_pago',
        'usuario_id',
        'usuario_nombre',
        'comprobante_path',
        'nombre_beneficiario',
        'clave_rastreo',
        'banco',
        'fecha_comprobante',
        'notas',
    ];
    
    protected $casts = [
        'monto_pago' => 'decimal:2',
        'fecha_pago' => 'datetime',
        'fecha_comprobante' => 'datetime',
    ];
    
    public function solicitudPago(): BelongsTo
    {
        return $this->belongsTo(SolicitudPago::class);
    }
}

// Actualizar modelo SolicitudPago
class SolicitudPago extends BaseModel
{
    // Agregar relación
    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }
    
    // Método helper para obtener total pagado
    public function getTotalPagadoAttribute(): float
    {
        return $this->pagos()->sum('monto_pago');
    }
}
```

### 🤔 Decisión Arquitectónica

**Opción A: Mantener diseño actual (un solo pago)**
- ✅ Más simple
- ✅ Sin cambios estructurales
- ❌ No hay historial
- ❌ Auditoría limitada
- **Usar si:** Solo se requiere saber el estado actual, no el historial

**Opción B: Crear tabla `pagos`**
- ✅ Historial completo
- ✅ Auditoría detallada
- ✅ Múltiples comprobantes
- ❌ Más complejo
- ❌ Requiere migración de datos existentes
- **Usar si:** Se requiere trazabilidad completa de cada transacción

---

## ✅ CHECKLIST DE IMPLEMENTACIÓN REQUERIDA

### 🗄️ Base de Datos

- [x] ~~`monto_autorizado` DECIMAL(10,2)~~ - **Ya existe**
- [x] ~~`notas_autorizacion` TEXT~~ - **Ya existe** (renombrar a `motivo_autorizacion_parcial`)
- [ ] `usuario_autorizo_parcial_id` INT - **FALTA**
- [ ] `usuario_autorizo_parcial_nombre` VARCHAR(255) - **FALTA**
- [ ] `fecha_autorizacion_parcial` DATETIME - **FALTA**
- [ ] Agregar campos al `$fillable` del modelo `SolicitudPago`
- [ ] Agregar campos al `$casts` del modelo (si aplica)

**Migración necesaria:**

```php
Schema::table('solicitudes_pago', function (Blueprint $table) {
    $table->integer('usuario_autorizo_parcial_id')
        ->nullable()
        ->after('notas_autorizacion')
        ->comment('ID del usuario que autorizó monto parcial');
        
    $table->string('usuario_autorizo_parcial_nombre', 255)
        ->nullable()
        ->after('usuario_autorizo_parcial_id')
        ->comment('Nombre del usuario que autorizó monto parcial');
        
    $table->datetime('fecha_autorizacion_parcial')
        ->nullable()
        ->after('usuario_autorizo_parcial_nombre')
        ->comment('Fecha y hora de la autorización parcial');
        
    // Opcional: renombrar notas_autorizacion a motivo_autorizacion_parcial
    $table->renameColumn('notas_autorizacion', 'motivo_autorizacion_parcial');
});
```

### 🔌 Backend - Endpoints

- [ ] Crear endpoint `POST /api/solicitudes-pago/{id}/autorizar-parcial`
- [ ] Crear `SolicitudPagoAutorizarParcialRequest` con validaciones:
  - Rol válido
  - `monto_autorizado` > 0
  - `monto_autorizado` <= `monto_total`
  - Motivo mínimo 10 caracteres
- [ ] Implementar método `autorizarParcial()` en `ConstruccSolicitudPagoController`
- [ ] Modificar método `confirmarPago()` para validar contra `monto_autorizado`
- [ ] Actualizar `ConstruccSolicitudPagoResource` para incluir nuevos campos

### 🧮 Lógica de Negocio

- [ ] Modificar `actualizarSaldos()` para usar `monto_autorizado` en lugar de `monto_total`
- [ ] Validar que no se pague más del `monto_autorizado`
- [ ] Actualizar condición de estado "PAGADO": `monto_abonado >= (monto_autorizado ?? monto_total)`
- [ ] Agregar validación en `confirmarPago()`:
  ```php
  $montoDisponible = ($solicitudPago->monto_autorizado ?? $solicitudPago->monto_total) 
                      - ($solicitudPago->monto_abonado ?? 0);
  if ($request->monto_pagado > $montoDisponible) {
      return $this->error('Excede monto autorizado');
  }
  ```

### 📊 Tabla de Pagos (Opcional pero Recomendado)

- [ ] **Evaluar** necesidad de tabla separada `pagos`
- [ ] Si se aprueba:
  - [ ] Crear migración para tabla `pagos`
  - [ ] Crear modelo `Pago`
  - [ ] Agregar relación `hasMany(Pago::class)` en `SolicitudPago`
  - [ ] Modificar `confirmarPago()` para crear registro en `pagos`
  - [ ] Mantener `monto_abonado` y `saldo_pendiente` como campos calculados
  - [ ] Migrar datos históricos si existen

### 🧪 Testing

- [ ] Test: Autorización normal (sin cambios)
- [ ] Test: Autorización parcial exitosa
- [ ] Test: Validar `monto_autorizado` > `monto_total` (debe fallar)
- [ ] Test: Validar `monto_autorizado` <= 0 (debe fallar)
- [ ] Test: Validar motivo vacío (debe fallar)
- [ ] Test: Validar motivo con menos de 10 caracteres (debe fallar)
- [ ] Test: Pagar más del monto autorizado (debe fallar)
- [ ] Test: Pagar exactamente el monto autorizado (debe pasar)
- [ ] Test: Abonos múltiples hasta completar monto autorizado
- [ ] Test: Estado cambia a PAGADO cuando `monto_abonado >= monto_autorizado`

### 📝 Documentación

- [ ] Documentar endpoint en Swagger/OpenAPI
- [ ] Actualizar Postman collection
- [ ] Crear ejemplos de uso
- [ ] Documentar casos de prueba
- [ ] Crear query de reporte de autorizaciones parciales

---

## 🎯 CONCLUSIONES

### 1. **Implementación Parcial**
La base de datos ya tiene 2 de los 5 campos necesarios (`monto_autorizado` y `notas_autorizacion`), pero **falta la trazabilidad completa**: quién autorizó, cuándo y por qué de forma estructurada.

### 2. **Validación Crítica Faltante** 🚨
El sistema actual **NO impide** pagar más del monto autorizado. Esto representa un **riesgo de negocio significativo** porque:
- Un usuario podría autorizar $20,000
- Pero se podría pagar $100,000 completos
- No hay validación que lo impida

### 3. **Endpoint Faltante**
No existe el endpoint `POST /solicitudes-pago/{id}/autorizar-parcial`. Actualmente solo se puede autorizar el monto completo a través de `POST /solicitudes-pago/{id}/autorizar`.

### 4. **Tabla de Pagos**
El diseño actual solo permite guardar información del **último pago**. Si se requiere:
- Historial completo de transacciones
- Múltiples comprobantes
- Auditoría detallada de cada abono

Se recomienda crear una tabla separada `pagos` con relación `hasMany`.

### 5. **Modelo Desactualizado**
Los campos `monto_autorizado` y `notas_autorizacion` no están en el `$fillable` del modelo `SolicitudPago`, lo que podría causar errores al intentar actualizarlos mediante `update()` o `create()`.

### 6. **Lógica de Negocio Incompleta**
El método `actualizarSaldos()` usa `monto_total` en lugar de `monto_autorizado` para calcular el saldo pendiente, lo cual es incorrecto cuando hay autorizaciones parciales.

---

## 💡 RECOMENDACIONES

### Prioridad Alta 🔴

1. **Agregar validación en `confirmarPago()`**
   - Impedir pagos que excedan `monto_autorizado`
   - Esta es la validación más crítica para evitar riesgos de negocio

2. **Completar campos de auditoría**
   - Agregar `usuario_autorizo_parcial_id`, `usuario_autorizo_parcial_nombre`, `fecha_autorizacion_parcial`
   - Actualizar `$fillable` del modelo

3. **Crear endpoint `autorizar-parcial`**
   - Implementar el endpoint nuevo con todas las validaciones
   - Crear el Request correspondiente

### Prioridad Media 🟡

4. **Corregir método `actualizarSaldos()`**
   - Usar `monto_autorizado` en lugar de `monto_total`
   - Actualizar lógica de estado PAGADO

5. **Actualizar Resource**
   - Incluir nuevos campos en las respuestas API
   - Documentar en Swagger

### Prioridad Baja 🟢

6. **Evaluar tabla `pagos` separada**
   - Si se requiere historial completo, implementar tabla separada
   - Si no, mantener diseño actual

7. **Testing completo**
   - Crear suite de pruebas para todos los casos de uso
   - Validar edge cases

---

## 📞 Siguiente Paso

Definir con el equipo:
1. ¿Se requiere historial completo de pagos o solo el estado actual?
2. ¿Se procede con la implementación de los campos faltantes?
3. ¿Cuál es la prioridad de implementación?

---

**Documento generado el:** 21 de enero de 2026  
**Versión:** 1.0  
**Revisar documento original:** `PLAN_TRABAJO_AUTORIZACION_PARCIAL_SPP.md`
