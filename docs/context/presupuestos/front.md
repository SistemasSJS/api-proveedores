# Presupuestos — Frontend

Repo: `app-proveedores`.

## Módulo principal

`src/app/pages/proveedor/presupuesto-proveedor/`

Montado en `proveedor-routing.module.ts` como ruta `presupuestos`.

| Área | Path relativo |
|------|----------------|
| Servicio documento | `services/presupuesto-proveedor.service.ts` (delega cartera/conceptos a módulos hijos) |
| Listados | `pages/presupuesto-proveedor-list` (enviados / recibidos / historial) |
| Form | `pages/presupuesto-proveedor-form` |
| Preview | `pages/presupuesto-proveedor-preview` |
| Enlace (auth) | `pages/presupuesto-enlace-publico` (`…/presupuestos/enlace-publico/:token`) |
| Stepper / modales | `components/presupuesto-form`, `presupuesto-page-modals` |
| Recursos CRUD (lazy) | `pages/{clientes\|catalogo-conceptos\|tarjetas}/` — module + routes + services + models + constants |
| Paths (barrel) | `constants/presupuesto-gestion.paths.ts` → reexporta paths de cada recurso |

Rutas UI: `/pages/proveedor/presupuestos/{list|recibidos|historial|crear|editar/:id|detalle/:id|preview/:id|clientes|…|catalogo-conceptos|…|tarjetas-presentacion|…}`.

## Menú y secciones (v1 — implementado)

Orden en `user-menu-new.ts`:

```text
Presupuestos
├── Generar Presupuesto       →  /pages/proveedor/presupuestos/crear
├── Mis presupuestos          →  /pages/proveedor/presupuestos/list
│                                 (segmentos internos: enviados | recibidos)
└── Recursos (colapsable)
    ├── Clientes              →  /pages/proveedor/presupuestos/clientes
    ├── Catálogo de conceptos →  /pages/proveedor/presupuestos/catalogo-conceptos
    └── Tarjetas Presentación →  /pages/proveedor/presupuestos/tarjetas-presentacion
                                  (Perfil → pestaña Tarjetas navega al listado)
```

Principios:

- Bajo Presupuestos: acciones de documento (Generar / Mis presupuestos) + grupo **Recursos** (Clientes, Conceptos, Tarjetas).
- Cada recurso tiene **CRUD en pages propias** (patrón SPP: list / form / detail + components), **sin** embeber modales de captura.
- Captura (`crear` / `editar`) sigue eligiendo de esos recursos vía modales/selectores (snapshot).
- Dos entradas a Tarjetas: menú Presupuestos → Recursos + Perfil (misma ruta de listado).
- **Historial** en menú: fuera de v1.
- **Plantillas**: roadmap; no en menú v1.

Shell (menús): tema claro compartido móvil/desktop — ver `docs/context/platform-shared.md` (Shell UI) y `sidebar-menu-sections.scss`.

### Páginas de gestión (módulos lazy bajo `pages/{recurso}/`)

Cada recurso es un NgModule completo (`services`, `models`, `constants`, `pages`, `components`) con `loadChildren` desde `presupuesto-proveedor.routes.ts`. URLs públicas sin cambio.

| Recurso | Módulo | Path URL | Servicio |
|---------|--------|----------|----------|
| Clientes | `pages/clientes/presupuesto-clientes.module.ts` | `…/clientes` | `PresupuestoClientesService` |
| Catálogo conceptos | `pages/catalogo-conceptos/…` | `…/catalogo-conceptos` | `PresupuestoCatalogoConceptosService` |
| Tarjetas Presentación | `pages/tarjetas/…` | `…/tarjetas-presentacion` | `PresupuestoTarjetasService` (fachada sobre config perfil) |

Rutas hijas por módulo: `''` \| `crear` \| `editar/:id` \| `detalle/:id`. Patrón: regla `front-feature-crud.mdc` en app-proveedores.

UI: look alineado a cards/forms de captura (`styles/*-shared.scss` del recurso → `presupuesto-gestion-shared.scss`); no reutiliza el markup de los modales de captura.

Listados de gestión: barra search + toggle **detallada / mosaico** (constantes `presupuesto-gestion-view.constants.ts`), card con `[viewMode]`, conteo + `setItemCount`. Patrón: regla `front-listados.mdc` en app-proveedores.

## Config emisor/receptor (Tarjetas Presentación)

- API / servicio: `perfil-usuario-proveedor/services/proveedor-presupuesto-config.service.ts`
- UI de gestión: páginas `presupuesto-tarjetas-*` bajo Presupuestos
- Entradas: menú → Tarjetas Presentación; Perfil → pestaña **Tarjetas** (navigate a listado). Fragment `#presupuestos` → `…/tarjetas-presentacion`
- `PresupuestoConfigSharedModule` / `presupuesto-config-form` quedan como pieza de perfil/legacy; la casa de gestión es el CRUD de Tarjetas en Presupuestos

## Cuentas bancarias y pasarelas (perfil — cobro roadmap)

- UI: `perfil-usuario-proveedor/components/datos-bancarios-form/`
- Cuentas bancarias: implementadas.
- Sección **Servicios digitales**: PayPal y Stripe — UI placeholder (“No configurado” / Configurar); **sin integración real**.

## Público (sin login)

- `src/app/public/presupuesto-publico/`
- `src/app/public/visor-presupuesto-publico/`
- Rutas en `public-routing.module.ts`
- URL pública: `/public/presupuesto/{token_publico}` (param se llama `id` en la ruta, pero es el **token**, no la PK).

### Enlace público y QR del pie del PDF

- El QR del PDF y los correos usan `{APP_FRONTEND_URL}/public/presupuesto/{token}`.
- Si el usuario **tiene sesión** y abre esa URL, `publicPresupuestoAuthRedirectGuard` redirige a  
  `/pages/proveedor/presupuestos/enlace-publico/{token}` (vista dentro del shell; **no** a `preview/:id`, porque el param no es el id numérico).
- Sin sesión: se muestra `presupuesto-publico` con cabecera propia.
- Guard: `src/app/public/guards/public-presupuesto-auth-redirect.guard.ts`.
- Config API: `APP_FRONTEND_URL` (`config('app.frontend_url')`) debe apuntar al front, no a la API.

## Monedas (UI)

Campo `term_cond_moneda`: solo **MXN** \| **USD** \| **EUR** (default MXN). Prefijo de monto: `€` para EUR, `$` para MXN/USD.

## Ajustes del documento (fecha / layout / títulos de anexos)

### Fecha de emisión y `ppto_config`

- En captura (`presupuesto-page-modals`), icono **settings** en la card «Presupuesto dirigido a» abre `presupuesto-ajustes-modal`.
- Campos: `fecha_emision` (≤ hoy) + `ppto_config.gap_logo_info_mm` (y extensible a otros mm).
- Preview: botón **Historial** → modal timeline de `estado_logs` (fecha, estados, nota, usuario).
- Unidades en concepto (captura y catálogo CRUD): buscador + opción **Otro** (texto libre ≤ 50).
- Nombres propios Dirigido a / Atentamente: sentence case (`presupuesto-texto-documento.helper.ts`).
- Estilos overlay: `ion-modal.modal-ajustes-presupuesto` y `ion-modal.fecha-picker-modal` en `src/global.scss`.

### Títulos de sección de anexos

| Campo | Card UI | Default | Dónde se ve en PDF / preview |
|-------|---------|---------|------------------------------|
| `titulo_anexos` | Anexos imagen (inline) | **Anexos** | Blade sección imágenes + preview (`tituloAnexosPreview`) |
| `titulo_anexos_pdf` | Anexos PDF (inline) | **Anexos PDF** | Estampado de hojas PDF mergeadas (mismo texto que el formulario) |

- Máx. 80 caracteres (alineado al API).
- Persistencia: borradores `tituloAnexosDraft` / `tituloAnexosPdfDraft` + `ngModel` standalone; commit al **blur**, al guardar borrador y antes de vista previa (`commitTituloAnexosDraft` / `commitTituloAnexosPdfDraft`). Evita truncado por autosave por tecla.
- Autosave: si hubo edición concurrente (`autosaveDrainPending`), **no** marcar pristine hasta drenar el valor completo.
- Preview imágenes: binding con `[textContent]` (no interpolación frágil).

### Límite de anexos imagen (solo front)

- Constante: `PRESUPUESTO_ANEXOS_IMAGEN_MAX = 4` en `helpers/presupuesto-anexo-imagen.helper.ts`.
- Al llegar al tope: dropzone deshabilitada (`anexos-dropzone--disabled`).
- Si el lote supera los cupos libres: se recorta y se avisa.
- La API **no** impone este tope.
- Motivo UX: una página de anexos imagen en PDF muestra 4 por hoja.

### PDF / preview — numeración

- Filas de concepto y párrafo: primera columna (`#`) centrada en preview (`presupuesto-proveedor-preview.page.scss`) y en blades PDF (`pdf.blade.php` / `pdf-tailwind.blade.php`).

## Folio en UI

- Muestra `numero_presupuesto` (`PRES-XXXX`) del API; el consecutivo lo lleva el backend por proveedor.

## Plan Plus — badge obligatorio en features Plus

Cuando una capacidad sea **Plus** (plan superior / no incluida en el esquema gratuito), la UI **debe** mostrar el badge con la directiva o el componente de `@theme`.

### Directiva (sobre un host, p. ej. botón)

```html
<button type="button" appPlanPlusBadge disabled>…</button>
<button type="button" appPlanPlusBadge="overlay">…</button>
<button type="button" appPlanPlusBadge="inline">…</button>
```

- Selector: `[appPlanPlusBadge]`
- Archivo: `src/app/@theme/directives/plan-plus-badge.directive.ts`
- Inputs: `planPlusBadgeLabel` (default `Plus`), `planPlusBadgeAriaLabel`
- `false` desactiva el badge; por defecto modo **overlay**

### Componente (badge suelto)

```html
<app-plan-plus-badge mode="inline"></app-plan-plus-badge>
<app-plan-plus-badge mode="overlay"></app-plan-plus-badge>
```

- Archivo: `src/app/@theme/components/plan-plus-badge/`
- Exportado vía `ThemeModule`

### Usos actuales en presupuestos (referencia)

- Tab / UI catálogo de conceptos: `<app-plan-plus-badge mode="inline">`
- Acciones de cartera / guardar en catálogo de clientes: `appPlanPlusBadge` en botones

**Regla para agentes:** si el requisito dice que algo es Plus → añadir `appPlanPlusBadge` o `<app-plan-plus-badge>` en la UI afectada.

## Catálogo de conceptos (Plus)

- API: `{proveedor}/presupuestos/presupuesto-catalogo-conceptos` (CRUD).
- Modal de concepto (hoy):
  - Tab Catálogo: listar / buscar / filtrar; click = snapshot a la línea; editar / eliminar; **Nuevo en catálogo**.
  - Tab Manual: checkbox «Guardar también en el catálogo» (+ categoría producto/servicio) al añadir línea. En **móvil**, el modal usa altura ~`96dvh` y el pie (CANCELAR / Añadir) queda **fijo fuera del scroll** para que no se oculte al marcar el checkbox.
- Badge Plus en tab y acciones de catálogo. **No** integrar el dominio Catálogo de productos salvo decisión explícita.
- Sección **Catálogo de conceptos** en rutas propias (`…/catalogo-conceptos` + crear/editar/detalle); el modal de captura sigue pudiendo elegir/snapshot.
