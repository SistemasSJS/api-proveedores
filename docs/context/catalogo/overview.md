# CatÁlogo de productos — Overview

Dominio **aislado**: inventario comercial del proveedor (productos y taxonomÍa) + stock por sucursal + importaciÓn CSV.

## PropÓsito

Administrar productos, categorÍas (y subcategorÍas), marcas, unidades de medida; asignar productos a sucursales con stock; importar por CSV.

## LÍmites (quÉ entra)

- Producto, Categoria, Marca, UnidadMedida
- Pivot sucursal-producto (stock/precio local)
- Import CSV + auditorÍas de import
- Consumo read-only desde admin/shared/construcc/tienda (consumidores, no dueÑos del diseÑo)

## QuÉ NO es este dominio

| No mezclar con | Motivo |
|----------------|--------|
| Solicitudes de pago | Sin `producto_id` en SP |
| Presupuestos | Conceptos sin FK a producto; tab UI stub |
| "CatÁlogo" de clientes en presupuestos | Otro significado |

## Estado / gap front

- **API:** implementada y activa en `gerente.php`.
- **Front:** mÓdulos existen en disco, pero el `proveedor-routing` actual **no monta** las lazy routes de catÁlogo (sÍ hay menÚ/cÓdigo). Tratar como *implementado / desconectado del shell de rutas actual*.

## Docs del dominio

- [api.md](./api.md)
- [database.md](./database.md)
- [front.md](./front.md)

Ver tambiÉn: [../cross-domain.md](../cross-domain.md)