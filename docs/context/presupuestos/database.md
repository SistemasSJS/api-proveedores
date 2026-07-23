# Presupuestos — Base de datos

## Models / tablas

| Model | Tabla | Rol |
|-------|-------|-----|
| `Presupuesto` | `presupuestos` | Documento principal |
| `PresupuestoConcepto` | `presupuesto_conceptos` | Líneas (concepto/párrafo); snapshot sin FK a productos |
| `PresupuestoAnexo` | `presupuesto_anexos` | Anexos imagen |
| `PresupuestoAnexoPdf` | `presupuesto_anexo_pdf` | Anexos PDF |
| `CarteraCliente` | `cartera_clientes` | Clientes del emisor (dominio presupuestos) |
| `PresupuestoCatalogoConcepto` | `presupuesto_catalogo_conceptos` | Biblioteca reutilizable de conceptos (Plus) |
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

## Folio (`numero_presupuesto`)

- Formato: `PRES-XXXX` (cero-padded, mínimo 4 dígitos).
- Fuente del siguiente folio: `proveedores.consecutivo_presupuesto_siguiente` (no la PK del presupuesto).
- Migración one-shot `2026_07_23_095250_bump_presupuesto_folios_by_200`: suma **200** al consecutivo numérico de cada `PRES-*` existente y realinea `consecutivo_presupuesto_siguiente` al `max(folio)+1` por proveedor (evita colisiones). Folios que no matchean `PRES-\d+` se omiten.

## Moneda

Campo típico `term_cond_moneda`: valores admitidos **MXN** | **USD** | **EUR** (default MXN).

## Conceptos

Tipos: `concepto` | `parrafo`. Campos libres: descripción, cantidad, unidad, precios, imagen. **Sin `producto_id`.**

Catálogo de conceptos reutilizable: tabla `presupuesto_catalogo_conceptos` (`descripcion`, `categoria` producto|servicio, `unidad`, `precio_unitario`, `imagen_path` opcional). Al usarlo en un presupuesto se hace **snapshot** a la línea (sin FK).

## Campos de documento (captura / PDF)

| Campo | Tabla | Notas |
|-------|-------|-------|
| `fecha_emision` | `presupuestos` | Fecha del documento; editable en UI (no futura en front) |
| `titulo_anexos` | `presupuestos` | `varchar(80)` nullable; migración `2026_07_23_095249_…`. Default de presentación **Anexos** si null/vacío (Resource, PDF sección imágenes, preview) |
| `titulo_anexos_pdf` | `presupuestos` | `varchar(80)` nullable; migración `2026_07_23_103654_…`. Default de presentación **Anexos PDF** (card de anexos PDF en captura; Resources) |

## Cobro (relacionado, no SP)

- Cuentas bancarias del proveedor: dominio de soporte en perfil (tabla `cuentas_bancarias` / model `CuentaBancaria`).
- Pasarelas PayPal/Stripe: **sin persistencia de integración** todavía.
