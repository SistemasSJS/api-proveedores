# Catálogo de productos — Frontend

Repo: `app-proveedores`. Carpetas bajo `src/app/pages/proveedor/`:

| Carpeta | Rol |
|---------|-----|
| `producto-proveedor/` | Listado / form / detail |
| `categorias-proveedor/` | Categorías |
| `marcas-proveedor/` | Marcas |
| `unidades-proveedor/` | Unidades |
| `sucursales-proveedor/` | Sucursales (+ stock) |
| `import-productos/` | Flujo import + historial |
| `csv-import/` | Alternativa CSV (upload → confirm → results) |

Modelos compartidos: `shared/models/producto.model.ts`.

## Gap de routing

En `proveedor-routing.module.ts` **actual** están montados perfil, dashboard, sp, presupuestos, empresas, usuarios — **no** productos/categorías/marcas/import.

Los módulos y el menú (`PROVEEDOR_CATALOG` / tipo catálogo) siguen existiendo. URLs esperadas históricamente: `/pages/proveedor/productos`, `categorias`, `import-productos`, `csv-import`, etc.

Al trabajar el front de catálogo: verificar si hay que **volver a registrar** lazy routes, no asumir que ya navegan.
