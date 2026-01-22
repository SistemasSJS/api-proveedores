# HEVENTEC
## Plan de Trabajo: Autorización de Monto Parcial en SPP
**Fecha:** 21-01-2026  
**Proyecto:** GestionPro  
**Feature:** Autorizar monto parcial en Solicitudes de Pago para Proveedores (SPP)

---

| ☐ | Aplicación | Proyecto ó Issue | Back o Front | Tiem (H) | Actividad |
|---|------------|------------------|--------------|----------|-----------|
| ☐ | GestionPro | Autorización de un monto parcial en SPP | Back | 0.5 | Generar migración para actualizar la tabla `solicitudes_pago` y agregar los campos: `monto_autorizado`, `usuario_autorizo_parcial_id`, `usuario_autorizo_parcial_nombre`, `motivo_autorizacion_parcial`, `fecha_autorizacion_parcial`. |
| ☐ | GestionPro | Autorización de un monto parcial en SPP | Back | 0.5 | Actualizar modelo `SolicitudPago.php` agregando los 5 nuevos campos al array `$fillable` y configurar `$casts` para tipos de datos correctos (decimal, integer, datetime). |
| ☐ | GestionPro | Autorización de un monto parcial en SPP | Back | 0.5 | Crear accessors en modelo `SolicitudPago.php`: `getMontoDisponibleParaPagarAttribute()`, `getMontoAutorizadoEfectivoAttribute()`, `getEsAutorizacionParcialAttribute()`, `getMontoPendienteTotalAttribute()`. |
| ☐ | GestionPro | Autorización de un monto parcial en SPP | Back | 0.5 | Agregar relación `usuarioAutorizoParcial()` en modelo `SolicitudPago.php` con `belongsTo(User::class)`. |
| ☐ | GestionPro | Autorización de un monto parcial en SPP | Back | 0.5 | Crear 8 scopes en modelo `SolicitudPago.php`: `scopeConAutorizacionParcial()`, `scopeConAutorizacionCompleta()`, `scopeAutorizadoMenorQue()`, `scopeAutorizadoParcialmentePorUsuario()`, `scopeAutorizacionParcialEntreFechas()`, `scopeConSaldoDisponibleParaPagar()`, `scopePagadasCompletamente()`, `scopeOrdenarPorDiferenciaAutorizada()`. |
| ☐ | GestionPro | Autorización de un monto parcial en SPP | Back | 0.5 | Crear FormRequest `AutorizarMontoParcialRequest.php` con validaciones: `rol` (required), `monto_autorizado` (required, numeric, min:0.01), `motivo` (required, min:10 caracteres). |
| ☐ | GestionPro | Autorización de un monto parcial en SPP | Back | 0.5 | Agregar validaciones personalizadas en `withValidator()` del FormRequest: monto_autorizado <= monto_total, SP no debe estar pagada, SP no debe tener pagos previos. |
| ☐ | GestionPro | Autorización de un monto parcial en SPP | Back | 1 | Crear método `autorizarParcial()` en controlador `ConstruccSolicitudPagoController.php` que reciba: rol, monto_autorizado, motivo, usuario_id, usuario_nombre. |
| ☐ | GestionPro | Autorización de un monto parcial en SPP | Back | 0.5 | Implementar lógica de autorización parcial en método `autorizarParcial()`: actualizar nivel de autorización según rol (dg/gerente), guardar monto_autorizado, usuario, motivo y fecha, cambiar estado a 'autorizada' si se completaron niveles. |
| ☐ | GestionPro | Autorización de un monto parcial en SPP | Back | 0.5 | Agregar ruta `POST /api/solicitudes-pago/{id}/autorizar-parcial` en archivo `routes/segmented/mixed.php` con middleware `auth:sanctum` y `role:gerente,dg`. |
| ☐ | GestionPro | Autorización de un monto parcial en SPP | Back | 0.5 | Modificar método `index()` en controlador para incluir eager loading de relación `usuarioAutorizoParcial` y agregar filtros: `solo_autorizacion_parcial`, `usuario_autorizo_parcial_id`. |
| ☐ | GestionPro | Autorización de un monto parcial en SPP | Back | 0.5 | Actualizar respuesta JSON del método `index()` agregando los nuevos campos: `monto_autorizado`, `monto_disponible_para_pagar`, `es_autorizacion_parcial`, `usuario_autorizo_parcial_nombre`, `motivo_autorizacion_parcial`, `fecha_autorizacion_parcial`. |
| ☐ | GestionPro | Autorización de un monto parcial en SPP | Back | 1 | Modificar método `pagarMultiple()` agregando validación ANTES de procesar pagos: calcular `montoDisponible = (monto_autorizado ?? monto_total) - monto_pagado` y rechazar si `monto_pago > montoDisponible`. |
| ☐ | GestionPro | Autorización de un monto parcial en SPP | Back | 0.5 | Agregar logs de advertencia en método `pagarMultiple()` cuando se intente pagar más del monto autorizado, incluyendo: solicitud_id, folio, monto_autorizado, monto_disponible, monto_intento_pago. |
| ☐ | GestionPro | Autorización de un monto parcial en SPP | Back | 0.5 | Actualizar lógica de estado "pagado" en método `pagarMultiple()`: cambiar estado cuando `monto_pagado >= (monto_autorizado ?? monto_total)` en lugar de solo comparar con `monto_total`. |
| ☐ | GestionPro | Autorización de un monto parcial en SPP | Back | 0.5 | Crear o actualizar `SolicitudPagoResource.php` agregando campos: `monto_autorizado`, `monto_autorizado_efectivo`, `monto_disponible_para_pagar`, `es_autorizacion_parcial`. |
| ☐ | GestionPro | Autorización de un monto parcial en SPP | Back | 0.5 | Agregar objeto anidado `autorizacion_parcial` en Resource con: `usuario_id`, `usuario_nombre`, `motivo`, `fecha`, `diferencia`, `porcentaje_autorizado` (solo cuando `es_autorizacion_parcial = true`). |
| ☐ | GestionPro | Autorización de un monto parcial en SPP | Back | 0.5 | Crear Observer `SolicitudPagoObserver.php` con método `saving()` que actualice automáticamente el estado a "pagado" cuando `monto_pagado >= (monto_autorizado ?? monto_total)`. |
| ☐ | GestionPro | Autorización de un monto parcial en SPP | Back | 0.5 | Registrar Observer en `AppServiceProvider.php`: `SolicitudPago::observe(SolicitudPagoObserver::class)`. |
| ☐ | GestionPro | Autorización de un monto parcial en SPP | Back | 0.5 | Crear archivo `.vscode/http/solicitudes-pago-autorizacion-parcial.http` con 12 casos de prueba: autorizar parcial válido, monto mayor al total, sin motivo, pagar exacto, exceder monto, abonos. |
| ☐ | GestionPro | Autorización de un monto parcial en SPP | Back | 0.5 | Ejecutar migración con `php artisan migrate` y verificar que se crearon los 5 campos en tabla `solicitudes_pago` con índices correctos. |
| ☐ | GestionPro | Autorización de un monto parcial en SPP | Back | 0.5 | Probar endpoint `POST /solicitudes-pago/{id}/autorizar-parcial` con caso exitoso: monto_autorizado=20000, motivo válido, verificar respuesta incluye todos los campos. |
| ☐ | GestionPro | Autorización de un monto parcial en SPP | Back | 0.5 | Probar validaciones del endpoint: monto mayor al total (debe rechazar 422), sin motivo (debe rechazar 422), motivo con menos de 10 caracteres (debe rechazar 422). |
| ☐ | GestionPro | Autorización de un monto parcial en SPP | Back | 0.5 | Probar endpoint `GET /solicitudes-pago` con filtro `solo_autorizacion_parcial=true` y verificar que solo retorna SPs con `monto_autorizado IS NOT NULL`. |
| ☐ | GestionPro | Autorización de un monto parcial en SPP | Back | 0.5 | Probar flujo de pago: crear SP de $100k, autorizar $20k parcialmente, intentar pagar $30k (debe fallar), pagar $20k (debe marcar como pagada). |
| ☐ | GestionPro | Autorización de un monto parcial en SPP | Back | 0.5 | Probar flujo de abonos: autorizar $20k parcialmente, pagar abono de $10k (debe quedar en autorizada), pagar segundo abono de $10k (debe marcar como pagada). |
| ☐ | GestionPro | Autorización de un monto parcial en SPP | Back | 0.5 | Verificar que los logs se generan correctamente en `storage/logs/laravel.log` para: autorización parcial exitosa, intento de pago excesivo, cambio de estado a pagado. |
| ☐ | GestionPro | Autorización de un monto parcial en SPP | Back | 0.5 | Documentar en archivo `.docs/API_AUTORIZACION_PARCIAL.md` los 3 endpoints modificados: autorizar-parcial (nuevo), listar (modificado), pagar-multiple (validación agregada). |

---

## 📊 RESUMEN

**Total de actividades:** 28  
**Tiempo estimado total:** 15 horas  
**Distribución:**
- Migración y Modelo: 2.5 horas
- Validaciones y Requests: 1 hora  
- Controladores y Endpoints: 3 horas
- Resources y Observers: 1.5 hora
- Testing y Validación: 4 horas
- Documentación: 0.5 horas

---

## 🗂️ ARCHIVOS A CREAR/MODIFICAR

### Crear (7 archivos nuevos):
1. `database/migrations/YYYY_MM_DD_HHMMSS_add_autorizacion_parcial_to_solicitudes_pago.php`
2. `app/Http/Requests/SolicitudPago/AutorizarMontoParcialRequest.php`
3. `app/Observers/SolicitudPagoObserver.php`
4. `app/Http/Resources/SolicitudPago/SolicitudPagoResource.php` (si no existe)
5. `.vscode/http/solicitudes-pago-autorizacion-parcial.http`
6. `.docs/API_AUTORIZACION_PARCIAL.md`

### Modificar (4 archivos existentes):
1. `app/Models/SolicitudPago.php`
2. `app/Http/Controllers/ConstruccSolicitudPagoController.php`
3. `routes/segmented/mixed.php`
4. `app/Providers/AppServiceProvider.php`

---

## 📅 CRONOGRAMA SUGERIDO

### Día 1 (8 horas)
- ✅ **Mañana (4h):** Actividades 1-10 (Migración, Modelo, Scopes, FormRequest, Endpoint)
- ✅ **Tarde (4h):** Actividades 11-18 (Modificar Listar, Validar Pagar, Resources, Observer)

### Día 2 (7 horas)
- ✅ **Mañana (4h):** Actividades 19-24 (Testing completo de todos los casos)
- ✅ **Tarde (3h):** Actividades 25-28 (Validación final, logs, documentación)

---

**Responsable:** Gerónimo Montes  
**Jefe de Desarrolladores:** Julio Sergio Salazar  
**Fecha de creación:** 21 de enero de 2026  
**Versión:** 1.0
