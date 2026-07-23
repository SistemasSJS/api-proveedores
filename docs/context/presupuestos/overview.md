# Presupuestos — Overview

Dominio **aislado**: herramienta para generar presupuestos a distintos giros comerciales (mecánicos, herreros, constructores, vendedores, etc.) con una UX simple, al alcance de cualquier usuario.

Parte de un **esquema gratuito** (plan base) y funciones **Plus** (ver [front.md](./front.md) — badge / directiva). A futuro puede evolucionar a apps independientes; hoy cohabita en el monorepo sin mezclarse con catálogo de productos ni solicitudes de pago.

## Propósito

Facilitar la presupuestación comercial: crear, personalizar, enviar y cerrar (aceptar/rechazar) presupuestos; PDF con temas; cartera propia; acceso a empresas ya registradas; configuración de emisor (usuario ≠ solo datos de empresa).

## Capacidad (mapa Hecho / Pendiente / Roadmap)

| Capacidad | Estado | Notas |
|-----------|--------|-------|
| Cartera de clientes propia | **Hecho** | `cartera-clientes` |
| Acceso a empresas/proveedores registrados | **Hecho** | `proveedores-registrados` |
| Receptor manual (texto) | **Hecho** | Sin entidad previa |
| Conceptos libres (línea / párrafo) | **Hecho** | Snapshot; sin `producto_id` |
| Catálogo de conceptos reutilizable | **Hecho** (API + listado UI) | `presupuesto-catalogo-conceptos`; feature **Plus**; snapshot a la línea |
| Descuentos, IVA, términos y condiciones | **Hecho** | En formulario / PDF |
| Monedas | **Hecho** | Solo **MXN**, **USD**, **EUR** (`term_cond_moneda`; default MXN) |
| Anexos imagen | **Hecho** | Máx. **4** en captura (solo front); título sección `titulo_anexos` → PDF/preview |
| Anexos PDF | **Hecho** | Merge al final; título sección `titulo_anexos_pdf` → estampado; título por archivo opcional |
| Fecha de emisión editable | **Hecho** | Modal ajustes; front: no fecha futura |
| Folio `PRES-XXXX` | **Hecho** | Consecutivo por proveedor; bump histórico +200 (migración) |
| Numeración alineada concepto/párrafo | **Hecho** | Columna `#` centrada en PDF + preview |
| Temas PDF personalizables | **Hecho** | `pdf-themes` |
| Datos usuario emisor ≠ empresa emisora | **Hecho** | Config / tarjetas emisor-receptor |
| Envío app + correo + enlace público | **Hecho** | Aceptar / rechazar |
| Cuentas bancarias (perfil empresa) | **Hecho** (soporte) | Perfil; no cobranza automática |
| Pasarelas PayPal / Stripe | **Roadmap** | UI en perfil (“Servicios digitales”); **sin implementar** |
| Pago → finalización del presupuesto | **Roadmap** | Tras aceptar; no confundir con dominio SP |

## Límites (qué entra)

- Documento presupuesto + conceptos + anexos (imagen / PDF)
- Cartera y config emisor/receptor
- PDF, correo, notificaciones de presupuesto, token público
- Monedas MXN | USD | EUR
- Roadmap de cobro (cuentas / pasarelas) **como parte de este dominio**, no vía Solicitudes de pago

## Qué NO es este dominio

| No mezclar con | Motivo |
|----------------|--------|
| Solicitudes de pago (SP/SPP) | No hay conversión presupuesto → SP |
| Catálogo de **productos** | Conceptos sin FK a producto |
| Cotizaciones / pedidos | Otros flujos |

## Advertencia de lenguaje

- **“Catálogo”** en presupuestos suele ser **clientes/receptores** o el **catálogo de conceptos** — **no** el dominio Catálogo de productos.
- Funciones **Plus**: marcar en UI con `appPlanPlusBadge` / `<app-plan-plus-badge>` ([front.md](./front.md)).

## Estado general

Ciclo **borrador → enviar → aceptar/rechazar** maduro (API + front activos).

Ajustes de documento en captura (cerrados):

- `fecha_emision` — modal settings (≤ hoy en front)
- `titulo_anexos` — inline en card anexos imagen (default **Anexos**; PDF sección imágenes + preview)
- `titulo_anexos_pdf` — inline en card anexos PDF (default **Anexos PDF**; estampado al mergear PDFs)
- Anexos imagen: tope **4** solo en front (`PRESUPUESTO_ANEXOS_IMAGEN_MAX`)

**Pago → finalización**: roadmap. Catálogo de conceptos: API + UI de listado/alta/edición/baja (snapshot a la línea).

## Docs del dominio

- [api.md](./api.md)
- [database.md](./database.md)
- [front.md](./front.md)
- [workflows.md](./workflows.md)

Ver también: [../cross-domain.md](../cross-domain.md)
