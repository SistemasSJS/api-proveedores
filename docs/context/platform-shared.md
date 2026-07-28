# Plataforma compartida (no es un dominio de negocio)

Infraestructura que usan los tres dominios. **No expandir** como “módulo núcleo” ni meter lógica de catálogo/SP/presupuestos aquí.

## Stack

- **API:** Laravel, Sanctum, Eloquent — repo `api-proveedores`
- **App:** Angular + Ionic PWA — repo `app-proveedores`
- **Contrato JSON:** `status`, `code`, `message`, `data`, `errors` (`ApiResponse`)

## Alcance mínimo

| Pieza | Dónde | Uso |
|-------|--------|-----|
| Auth / sesión | `routes/segmented/auth.php`, front `@auth/` | Login, token, `/auth/me` |
| Proveedor | Model `Proveedor`, prefijo `proveedores/{proveedor}/…` | Contenedor multi-proveedor |
| Acceso | `tieneAccesoAProveedor`, middleware `proveedor.access` / `api.access` | Autorización por proveedor |
| Roles | `UserRoleEnumerate` (ADMINISTRADOR, GERENTE, CLIENTE, CONSTRUCC_APP, …) | Rutas segmentadas |
| Storage / mail / FCM | Traits, Mail, Notifications genéricas | Archivos, correo, push |
| Shell menús (front) | `app-sidebar-menu` / `app-desktop-sidebar` | Dos menús distintos; ver sección siguiente |

## Shell UI (menús)

Hay **dos menús** (móvil drawer / escritorio rail) con **la misma línea visual y paleta**:

| Menú | Componente |
|------|------------|
| Móvil | `app-sidebar-menu` |
| Escritorio | `app-desktop-sidebar` |

Estructura común: marca/empresa (cabecera clara) → nav navy → tarjeta usuario + logout abajo. Grupos expandibles. Tokens en `sidebar-menu-sections.scss` (`$sidebar-dark` ~ `#243b55`, primary Gestión para activo).

## Qué no va aquí

- Productos, categorías, marcas → [catalogo](./catalogo/)
- Solicitudes de pago, facturas, OC → [solicitudes-pago](./solicitudes-pago/)
- Presupuestos, conceptos, cartera de presupuesto → [presupuestos](./presupuestos/)

## Rutas segmentadas (mapa)

| Archivo | Contenido típico |
|---------|------------------|
| `routes/segmented/auth.php` | Autenticación |
| `routes/segmented/gerente.php` | API del gerente/proveedor (mezcla prefijos de los 3 dominios; aislar por path) |
| `routes/segmented/admin.php` | Panel administrador |
| `routes/segmented/public.php` | Endpoints públicos |
| `routes/segmented/construcc.php` | Consumidor externo (sobre todo SP + algo de catálogo) |
| `routes/segmented/notifications.php` | Webhooks / notificaciones |

Aunque `gerente.php` agrupe varios prefijos, **cada prefijo pertenece a un solo dominio**.
