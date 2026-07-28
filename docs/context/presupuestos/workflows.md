# Presupuestos — Workflows

## Ciclo de vida objetivo

```
borrador → enviar → aceptar / rechazar → pago → finalización
```

| Etapa | Estado en código | Notas |
|-------|------------------|-------|
| Borrador | `borrador` | Crear/editar: receptor, emisor, líneas, anexos, títulos de sección, fecha, términos, moneda, tema |
| Enviar | `enviado` | App y/o correo; opcional notificar receptor en app; enlace público |
| Aceptar / rechazar | `aceptado` \| `rechazado` \| `rechazado_con_observacion` | Autenticado o token público |
| Vencido | `vencido` | Si aplica reglas de vigencia |
| Pago | — | **Roadmap**: cuentas bancarias (hechas) + pasarelas PayPal/Stripe (UI sin backend) |
| Finalización | — | **Roadmap**: cierre tras pago; aún no modelado como estado |

Estados actuales del model: `borrador` \| `enviado` \| `aceptado` \| `rechazado` \| `rechazado_con_observacion` \| `vencido`.

## Flujo operativo (implementado)

1. **Borrador** — receptor (cartera \| proveedor registrado \| manual) + tarjeta emisor + conceptos + anexos + términos/descuento/moneda.
2. **Ajustes de documento (borrador)**
   - `fecha_emision` — modal settings (≤ hoy).
   - `titulo_anexos` — inline en card anexos imagen (default **Anexos**; commit blur / guardar / preview).
   - `titulo_anexos_pdf` — inline en card anexos PDF (default **Anexos PDF**; mismo commit).
   - Anexos imagen: máximo 4 en captura (solo front).
3. **Enviar** — `enviar` / `enviar-correo` / `notificar-receptor-app` / `reenviar`.
4. **Receptor** — listado “recibidos”, notificación, o enlace público.
5. **Aceptar / rechazar** — preview autenticado o `public/presupuestos/{token}/…`.
6. **Duplicar** — nuevo borrador desde uno existente (incluye títulos de anexos).

## PDF y personalización

- Preview sin guardar: `POST …/generar-pdf`
- PDF persistido: `GET …/{presupuesto}/pdf`
- Tema por presupuesto: `PATCH …/pdf-theme` + listado `GET …/pdf-themes`
- **Anexos imagen**: título de página = `titulo_anexos` o **Anexos**; hasta 4 imágenes por hoja Blade
- **Anexos PDF**: archivos se mergean al final (`PresupuestoPdfAnexoMerger`); título de sección en estampado = `titulo_anexos_pdf` o **Anexos PDF** (`PresupuestoPdfAnexoEstampado`); título del archivo PDF como subtítulo si es distinto
- Tabla de conceptos: numeración (`#`) centrada también en filas párrafo
- Folio impreso: `numero_presupuesto` (`PRES-XXXX`)
- **QR pie de página**: apunta a `/public/presupuesto/{token}`; con sesión el front abre `enlace-publico/:token` (no `preview/:id`)

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
- Sección menú **Clientes**: list / form / detail propios (`presupuesto-clientes-*`). En captura se elige de la cartera (o manual / proveedor registrado).

## Catálogo de conceptos

- CRUD embebido en modal de línea (tab Catálogo + “guardar también en catálogo” en Manual); feature **Plus**.
- Sección menú **Catálogo de conceptos**: list / form / detail propios (`presupuesto-catalogo-conceptos-*`); al usarlo en el presupuesto se hace **snapshot** a la línea (sin FK).
