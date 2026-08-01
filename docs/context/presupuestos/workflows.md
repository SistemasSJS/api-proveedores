# Presupuestos — Workflows

## Ciclo de vida objetivo

```
borrador → enviado → aceptar | rechazar(/con observación) → [reenviar si corrección] → … → pago → finalización
```

| Etapa | Estado en código | Notas |
|-------|------------------|-------|
| Borrador | `borrador` | Crear/editar; **sin** fila en `presupuesto_estado_logs` |
| Enviar | `enviado` | Primer log; emisor no edita mientras esté enviado |
| Aceptar | `aceptado` | Log; fechas derivadas del historial |
| Rechazar / pedir corrección | `rechazado` \| `rechazado_con_observacion` | Con motivo → observación/corrección; `nota` en log = motivo |
| Reenviar tras corrección | `enviado` | Desde rechazado(+motivo); limpia `motivo_rechazo`; nuevo log |
| Vencido | `vencido` | Cron `actualizarVencidos` también registra log |
| Pago | — | **Roadmap**: cuentas + pasarelas |
| Finalización | — | **Roadmap** |

## Flujo operativo (implementado)

1. **Borrador** — receptor (cartera \| proveedor registrado \| manual) + tarjeta emisor + conceptos + anexos + términos/descuento/moneda.
2. **Ajustes de documento (borrador)**
   - `fecha_emision` — modal settings (≤ hoy).
   - `ppto_config.gap_logo_info_mm` (y otros mm) — mismo modal; defaults en `PresupuestoPdfDocumentConfig` (gap logo↔info = **7 mm**).
   - `titulo_anexos` / `titulo_anexos_pdf` — inline en cards anexos.
   - Anexos imagen: máximo 4 en captura (solo front).
3. **Enviar** — `enviar` (también desde rechazo con observación) / `enviar-correo` / `notificar-receptor-app` / `reenviar` (correo).
4. **Receptor** — listado “recibidos”, notificación (nº + empresa + título/`concepto_general` + tipo de evento), o enlace público.
5. **Aceptar / rechazar** — preview autenticado o token público; rechazo con motivo → `rechazado_con_observacion`.
6. **Timeline** — modal Historial en preview (`estado_logs`).
7. **Duplicar** — nuevo borrador desde uno existente.

## PDF y personalización

- Preview sin preguntar guardar (flujo page-modals: autosave si dirty).
- `config_mostrar_totales` oculta totales + IVA + importe con letra.
- **QR pie**: `{APP_FRONTEND_URL}/public/presupuesto/{token}` — debe coincidir con ruta front; con sesión → `enlace-publico/:token`.
- Márgenes/gaps: variables Blade desde config + override `ppto_config`.
- Icono marca: app sin borde (`assets/icon` / `presupuestos/app-sin-borde`); impresión/PDF con borde (`logo-gestionplus_only_blade.png` / `presupuestos/impresion-con-borde`).
- Nombres propios Dirigido a / Atentamente: sentence case (1ª mayúscula) en helper front.

## Emisor: usuario vs empresa

La config emisor/receptor permite datos de **contacto/usuario emisor** distintos al snapshot de **empresa emisora** (logo, razón social, etc.). Se elige tarjeta al armar el presupuesto.

Gestión en sección **Tarjetas Presentación** (list / form / detail); Perfil (pestaña Tarjetas) enlaza al listado. En captura solo se elige tarjeta (snapshot al documento).

## Cobro (roadmap — no es dominio SP)

Tras aceptación, la visión es cobro/finalización **dentro de presupuestos**:

1. **Cuentas bancarias** — ya en perfil empresa (`datos-bancarios-form`); el presupuesto podrá referenciarlas.
2. **Pasarelas** — PayPal y Stripe: sección “Servicios digitales” en el mismo perfil; botones Configurar; **aún no implementadas**.
3. **No** convertir el presupuesto en Solicitud de pago (SP) salvo decisión futura explícita (hoy: dominios aislados).

## Cartera (Clientes)

- Alta/edición también en modal de selección de cliente al crear presupuesto.
- Guardar receptor en cartera / ciertas acciones pueden ser **Plus** (badge en UI).
- Sección menú **Recursos → Clientes**: list / form / detail propios (`presupuesto-clientes-*`). En captura se elige de la cartera (o manual / proveedor registrado).

## Catálogo de conceptos

- CRUD embebido en modal de línea (tab Catálogo + “guardar también en catálogo” en Manual); feature **Plus**.
- Sección menú **Recursos → Catálogo de conceptos**: list / form / detail propios (`presupuesto-catalogo-conceptos-*`); al usarlo en el presupuesto se hace **snapshot** a la línea (sin FK).
