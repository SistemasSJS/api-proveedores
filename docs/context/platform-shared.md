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
| Roles | `UserRoleEnumerate` (ADMINISTRADOR, GERENTE, SUPERVISOR, VENTAS, AUXILIAR, …) | Rutas segmentadas + menú |
| Usuarios / matriz MVP | [platform-users-roles.md](./platform-users-roles.md) | Gestión empresa: principal GERENTE, roles asignables SUP/VEN/AUX |
| Storage / mail / FCM | Traits, Mail, Notifications genéricas | Archivos, correo, push |
| Shell menús (front) | `app-sidebar-menu` / `app-desktop-sidebar` | Dos menús distintos; ver sección siguiente |

## Shell UI (menús)

Hay **dos menús** (móvil drawer / escritorio rail) con **la misma línea visual y paleta**:

| Menú | Componente |
|------|------------|
| Móvil | `app-sidebar-menu` |
| Escritorio | `app-desktop-sidebar` |

Estructura común (ambos: `app-sidebar-menu` + `app-desktop-sidebar`; `sidebar-menu-sections.scss`):

1. **Cabecera** charcoal más oscuro `#222428` (texto blanco)
2. **Cuerpo** claro `#f4f9f9` — ítems legibles, activo primary
3. **Pie** `#2a2e34` (oscuro pero más claro que la cabecera): Invitar / Compartir + tarjeta usuario (con margen)

Sin hueco menú–contenido en desktop.

### Título del toolbar (AppStateService)

El shell muestra `appName$` + `headerSubtitle$`. Contrato obligatorio en pages (presupuestos, Mi Empresa, Mi Perfil, usuarios, etc.): regla Cursor `front-header-appstate.mdc` — `setHeader` en init/WillEnter, `clearHeader` en WillLeave (nunca en Destroy).

Al leer `route.data` en cadena de padres: **priorizar la ruta hoja**; no dejar que un padre genérico (`Proveedor`) pise títulos específicos (`Mi Empresa`, `Inicio`, …).

| Pantalla | title (routing) | subtitle tipico |
|----------|-----------------|-----------------|
| Mi Empresa | Mi Empresa | Perfil de la empresa (o segmento activo) |
| Mi Perfil | Mi Perfil | Datos de usuario |
| Inicio | Inicio | Panel de control / nombre del proveedor |
| Solicitudes de pago | Solicitudes de pago | listado, historial, crear, detalle… |
| Catálogo productos | Productos / Marcas / Categorías / Importación | según cada routing |
| Presupuestos | según `presupuesto-proveedor.routes.ts` / recursos | listado, crear, preview, clientes… |
| Dashboard Admin | Dashboard | Panel administrativo |

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
