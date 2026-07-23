# Presupuestos — Frontend

Repo: `app-proveedores`.

## Módulo principal

`src/app/pages/proveedor/presupuesto-proveedor/`

Montado en `proveedor-routing.module.ts` como ruta `presupuestos`.

| Área | Path relativo |
|------|----------------|
| Servicio | `services/presupuesto-proveedor.service.ts` |
| Listados | `pages/presupuesto-proveedor-list` (enviados / recibidos / historial) |
| Form | `pages/presupuesto-proveedor-form` |
| Preview | `pages/presupuesto-proveedor-preview` |
| Enlace (auth) | `pages/presupuesto-enlace-publico` |
| Stepper / modales | `components/presupuesto-form`, `presupuesto-page-modals` |

Rutas UI típicas: `/pages/proveedor/presupuestos/{list|recibidos|historial|crear|editar/:id|detalle/:id|preview/:id|…}`.

## Config emisor/receptor (perfil)

- UI: `perfil-usuario-proveedor/components/presupuesto-config-form/`
- Servicio: `perfil-usuario-proveedor/services/proveedor-presupuesto-config.service.ts`

## Cuentas bancarias y pasarelas (perfil — cobro roadmap)

- UI: `perfil-usuario-proveedor/components/datos-bancarios-form/`
- Cuentas bancarias: implementadas.
- Sección **Servicios digitales**: PayPal y Stripe — UI placeholder (“No configurado” / Configurar); **sin integración real**.

## Público (sin login)

- `src/app/public/presupuesto-publico/`
- `src/app/public/visor-presupuesto-publico/`
- Rutas en `public-routing.module.ts`

## Monedas (UI)

Campo `term_cond_moneda`: solo **MXN** \| **USD** \| **EUR** (default MXN). Prefijo de monto: `€` para EUR, `$` para MXN/USD.

## Ajustes del documento (fecha / título anexos)

### Fecha de emisión

- En captura (`presupuesto-page-modals`), icono **settings** en la card «Presupuesto dirigido a» abre `presupuesto-ajustes-modal` **solo** con `fecha_emision` (no mezclar otros campos ahí).
- UI: trigger con icono calendario + fecha en español; `ion-datetime` (`presentation="date"`, locale `es-MX`) con **`max` = hoy** (zona local). Validación / clamp: no se puede guardar fecha futura.
- Estilos overlay: `ion-modal.modal-ajustes-presupuesto` y `ion-modal.fecha-picker-modal` en `src/global.scss`.
- Preview **no** sobrescribe la fecha del borrador con “hoy” al abrir.

### Título de sección de anexos (`titulo_anexos` / `titulo_anexos_pdf`)

- **Imágenes**: editable **inline** en el encabezado de la card de anexos (default **Anexos**).
- **PDF**: mismo patrón en la card de anexos PDF (default **Anexos PDF**); no en el modal de ajustes.
- Máx. 80 caracteres (alineado al API).
- Persistencia: borradores locales `tituloAnexosDraft` / `tituloAnexosPdfDraft` + `ngModel` standalone; se confirman al **blur**, al guardar borrador y antes de vista previa (`commitTituloAnexosDraft` / `commitTituloAnexosPdfDraft`). Evita que el autosave por tecla deje valores parciales en BD.
- Autosave: si hubo edición concurrente durante un guardado (`autosaveDrainPending`), **no** marcar pristine hasta drenar el valor completo.
- Preview: getter `tituloAnexosPreview` / `normalizeTituloAnexos` para la sección de imágenes; binding con `[textContent]`.

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
- Modal de concepto:
  - Tab Catálogo: listar / buscar / filtrar; click = snapshot a la línea; editar / eliminar; **Nuevo en catálogo**.
  - Tab Manual: checkbox «Guardar también en el catálogo» (+ categoría producto/servicio) al añadir línea.
- Badge Plus en tab y acciones de catálogo. **No** integrar el dominio Catálogo de productos salvo decisión explícita.
