# Usuarios, roles y accesos (plataforma / core)

**No es un dominio de negocio** (no catálogo, no SP, no presupuestos). Forma parte del **core de plataforma**, junto con auth y `proveedor_id`.

Documento de referencia del **MVP de permisos fijos por rol** (sin facultades por usuario).

## Alcance del módulo de gestión

| Área | Qué cubre en MVP |
|------|------------------|
| **Usuarios** | Alta, consulta, edición, baja (según rol) dentro de un proveedor |
| **Roles** | Solo **asignación** de plantillas fijas: `SUPERVISOR`, `VENTAS`, `AUXILIAR` |
| **Empresas** | Contexto “mi empresa”; reasignación entre empresas = **solo admin** |

## Usuario principal

- Solo puede existir **un** vínculo `PRINCIPAL` activo por proveedor.
- En MVP: principal = rol **`GERENTE`** + pivot **`PRINCIPAL`**.
- Acceso completo a la empresa (sin restricciones de la matriz).
- **No** se puede borrar desde la gestión de usuarios de la empresa.
- Un **ADMINISTRADOR** sí puede borrar / reasignar / cambiar ese vínculo (panel admin).

Desde la gestión de empresa (gerente/supervisor):

- Solo se crean usuarios **`SECUNDARIO`**.
- Solo se asignan roles **`SUPERVISOR` | `VENTAS` | `AUXILIAR`** (nunca `GERENTE` ni `ADMINISTRADOR`).

## Quién gestiona usuarios

| Acción | GERENTE (principal) | SUPERVISOR | VENTAS / AUXILIAR | ADMIN |
|--------|---------------------|------------|-------------------|-------|
| Ver menú / listar / detalle | T | T | — | T |
| Crear / editar | T | T | — | T |
| Borrar (no principal) | T | — | — | T |
| Borrar principal | — | — | — | T |
| Reasignar a otra empresa | — | — | — | T |

## Matriz de acceso por módulo (MVP)

Letras: **T** = todo · **R** = solo consultar · **—** = sin acceso.

### General

| Capacidad | GERENTE | SUPERVISOR | VENTAS | AUXILIAR |
|-----------|---------|------------|--------|----------|
| Inicio (dashboard) | T | T | T | T |
| Mi Empresa | T | T | — | — |

### Gestión de usuarios

| Capacidad | GERENTE | SUPERVISOR | VENTAS | AUXILIAR |
|-----------|---------|------------|--------|----------|
| Usuarios C · R · U | T | T | — | — |
| Usuarios D | T* | — | — | — |

\* Excepto el principal.

### Presupuestos (dominio; el menú/API respetan esta matriz)

| Capacidad | GERENTE | SUPERVISOR | VENTAS | AUXILIAR |
|-----------|---------|------------|--------|----------|
| Presupuestos | T | T | T | R |
| Clientes | T | T | T | R |
| Catálogo de conceptos | T | T | T | R |
| Tarjetas de presentación | T | T | T | R |

### Solicitudes de pago (SPP)

| Capacidad | GERENTE | SUPERVISOR | VENTAS | AUXILIAR |
|-----------|---------|------------|--------|----------|
| Módulo SPP | T | T | — | — |

### Catálogo de productos (aún no redefinido en esta ronda)

Sigue visible solo para **GERENTE** en menú hasta nueva definición.

## Enforcement (MVP)

1. **Front menú** (`user-menu-new.ts`): `roles: [...]` por ítem según esta matriz.
2. **API usuarios** (`ProveedorUsuarioController` + requests): acceso CRU gerente/supervisor; delete solo gerente (o admin); whitelist de `role_id`; fuerza `SECUNDARIO`.
3. **Dominios (presupuestos / SP):** menú alineado; endurecer endpoints por rol de forma incremental (misma matriz).
4. **No hay** UI de facultades por usuario en esta versión.

## Config / código de referencia

| Pieza | Ubicación |
|-------|-----------|
| Config MVP | `config/proveedor_gestion_mvp.php` |
| Enum roles | `App\Enums\UserRoleEnumerate` |
| Controller usuarios | `App\Http\Controllers\ProveedorUsuarioController` |
| Menú front | `app-proveedores/.../pages/user-menu-new.ts` |
| Roles en forms | filtro a SUP / VEN / AUX |

## Qué no hacer

- Inventar facultades por usuario sin actualizar este doc.
- Permitir que el gerente asigne `GERENTE` / `ADMINISTRADOR` desde la app empresa.
- Mezclar esta lógica dentro de `docs/context/catalogo|solicitudes-pago|presupuestos` — aquí es plataforma.

## Relación con dominios

Ver [cross-domain.md](./cross-domain.md): auth, roles y gestión de usuarios son **plataforma → cualquiera**; no crean puente presupuesto↔SP.
