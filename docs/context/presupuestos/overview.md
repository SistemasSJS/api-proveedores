# Presupuestos — Overview

Dominio **aislado**: documento comercial (presupuesto) entre un proveedor emisor y un receptor (cartera, otro proveedor o datos manuales).

## PropÓsito

Crear, editar, enviar y aceptar/rechazar presupuestos; generar PDF; gestionar cartera de clientes del presupuesto y tarjetas emisor/receptor.

## LÍmites (quÉ entra)

- Presupuesto + conceptos (lÍneas / pÁrrafos) + anexos imagen/PDF
- Cartera de clientes del presupuesto
- Config emisor/receptor (tarjetas de contacto)
- PDF, correo, notificaciones de presupuesto
- Enlace pÚblico (token) aceptar/rechazar

## QuÉ NO es este dominio

| No mezclar con | Motivo |
|----------------|--------|
| Solicitudes de pago | No hay conversiÓn presupuesto a SP |
| CatÁlogo de productos | Conceptos son texto/snapshot; sin `producto_id` |
| Cotizaciones / pedidos | Dominios hermanos o legado, no este flujo |

## Advertencia de lenguaje

En este dominio, **"catÁlogo"** suele referirse a **clientes/receptores** (cartera o proveedores registrados), **no** al catÁlogo de productos.

## Estado

Maduro y en uso activo (API + front montados en el router).

## Docs del dominio

- [api.md](./api.md)
- [database.md](./database.md)
- [front.md](./front.md)
- [workflows.md](./workflows.md)

Ver tambiÉn: [../cross-domain.md](../cross-domain.md)