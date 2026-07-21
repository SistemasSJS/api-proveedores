# Presupuestos — Base de datos

## Models / tablas

| Model | Tabla | Rol |
|-------|-------|-----|
| `Presupuesto` | `presupuestos` | Documento principal |
| `PresupuestoConcepto` | `presupuesto_conceptos` | Líneas (concepto/párrafo); snapshot sin FK a productos |
| `PresupuestoAnexo` | `presupuesto_anexos` | Anexos imagen |
| `PresupuestoAnexoPdf` | `presupuesto_anexo_pdf` | Anexos PDF |
| `CarteraCliente` | `cartera_clientes` | Clientes del emisor (dominio presupuestos) |
| `ConfigEmisorReceptorPresupuesto` | `config_emisor_receptor_presupuestos` | Tarjetas emisor/receptor |

## Relaciones clave (`Presupuesto`)

- Emisor: `proveedor_id` → `Proveedor`
- Receptor: `empresa_receptora_id` → `CarteraCliente` **o** `proveedor_receptor_id` → `Proveedor` **o** solo campos texto
- Snapshot emisor: `config_emisor_presupuesto_id` + campos `empresa_emisora_*`
- Hijos: `conceptos`, `anexos`, `anexosPdf`
- Público: `token_publico`

## Estados

`borrador` | `enviado` | `aceptado` | `rechazado` | `rechazado_con_observacion` | `vencido`

Roadmap (aún no en BD de presupuesto): estados o flags de **pago** / **finalización**.

## Moneda

Campo típico `term_cond_moneda`: valores admitidos **MXN** | **USD** | **EUR** (default MXN).

## Conceptos

Tipos: `concepto` | `parrafo`. Campos libres: descripción, cantidad, unidad, precios, imagen. **Sin `producto_id`.**

Catálogo de conceptos reutilizable: **pendiente** (no hay tabla/API dedicada aún).

## Cobro (relacionado, no SP)

- Cuentas bancarias del proveedor: dominio de soporte en perfil (tabla `cuentas_bancarias` / model `CuentaBancaria`).
- Pasarelas PayPal/Stripe: **sin persistencia de integración** todavía.
