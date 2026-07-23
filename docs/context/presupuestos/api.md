# Presupuestos — API

Repo: `api-proveedores`. Prefijo gerente: `proveedores/{proveedor}/…` + `proveedor.access`.

## Rutas principales (`routes/segmented/gerente.php`)

| Grupo | Path | Controller |
|-------|------|------------|
| Presupuestos | `{proveedor}/presupuestos` | `ProveedorPresupuestoController` |
| | `GET/POST /`, `GET/PUT/PATCH/DELETE /{presupuesto}` | CRUD |
| | `GET /next-folio`, `GET /pdf-themes` | Folio / temas |
| | `POST /generar-pdf` | PDF sin persistir |
| | `GET /{presupuesto}/pdf`, `PATCH …/pdf-theme` | PDF guardado / tema |
| | `POST …/duplicar`, `…/enviar`, `…/enviar-correo`, `…/notificar-receptor-app`, `…/reenviar` | Ciclo de vida |
| | `GET /proveedores-registrados` | Receptores = proveedores del sistema |
| Cartera | `{proveedor}/presupuestos/cartera-clientes` | `ProveedorPresupuestoCarteraClientesController` |
| Catálogo conceptos | `{proveedor}/presupuestos/presupuesto-catalogo-conceptos` | `ProveedorPresupuestoCatalogoConceptosController` |
| Anexos | `{proveedor}/presupuestos/{presupuesto}/anexos` (+ `/bulk`) | `ProveedorPresupuestoAnexoController` |
| Anexos PDF | `…/anexos-pdf` | `ProveedorPresupuestoAnexoPdfController` |
| Config | `{proveedor}/config-emisor-receptor-presupuestos` | `ProveedorPresupuestoConfigController` |

## Público (`routes/segmented/public.php`)

| Método | Path | Acción |
|--------|------|--------|
| GET | `public/presupuestos/{token}` | Ver |
| GET | `public/presupuestos/{token}/pdf` | PDF |
| POST | `public/presupuestos/{token}/aceptar` | Aceptar |
| POST | `public/presupuestos/{token}/rechazar` | Rechazar |

Controller: `PresupuestoPublicController`.

## Piezas de soporte

| Pieza | Ubicación |
|-------|-----------|
| PDF | `app/Support/PresupuestoPdf.php` (+ layout/tema/anexos) |
| Mail | `app/Mail/PresupuestoEnviadoMail.php` |
| Notifications | `app/Notifications/Presupuesto/*` |
| Resources | `app/Http/Resources/Presupuesto/*` |
| Requests | `app/Http/Requests/Presupuesto/*` |

## Notas de contrato

- Moneda del documento: `term_cond_moneda` ∈ `MXN` \| `USD` \| `EUR`.
- Folio: `GET …/next-folio` / asignación al crear usan `PRES-` + `proveedores.consecutivo_presupuesto_siguiente` (no el `id` del presupuesto).
- `fecha_emision`: aceptada en store/update; el front limita a **≤ hoy**.
- `titulo_anexos`: `nullable|string|max:80` en `StorePresupuestoRequest` / `UpdatePresupuestoRequest`. Resources y PDF (sección imágenes) normalizan vacío → **Anexos**.
- `titulo_anexos_pdf`: `nullable|string|max:80`. Resources normalizan vacío → **Anexos PDF** (título de la card de anexos PDF en captura; los PDFs se concatenan al final sin portada de sección).
- PDF: columna de numeración de líneas (concepto y párrafo) alineada al centro (`td:first-child`).
- No hay endpoints de cobro PayPal/Stripe ni de “finalizar por pago” en presupuestos (roadmap).
- Cuentas bancarias: rutas de `{proveedor}/cuentas-bancarias` (perfil / soporte); no son el flujo SP.
