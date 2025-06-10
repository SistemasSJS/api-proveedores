# 📦 Modelo Base para Sistema de Proveedores y Requisiciones

## 🏗️ Estructura General del Sistema

El sistema permitirá que proveedores suban y gestionen su catálogo de productos.
Los usuarios finales podrán realizar requisiciones sobre estos productos.

## 🗂️ Entidades Principales

1. **Proveedor**

   - Puede tener una o varias sucursales.
   - Puede tener usuarios asociados:
     - Usuario Principal (Main)
     - Usuarios secundarios (Asociados)
   - Gestiona su catálogo de productos completo.

2. **Producto**

   - Relacionado con:
     - Proveedor
     - Marca
     - Línea
     - Categoría (con subcategorías anidadas hasta 2 niveles)
     - Catálogo de fotos (múltiples imágenes)
     - Tabla de especificaciones (estructura flexible, tipo key-value)

3. **Marca**

   - Relacionada a los productos.
   - Es gestionada por cada proveedor, no es global.

4. **Línea**

   - Asociada a marcas y productos.
   - También específica por proveedor.

5. **Categoría**

   - Soporta subcategorías anidadas hasta 2 niveles.
   - Ejemplo:
     Categoría Padre → Subcategoría Nivel 1 → Subcategoría Nivel 2

6. **Sucursal**

   - Múltiples sucursales por proveedor.
   - Los productos pueden asociarse a sucursales específicas (si es necesario).

7. **Especificaciones**

   - Tabla adicional que permite agregar propiedades dinámicas al producto.
   - Ejemplo: Peso, Color, Material, Capacidad, etc.

8. **Imágenes**
   - Múltiples imágenes por producto.
   - Pueden organizarse como galería/catálogo.

## 👥 Gestión de Usuarios y Roles

| Rol           | Descripción / Acceso                                                                |
| ------------- | ----------------------------------------------------------------------------------- |
| ADMINISTRADOR | Acceso total a todo el sistema, configuración y administración general.             |
| GERENTE       | Gestión integral de proveedores, catálogos y supervisores.                          |
| SUPERVISOR    | Supervisión de operaciones diarias, control parcial sobre usuarios y requisiciones. |
| VENTAS        | Acceso para gestionar requisiciones, clientes y ventas, sin acceso administrativo.  |
| AUXILIAR      | Permisos limitados, apoyo en tareas específicas, sin acceso a funciones críticas.   |

### Asociación de Usuarios

| Usuario       | proveedor_id (nullable) |
| ------------- | ----------------------- |
| ADMINISTRADOR | NULL                    |
| GERENTE       | ID del proveedor        |
| SUPERVISOR    | ID del proveedor        |
| VENTAS        | ID del proveedor        |
| AUXILIAR      | ID del proveedor        |

### Sugerencias Técnicas

- Middleware por rol para controlar acceso granular.
- Policies para validar la propiedad y permisos sobre recursos.
- Recomendable implementar control de acceso estrictamente basado en roles para evitar fugas de datos o modificaciones no autorizadas.

## 📋 Requisiciones

- Los clientes pueden realizar requisiciones sobre productos de distintos proveedores.
- Cada requisición debe registrar:
  - Usuario que la genera.
  - Productos requeridos.
  - Proveedor asociado.

## 🔐 Seguridad y Acceso

- Los proveedores solo pueden acceder a su propio catálogo.
- Los proveedores asociados tienen acceso restringido según configuración.
- Las operaciones CRUD deben estar protegidas por middleware y policies.

## 💾 Sugerencia de Tablas (Simplificada)

- `users` (rol_id, proveedor_id nullable)
- `roles` (ADMINISTRADOR, GERENTE, SUPERVISOR, VENTAS, AUXILIAR)
- `proveedors`
- `sucursals`
- `productos`
- `categorias` (parent_id para anidación)
- `marcas`
- `lineas`
- `imagenes` (producto_id)
- `especificaciones` (producto_id, key, value)
- `requisiciones` (usuario_id, proveedor_id, fecha)
- `requisicion_productos` (requisicion_id, producto_id, cantidad)

## 🚀 Ventajas del Modelo Propuesto

- Escalable.
- Multi-tenant (cada proveedor ve solo sus datos).
- Seguridad segmentada.
- Soporta catálogos complejos con marcas, líneas, subcategorías y especificaciones.
- Optimizado para CRUD eficientes y filtros dinámicos.
