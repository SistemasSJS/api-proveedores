# Relaciones entre dominios

Los tres dominios son **aislados**. Este archivo lista solo lo que existe de verdad. Si no está aquí, **no inventes** un puente.

## Resumen

| De → A | ¿Relación de negocio? | Detalle |
|--------|------------------------|---------|
| Catálogo → SP | **No** | SP no usa `producto_id` ni líneas de catálogo |
| Catálogo → Presupuestos | **No en datos** | Conceptos son snapshot (texto). UI tiene tab “catálogo” stub sin API de productos |
| Presupuestos → SP | **No** | No hay conversión presupuesto → solicitud de pago |
| SP → Catálogo | **No** | Endpoints de productos bajo `construcc/…` son otro dominio, mismo archivo de rutas |
| Cualquiera → Plataforma | **Sí** | Auth, `proveedor_id`, storage, notificaciones infra |

## Acoplamientos suaves (no son puentes de negocio)

1. **Home / dashboard:** puede mostrar contadores o enlaces a más de un dominio. Solo navegación/UI.
2. **Métricas SP:** el dashboard de SP puede contar presupuestos como número suelto. No hay FK ni flujo.
3. **Palabra “catálogo” en presupuestos:** suele significar **cartera de clientes/receptores**, no productos. No confundir.
4. **Construcc (`construcc.php`):** mezcla HTTP de SP y de productos. Separar por controller: productos → dominio catálogo; SP/pagos → dominio SP.

## Dependencia externa (solo SP)

- **API Construcciones:** órdenes de compra (consulta), webhooks de pago/rechazo, consumidor con ApiKey. Pertenece al dominio [solicitudes-pago](./solicitudes-pago/), no a plataforma genérica ni a los otros dos dominios.

## Futuro (apps independientes)

Si se dividen repos/apps, estas relaciones no deberían crecer. Evitar nuevas FKs o servicios compartidos entre catálogo, SP y presupuestos.
