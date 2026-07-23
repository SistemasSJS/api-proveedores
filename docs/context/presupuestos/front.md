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

- En captura (`presupuesto-page-modals`), icono **settings** en la card «Presupuesto dirigido a» abre modal solo para `fecha_emision`.
- `titulo_anexos` se edita **inline** en el encabezado de la card de anexos (input + icono edit; default **Anexos**).

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
