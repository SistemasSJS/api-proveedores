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

## Config en perfil

- UI: `perfil-usuario-proveedor/components/presupuesto-config-form/`
- Servicio: `perfil-usuario-proveedor/services/proveedor-presupuesto-config.service.ts`

## Público (sin login)

- `src/app/public/presupuesto-publico/`
- `src/app/public/visor-presupuesto-publico/`
- Rutas en `public-routing.module.ts`

## Stub a no confundir

Modal de concepto con tab “catálogo”: lista vacía / sin llamada a `ProductoService`. No implica integración con dominio catálogo.
