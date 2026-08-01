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
| | `POST …/duplicar`, `…/enviar`, `…/enviar-correo`, `…/notificar-receptor-app`, `…/reenviar` | Ciclo de vida (`enviar` también desde rechazo con observación) |
| | `GET /proveedores-registrados` | Receptores = proveedores del sistema |
| Cartera | `{proveedor}/presupuestos/cartera-clientes` | `ProveedorPresupuestoCarteraClientesController` |
| Catálogo conceptos | `{proveedor}/presupuestos/presupuesto-catalogo-conceptos` | `ProveedorPresupuestoCatalogoConceptosController` |
| Anexos | `{proveedor}/presupuestos/{presupuesto}/anexos` (+ `/bulk`) | `ProveedorPresupuestoAnexoController` |
| Anexos PDF | `…/anexos-pdf` | `ProveedorPresupuestoAnexoPdfController` |
| Config | `{proveedor}/config-emisor-receptor-presupuestos` | `ProveedorPresupuestoConfigController` |

`show` carga `estadoLogs.user`. Payload incluye `estado_logs`, fechas derivadas, `ppto_config`.

## Público (`routes/segmented/public.php`)

| Método | Path | Acción |
|--------|------|--------|
| GET | `public/presupuestos/{token}` | Ver |
| GET | `public/presupuestos/{token}/pdf` | PDF |
| POST | `public/presupuestos/{token}/aceptar` | Aceptar |
| POST | `public/presupuestos/{token}/rechazar` | Rechazar (`motivo` → `rechazado_con_observacion` + `nota` en log) |

Controller: `PresupuestoPublicController`.

## Enlace web / QR (pie del PDF y correos)

- URL canónica front: `{APP_FRONTEND_URL}/public/presupuesto/{token_publico}` (misma ruta que `public-routing`).
- Generación QR PDF: `PresupuestoPdf::generarQrCodeParaPresupuesto` (BaconQrCode → data URI).
- Correo / QR auxiliar en controller: `ProveedorPresupuestoController` usa la misma URL pública (no la preview autenticada).
- Requiere `asegurarTokenPublico()` antes de armar el enlace. Si `frontend_url` apunta a la API, el QR “no existe”.

## Piezas de soporte

| Pieza | Ubicación |
|-------|-----------|
| PDF DomPDF | `app/Support/PresupuestoPdf.php` (+ `PresupuestoPdfDocumentConfig`, layout/tema) |
| Merge anexos PDF | `app/Support/PresupuestoPdfAnexoMerger.php` |
| Estampado anexos PDF | `app/Support/PresupuestoPdfAnexoEstampado.php` (usa `titulo_anexos_pdf`) |
| Mail | `app/Mail/PresupuestoEnviadoMail.php` |
| Notifications | `app/Notifications/Presupuesto/*` |
| Resources | `app/Http/Resources/Presupuesto/*` |
| Requests | `app/Http/Requests/Presupuesto/*` |

## Notas de contrato

- Moneda del documento: `term_cond_moneda` ∈ `MXN` \| `USD` \| `EUR`.
- Folio: `GET …/next-folio` / asignación al crear usan `PRES-` + `proveedores.consecutivo_presupuesto_siguiente` (no el `id` del presupuesto).
- `fecha_emision`: aceptada en store/update; el front limita a **≤ hoy**.
- `titulo_anexos`: `nullable|string|max:80` en `StorePresupuestoRequest` / `UpdatePresupuestoRequest`. Resources y Blade (sección imágenes) normalizan vacío → **Anexos**.
- `titulo_anexos_pdf`: `nullable|string|max:80`. Resources normalizan vacío → **Anexos PDF**. En el PDF generado: título principal del **estampado** de cada hoja mergeada (`PresupuestoPdfAnexoEstampado`). Si el anexo PDF tiene `titulo` propio distinto, se muestra como subtítulo.
- Duplicar: copia `titulo_anexos` y `titulo_anexos_pdf` (incluido en `only([...])` del controller).
- PDF tabla de conceptos: columna `#` centrada (`td:first-child`) en concepto y párrafo.
- Anexos imagen: **sin** límite de cantidad en API (el tope de 4 es solo front).
- No hay endpoints de cobro PayPal/Stripe ni de “finalizar por pago” en presupuestos (roadmap).
- Cuentas bancarias: rutas de `{proveedor}/cuentas-bancarias` (perfil / soporte); no son el flujo SP.
