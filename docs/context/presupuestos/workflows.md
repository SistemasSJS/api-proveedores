# Presupuestos — Workflows

## Ciclo de vida objetivo

```
borrador → enviar → aceptar / rechazar → pago → finalización
```

| Etapa | Estado en código | Notas |
|-------|------------------|-------|
| Borrador | `borrador` | Crear/editar: receptor, emisor, líneas, anexos, términos, moneda, tema |
| Enviar | `enviado` | App y/o correo; opcional notificar receptor en app; enlace público |
| Aceptar / rechazar | `aceptado` \| `rechazado` \| `rechazado_con_observacion` | Autenticado o token público |
| Vencido | `vencido` | Si aplica reglas de vigencia |
| Pago | — | **Roadmap**: cuentas bancarias (hechas) + pasarelas PayPal/Stripe (UI sin backend) |
| Finalización | — | **Roadmap**: cierre tras pago; aún no modelado como estado |

Estados actuales del model: `borrador` \| `enviado` \| `aceptado` \| `rechazado` \| `rechazado_con_observacion` \| `vencido`.

## Flujo operativo (implementado)

1. **Borrador** — receptor (cartera \| proveedor registrado \| manual) + tarjeta emisor + conceptos + anexos + términos/descuento/moneda.
2. **Ajustes de documento (borrador)** — `fecha_emision` vía modal settings (≤ hoy); `titulo_anexos` e `titulo_anexos_pdf` inline en sus cards (defaults **Anexos** / **Anexos PDF**; commit en blur / guardar / preview).
3. **Enviar** — `enviar` / `enviar-correo` / `notificar-receptor-app` / `reenviar`.
4. **Receptor** — listado “recibidos”, notificación, o enlace público.
5. **Aceptar / rechazar** — preview autenticado o `public/presupuestos/{token}/…`.
6. **Duplicar** — nuevo borrador desde uno existente.

## PDF y personalización

- Preview sin guardar: `POST …/generar-pdf`
- PDF persistido: `GET …/{presupuesto}/pdf`
- Tema por presupuesto: `PATCH …/pdf-theme` + listado `GET …/pdf-themes`
- Sección anexos imágenes: título = `titulo_anexos` o **Anexos**
- Sección anexos PDF (captura): título = `titulo_anexos_pdf` o **Anexos PDF** (los archivos PDF se mergean al final del documento generado)
- Tabla de conceptos: numeración (`#`) centrada también en filas párrafo
- Folio impreso: `numero_presupuesto` (`PRES-XXXX`)

## Emisor: usuario vs empresa

La config emisor/receptor permite datos de **contacto/usuario emisor** distintos al snapshot de **empresa emisora** (logo, razón social, etc.). Se elige tarjeta al armar el presupuesto.

## Cobro (roadmap — no es dominio SP)

Tras aceptación, la visión es cobro/finalización **dentro de presupuestos**:

1. **Cuentas bancarias** — ya en perfil empresa (`datos-bancarios-form`); el presupuesto podrá referenciarlas.
2. **Pasarelas** — PayPal y Stripe: sección “Servicios digitales” en el mismo perfil; botones Configurar; **aún no implementadas**.
3. **No** convertir el presupuesto en Solicitud de pago (SP) salvo decisión futura explícita (hoy: dominios aislados).

## Cartera

- Alta/edición en modal de selección de cliente al crear presupuesto.
- Guardar receptor en cartera / ciertas acciones de catálogo de clientes pueden ser **Plus** (badge en UI).
