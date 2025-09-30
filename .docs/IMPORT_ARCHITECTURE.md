# Nueva Arquitectura de Importaciones

## Resumen de Cambios

Se ha reorganizado y optimizado la arquitectura backend para la gestión de importaciones de productos, implementando un sistema más robusto y trazable.

## Componentes Implementados

### 1. Controllers

#### ImportHistoryController
- **Ubicación**: `app/Http/Controllers/ImportHistoryController.php`
- **Función**: Gestión completa del historial de importaciones
- **Métodos**:
  - `index()`: Listado paginado con filtros avanzados
  - `show()`: Detalle de una importación específica
  - `store()`: Crear nueva entrada de importación
  - `update()`: Actualizar registro de importación
  - `destroy()`: Eliminar registro de importación
  - `import()`: Ejecutar proceso de importación de productos

### 2. FormRequests

#### ImportHistoryIndexRequest
- **Ubicación**: `app/Http/Requests/ImportHistory/ImportHistoryIndexRequest.php`
- **Función**: Validación y filtros para listados de importaciones
- **Filtros soportados**:
  - `search`: Búsqueda por archivo, job_id, tipo
  - `tipo`: productos, marcas, categorias, mixed
  - `estado`: pending, processing, completed, failed, cancelled
  - `formato`: csv, xlsx, json
  - `date_from/date_to`: Rango de fechas
  - `fase`: upload, validation, processing, cleanup, completed
  - `has_errors`: Con/sin errores
  - `min_registros/max_registros`: Rango de registros

#### ImportHistoryStoreRequest
- **Ubicación**: `app/Http/Requests/ImportHistory/ImportHistoryStoreRequest.php`
- **Función**: Validación para creación/actualización de importaciones
- **Campos validados**: job_id, tipo, archivo, formato, estado, fase, etc.

### 3. Resources

#### ImportHistoryResource
- **Ubicación**: `app/Http/Resources/ImportHistoryResource.php`
- **Función**: Formateo de respuestas individuales
- **Características**:
  - Formateado de fechas y duraciones
  - Cálculo de estadísticas (tasa de éxito, errores)
  - Labels legibles para estados y tipos
  - Colores para estados (success, warning, danger, etc.)

#### ImportHistoryCollection
- **Ubicación**: `app/Http/Resources/ImportHistoryCollection.php`
- **Función**: Formateo de listados paginados
- **Características**:
  - Estadísticas de colección
  - Meta información de paginación
  - Opciones de filtros disponibles
  - Resúmenes por estado, tipo y formato

### 4. Services

#### ImportService
- **Ubicación**: `app/Services/ImportService.php`
- **Función**: Lógica dedicada para procesamiento de importaciones
- **Características**:
  - Procesamiento por lotes (chunking)
  - Integración automática con ImportAudit
  - Logging detallado de progreso
  - Manejo robusto de errores
  - Transacciones por lote

### 5. Modelo ImportAudit Mejorado

#### Nuevos Scopes y Métodos
- **Ubicación**: `app/Models/ImportAudit.php`
- **Mejoras**:
  - Scope `filter()` para búsquedas complejas
  - Método `getFilters()` para campos filtrables
  - Filtros JSON para búsquedas en logs
  - Índices optimizados para performance

## Rutas Implementadas

### Nuevas Rutas de Historial
```php
// Historial de importaciones
GET    /api/proveedores/{proveedor}/import-history        // Listado con filtros
POST   /api/proveedores/{proveedor}/import-history        // Crear registro
GET    /api/proveedores/{proveedor}/import-history/{id}   // Ver detalle
PATCH  /api/proveedores/{proveedor}/import-history/{id}   // Actualizar
DELETE /api/proveedores/{proveedor}/import-history/{id}   // Eliminar

// Nueva ruta de importación optimizada
POST   /api/proveedores/{proveedor}/productos/import      // Importar con auditoría
```

### Compatibilidad Mantenida
Las rutas existentes se mantienen para compatibilidad:
```php
POST   /api/proveedores/{proveedor}/productos/bulk       // Delegado al servicio
POST   /api/proveedores/{proveedor}/imports/products     // Sistema anterior
```

## Mejoras de Base de Datos

### Índices Agregados
- `idx_import_audits_proveedor_tipo`: Búsquedas por proveedor y tipo
- `idx_import_audits_proveedor_estado`: Filtros por estado
- `idx_import_audits_proveedor_fecha`: Ordenamiento por fecha
- `idx_import_audits_estado_fecha`: Búsquedas temporales
- `idx_import_audits_total_registros`: Filtros por cantidad
- `idx_import_audits_errores`: Filtros por errores

### Campo job_id
- Ahora es nullable para mayor flexibilidad
- Mantiene unicidad cuando se proporciona

## Características Principales

### 1. Sistema de Filtros Avanzados
- Búsqueda textual en múltiples campos
- Filtros por rango de fechas y registros
- Búsqueda en logs JSON
- Combinación de múltiples filtros

### 2. Auditoría Completa
- Registro automático de todas las operaciones
- Tracking de progreso en tiempo real
- Logs estructurados con timestamps
- Métricas de performance (memoria, tiempo)

### 3. Respuestas Enriquecidas
- Estadísticas calculadas automáticamente
- Formateo inteligente de datos
- Meta información completa
- Opciones de filtros disponibles

### 4. Performance Optimizado
- Índices de base de datos específicos
- Procesamiento por lotes eficiente
- Queries optimizadas para filtros
- Eager loading inteligente

## Uso de la API

### Listado con Filtros
```http
GET /api/proveedores/1/import-history?search=productos&estado=completed&per_page=15
```

### Importación con Auditoría
```http
POST /api/proveedores/1/productos/import
Content-Type: application/json

{
  "productos": [
    {
      "codigo": "ABC123",
      "producto": "Producto Test",
      "precio": 100.50,
      "marca": "Marca Test",
      "categoria": "Categoria Test"
    }
  ]
}
```

### Ver Detalle de Importación
```http
GET /api/proveedores/1/import-history/123
```

## Migración Gradual

1. **Mantenimiento de Compatibilidad**: Las rutas existentes siguen funcionando
2. **Migración Sugerida**: Usar `/productos/import` en lugar de `/productos/bulk`
3. **Monitoreo**: Usar `/import-history` para seguimiento de importaciones
4. **Depuración**: Los logs detallados facilitan resolución de problemas

## Beneficios Obtenidos

1. **Trazabilidad Completa**: Cada importación queda registrada con detalles
2. **Mejor UX**: Respuestas más ricas e informativas
3. **Filtros Potentes**: Búsquedas complejas y flexibles  
4. **Performance**: Índices optimizados y procesamiento eficiente
5. **Mantenibilidad**: Código más organizado y modular
6. **Escalabilidad**: Arquitectura preparada para crecimiento
