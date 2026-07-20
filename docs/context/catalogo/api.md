# Catálogo de productos — API

Repo: `api-proveedores`. Núcleo en `routes/segmented/gerente.php` bajo `proveedores/{proveedor}/…`.

## Prefijos gerente

| Prefijo | Controller |
|---------|------------|
| `{proveedor}/productos` | `ProveedorProductoController` (+ logo) |
| `{proveedor}/categorias` | `ProveedorCategoriaController` (+ subcategorías, logo, counts) |
| `{proveedor}/marcas` | `ProveedorMarcaController` (+ logo) |
| `{proveedor}/unidades` | `ProveedorUnidadMedidaController` |
| `{proveedor}/sucursales/{sucursal}/productos` | `SucursalProductoController` (asignar / desasignar / stock) |
| `{proveedor}/csv-import` | `CsvImportController` (+ job `CSVImportJob`) |

Middleware de recurso: `proveedor.producto`, `proveedor.categoria`, `proveedor.marca`, `proveedor.unidad`, `proveedor.sucursal` (según ruta).

## Otros consumidores (mismo dominio de datos)

| Archivo | Uso |
|---------|-----|
| `admin.php` | CRUD admin global / catálogos |
| `shared.php` | Búsqueda / tienda |
| `public.php` | Indexes read-only |
| `construcc.php` | Búsqueda productos para Construcc (**no** es lógica SP) |

## Servicios import

`app/Services/CSVImport/` — processor, validators, export.
