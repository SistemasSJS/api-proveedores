# 📋 Módulo de Construcción - API Laravel + Angular

> Implementación completa del módulo de construcción con rutas, controladores, servicios y Form Requests para validaciones.

## 📁 Estructura de Archivos Creados

### **Backend (Laravel)**

#### 🛣️ Rutas
- `routes/segmented/construcc.php` - Archivo principal de rutas del módulo

#### 🎛️ Controladores
- `app/Http/Controllers/ConstruccController.php` - Controlador principal

#### 🔧 Servicios
- `app/Services/ConstruccSearchService.php` - Servicio de búsqueda avanzada

#### ✅ Form Requests (Validaciones)
- `app/Http/Requests/Construcc/ProveedoresFilterRequest.php`
- `app/Http/Requests/Construcc/ProveedoresBusquedaRequest.php` 
- `app/Http/Requests/Construcc/ProductosProveedorRequest.php`
- `app/Http/Requests/Construcc/ProductosBusquedaRequest.php`
- `app/Http/Requests/Construcc/SugerenciasProductosRequest.php`
- `app/Http/Requests/Construcc/CategoriasProveedorRequest.php`

#### 📦 Resources
- `app/Http/Resources/construcc/ConstruccProveedorResource.php`
- `app/Http/Resources/construcc/ConstruccProductoResource.php`
- `app/Http/Resources/construcc/ConstruccCategoriaResource.php`
- `app/Http/Resources/construcc/ConstruccMarcaResource.php`
- `app/Http/Resources/construcc/ConstruccLineaResource.php`
- `app/Http/Resources/construcc/ConstruccUnidadResource.php`

### **Frontend (Angular)**
- `construcc.service.ts` - Servicio Angular con tipado TypeScript completo

---

## 🔐 Autenticación

**Todas las rutas requieren autenticación API Token (Sanctum)**

```bash
Authorization: Bearer {tu_token_sanctum}
```

---

## 📊 Endpoints Disponibles

### **🏢 PROVEEDORES (Con Paginación)**

#### `GET /api/construcc/proveedores`
Lista paginada de proveedores con filtros básicos.

**Parámetros:**
```bash
buscar=texto           # Búsqueda en nombre, razón social, RFC
estado=Estado          # Filtro por estado
municipio=Municipio    # Filtro por municipio
tipos_empresa_id=1,2,3 # Múltiples tipos de empresa
con_productos=true     # Solo proveedores con productos
sort_by=nombre_comercial # Campo de ordenamiento
order=asc              # Dirección del ordenamiento
per_page=20           # Elementos por página (5-100)
page=1                # Número de página
```

#### `GET /api/construcc/proveedores/buscar`
Búsqueda avanzada de proveedores con filtros extendidos.

**Parámetros adicionales:**
```bash
categoria_id=1,2,3     # Filtrar proveedores con productos en estas categorías
marca_id=4,5,6         # Filtrar proveedores con productos de estas marcas
```

#### `GET /api/construcc/proveedores/{id}/productos`
Productos de un proveedor específico (paginado).

**Parámetros:**
```bash
categoria_id=1,2,3     # Filtro por categorías
marca_id=4,5           # Filtro por marcas
linea_id=6,7           # Filtro por líneas
con_stock=true         # Solo productos con stock
destacado=true         # Solo productos destacados
sort_by=nombre         # Campo ordenamiento
order=asc              # Dirección ordenamiento
```

#### `GET /api/construcc/proveedores/{id}/productos/buscar`
Búsqueda avanzada en productos de un proveedor.

**Parámetros adicionales:**
```bash
buscar=cemento         # Búsqueda en nombre, descripción, SKU
precio_min=100         # Precio mínimo
precio_max=5000        # Precio máximo
```

---

### **🛍️ PRODUCTOS (Con Paginación)**

#### `GET /api/construcc/productos/buscar`
Búsqueda general de productos con filtros múltiples.

**Parámetros:**
```bash
buscar=texto               # Búsqueda general
proveedor_id=1,2,3         # Múltiples proveedores
categoria_id=1,2,3         # Múltiples categorías
subcategoria_id=4,5,6      # Múltiples subcategorías
marca_id=7,8,9             # Múltiples marcas
linea_id=10,11             # Múltiples líneas
unidad_medida_id=12,13     # Múltiples unidades
precio_min=100             # Precio mínimo
precio_max=5000            # Precio máximo
con_stock=true             # Solo con stock
destacado=true             # Solo destacados
orden_por=precio_base      # Campo ordenamiento
direccion=asc              # Dirección ordenamiento
```

#### `GET /api/construcc/productos/filtros`
Obtiene todos los filtros disponibles para productos.

#### `GET /api/construcc/productos/sugerencias`
Sugerencias para autocompletado.

**Parámetros:**
```bash
termino=cem            # Término de búsqueda (requerido)
proveedor_id=1         # Filtrar por proveedor (opcional)
limite=10              # Límite de resultados (5-50)
```

---

### **📋 CATÁLOGOS (Sin Paginación)**

#### `GET /api/construcc/catalogos/proveedores/{id}/marcas`
Marcas de un proveedor específico.

#### `GET /api/construcc/catalogos/proveedores/{id}/categorias`
Categorías de un proveedor.

**Parámetros:**
```bash
incluir_subcategorias=true  # Incluir subcategorías anidadas
solo_padres=true           # Solo categorías padre
```

#### `GET /api/construcc/catalogos/proveedores/{id}/lineas`
Líneas de un proveedor específico.

#### `GET /api/construcc/catalogos/proveedores/{id}/unidades`
Unidades de medida de un proveedor específico.

#### `GET /api/construcc/catalogos/proveedores/{id}/completos`
Todos los catálogos de un proveedor en una respuesta.

---

### **📈 REPORTES Y ESTADÍSTICAS**

#### `GET /api/construcc/reportes/estadisticas`
Estadísticas generales del módulo.

#### `GET /api/construcc/reportes/proveedores/{id}/resumen`
Resumen específico de un proveedor.

---

### **⚙️ CONFIGURACIÓN**

#### `GET /api/construcc/config/filtros-disponibles`
Lista de todos los filtros disponibles.

#### `GET /api/construcc/config/opciones-ordenamiento`
Opciones de ordenamiento disponibles.

---

## 🔧 Form Requests (Validaciones)

### **ProveedoresFilterRequest**
- Valida filtros básicos de proveedores
- Convierte automáticamente `con_productos` a boolean
- Establece valores por defecto para paginación

### **ProveedoresBusquedaRequest**
- Validaciones extendidas para búsqueda avanzada
- Soporte para filtros múltiples con regex `/^[\d,]+$/`
- Manejo de parámetros de ordenamiento

### **ProductosBusquedaRequest**
- Validaciones completas para búsqueda de productos
- Validación de rangos de precios (`precio_max > precio_min`)
- Soporte para filtros múltiples en todos los campos

### **SugerenciasProductosRequest**
- Validación específica para autocompletado
- Validación de existencia de proveedor
- Límites de resultados controlados

### **CategoriasProveedorRequest**
- Validación de parámetros boolean para categorías
- Conversión automática de strings a boolean

---

## 📱 Servicio Angular

### **Configuración**
```typescript
// Configuración de la URL base
private readonly apiUrl = 'http://localhost:8000/api';
private readonly baseUrl = `${this.apiUrl}/construcc`;
```

### **Manejo de Token**
```typescript
// El servicio busca automáticamente el token en:
localStorage.getItem('api_token') || sessionStorage.getItem('api_token')
```

### **Ejemplos de Uso**

#### Obtener Proveedores
```typescript
this.construccService.getProveedores({
  buscar: 'construccion',
  estado: 'Mexico',
  per_page: 20,
  page: 1
}).subscribe({
  next: (response) => {
    console.log('Proveedores:', response.data);
    console.log('Total:', response.pagination.total);
  },
  error: (error) => console.error('Error:', error)
});
```

#### Buscar Productos con Filtros Múltiples
```typescript
this.construccService.buscarProductos({
  buscar: 'cemento',
  categoria_id: [1, 2, 3], // Se convierte automáticamente a "1,2,3"
  marca_id: '4,5,6',       // También acepta string
  precio_min: 100,
  precio_max: 5000,
  con_stock: true,
  sort_by: 'precio_base',
  order: 'asc'
}).subscribe({
  next: (response) => {
    console.log('Productos encontrados:', response.data);
  },
  error: (error) => console.error('Error:', error)
});
```

#### Autocompletado de Productos
```typescript
this.construccService.getSugerenciasProductos('cem', 1, 5).subscribe({
  next: (response) => {
    console.log('Sugerencias:', response.data.sugerencias);
    // Usar en ion-searchbar o mat-autocomplete
  }
});
```

---

## 🚀 Características Destacadas

### **✅ Validaciones Robustas**
- Form Requests específicos para cada endpoint
- Validación de formatos múltiples (`1,2,3`)
- Conversión automática de tipos de datos
- Mensajes de error personalizados en español

### **🔍 Filtros Avanzados**
- Soporte para filtros múltiples en un solo parámetro
- Validación automática de formatos
- Conversión flexible entre arrays y strings

### **📄 Paginación Inteligente**
- Configuración automática de valores por defecto
- Límites configurables por endpoint
- Respuestas consistentes con metadatos de paginación

### **🎯 Tipado TypeScript Completo**
- Interfaces para todas las respuestas
- Tipado estricto para parámetros de filtros
- IntelliSense completo en el IDE

### **🛡️ Seguridad**
- Autenticación requerida en todos los endpoints
- Validación de existencia de recursos
- Middleware de auditoría habilitado

### **⚡ Performance**
- Eager loading optimizado
- Resources específicos para reducir payload
- Caché de consultas frecuentes

---

## 📋 Ejemplos de Respuesta

### **Respuesta Paginada**
```json
{
  "success": true,
  "message": "Datos obtenidos correctamente",
  "data": [...],
  "pagination": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 20,
    "to": 20,
    "total": 100,
    "next_page_url": "...",
    "prev_page_url": null
  }
}
```

### **Respuesta Simple**
```json
{
  "success": true,
  "message": "Operación exitosa",
  "data": {
    "marcas": [...],
    "total": 15
  }
}
```

---

## 🔧 Instalación y Configuración

### **Backend (Laravel)**
1. Las rutas se registran automáticamente en `routes-segmented.php`
2. El servicio `ConstruccSearchService` se inyecta automáticamente
3. Los Form Requests validan automáticamente las peticiones

### **Frontend (Angular)**
1. Importar el servicio en tu componente:
```typescript
import { ConstruccService } from './services/construcc.service';
```

2. Configurar la URL base según tu entorno
3. Asegurar que el token esté almacenado correctamente

---

## 🐛 Manejo de Errores

### **Errores Comunes y Soluciones**

**401 - No autorizado**
```typescript
// Verificar que el token esté presente y válido
const token = localStorage.getItem('api_token');
if (!token) {
  // Redirigir a login
}
```

**422 - Errores de validación**
```typescript
// Los Form Requests devuelven errores específicos
{
  "message": "Los datos proporcionados no son válidos.",
  "errors": {
    "categoria_id": ["Las categorías deben tener el formato: 1,2,3"]
  }
}
```

**404 - Recurso no encontrado**
```typescript
// El proveedor especificado no existe o no está activo
```

---

## 📚 Documentación Técnica

### **Patrones Implementados**
- Repository Pattern (SearchService)
- Resource Pattern (API Resources)
- Request Pattern (Form Requests)
- Service Pattern (Angular Services)

### **Middleware Aplicados**
- `auth:sanctum` - Autenticación requerida
- `audit` - Registro de actividad del usuario

### **Optimizaciones**
- Eager Loading selectivo
- Paginación eficiente
- Caché de consultas de catálogos
- Resources optimizados por contexto

---

## 🎯 Próximos Pasos

1. **Testing**: Implementar tests unitarios para los Form Requests
2. **Cache**: Agregar caché Redis para consultas frecuentes
3. **Swagger**: Generar documentación automática de la API
4. **Rate Limiting**: Implementar límites de peticiones por minuto

---

**🎉 ¡El módulo de construcción está listo para usar!**

Todos los archivos han sido creados e implementados siguiendo las mejores prácticas de Laravel y Angular, con validaciones robustas, tipado completo y documentación detallada.
