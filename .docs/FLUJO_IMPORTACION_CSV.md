# Flujo de Importación CSV - Documentación

## Descripción del Flujo

Este documento describe el nuevo flujo de importación CSV reorganizado según los requerimientos especificados.

## Estructura del Flujo

### 1. SubirCsvPage 📁
**Ruta**: `/pages/proveedores/csv-import/csv`  
**Responsabilidad**: Selección y subida del archivo CSV

**Funcionalidades**:
- ✅ Drag & drop de archivos CSV
- ✅ Validación de formato y tamaño (10MB max)
- ✅ Validación básica de estructura CSV
- ✅ Llamada al backend para obtener preview
- ✅ Almacena datos en CsvImportService
- ✅ Navegación a TablaPreviewPage

**Backend Endpoint**: `POST /api/proveedores/{id}/csv-import`  
**Response Structure**: `CsvUploadResponse`

```typescript
// Datos almacenados en el servicio
interface CsvUploadApiResponse {
  audit_id: number;
  job_id: string;
  preview_token: string;
  file_info: { name, size, total_rows, preview_rows };
  headers: string[];
  preview_data: any[];
  validation_summary: object;
  quality_metrics: object;
  processing_info: object;
  estado: string;
  mensaje: string;
}
```

### 2. TablaPreviewPage 📋
**Ruta**: `/pages/proveedores/csv-import/preview`  
**Responsabilidad**: Mostrar datos con DatosFiscalesFormComponent

**Funcionalidades**:
- ✅ Carga datos del CsvImportService (reactive)
- ✅ Muestra tabla con datos del CSV
- ✅ Validación en tiempo real de productos
- ✅ Edición inline de datos
- ✅ Filtrado por estado (válidos/duplicados/errores)
- ✅ Estadísticas de validación
- ✅ Al procesar: muestra modal ConfirmarImportacion

**Datos Mostrados**:
```typescript
interface ProductRowData {
  codigo: string;
  producto: string;
  precio: number;
  // ... más campos
  validation: {
    status: 'valido' | 'duplicado' | 'error';
    errors: ValidationError[];
    warnings: string[];
  }
}
```

### 3. ConfirmarImportacionPage (Modal) 🔍
**Tipo**: Modal Controller  
**Responsabilidad**: Mostrar resumen de importación

**Funcionalidades**:
- ✅ Modal que muestra resumen estadístico
- ✅ Desglose de productos, marcas, categorías, unidades
- ✅ Diferenciación entre nuevos vs existentes
- ✅ Lista de errores y advertencias
- ✅ Botones Confirmar/Cancelar
- ✅ Al confirmar: emite evento y cierra modal

**Datos del Modal**:
```typescript
interface ImportValidationResult {
  valid: boolean;
  errors: ValidationError[];
  warnings: string[];
  summary: {
    total: number;
    nuevos: number;
    existentes: number;
    productos: number;
    marcas: number;
    categorias: number;
    unidades: number;
    errores: number;
    advertencias: number;
  };
  breakdown: {
    productos: { nuevos, existentes, duplicados, total };
    marcas: { nuevas, existentes, total };
    categorias: { nuevas, existentes, total };
    unidades: { nuevas, existentes, total };
  };
}
```

### 4. Proceso de Importación (Loading + Backend Call) ⏳
**Responsabilidad**: Ejecutar la importación real

**Proceso**:
1. ✅ Muestra loading spinner
2. ✅ Llama al backend con confirmImport()
3. ✅ Maneja la respuesta (éxito/error)
4. ✅ Al finalizar: muestra modal ResultadosImportacion

**Backend Endpoint**: `POST /api/proveedores/{id}/csv-import/confirm`  
**Request**:
```typescript
{
  audit_id: number;
  preview_token: string;
  import_options: {
    skip_duplicates: boolean;
    update_existing: boolean;
    create_missing_relations: boolean;
  }
}
```

**Response Structure**: `CsvConfirmResponse`

### 5. ResultadosImportacionPage (Modal) 📊
**Tipo**: Modal Controller  
**Responsabilidad**: Mostrar resultados finales

**Funcionalidades**:
- ✅ Modal con estadísticas finales
- ✅ Productos importados vs errores
- ✅ Tiempo de procesamiento
- ✅ Lista de elementos importados
- ✅ Opciones de exportación (Excel, PDF, CSV)
- ✅ Navegación de vuelta al inicio

**Datos del Modal**:
```typescript
interface ImportResults {
  success: boolean;
  totalProcessed: number;
  importedCount: number;
  errorCount: number;
  duration: number;
  fileName: string;
  timestamp: Date;
  breakdown: {
    productos: { imported, updated, errors, total };
    marcas: { imported, errors, total };
    categorias: { imported, errors, total };
    unidades: { imported, errors, total };
  };
  errors: ImportError[];
  warnings: ImportWarning[];
  importedItems: ImportedItem[];
}
```

## Backend API Responses

### 1. CsvUploadResponse (PHP)
```php
class CsvUploadResponse {
  public int $auditId;
  public string $jobId;
  public string $previewToken;
  public array $fileInfo;
  public array $headers;
  public array $previewData;
  public array $validationSummary;
  public array $qualityMetrics;
  public array $processingInfo;
  public string $estado = 'preview';
  public string $mensaje;
}
```

### 2. CsvConfirmResponse (PHP)
```php
class CsvConfirmResponse {
  public int $auditId;
  public string $estado; // 'completado' | 'error'
  public array $estadisticas;
  public array $resumen;
  public ?array $erroresDetalle = null;
}
```

### 3. CsvValidateProductResponse (PHP)
```php
class CsvValidateProductResponse {
  public bool $valido;
  public array $errores;
  public array $advertencias;
  public bool $existe;
  public ?array $productoExistente;
  public array $sugerencias;
  public array $accionesRecomendadas;
}
```

## Frontend Service Integration

### CsvImportService
**Responsabilidades**:
- ✅ Estado global reactivo con BehaviorSubjects
- ✅ Métodos para getPreview(), confirmImport(), validateProduct()
- ✅ Interfaces tipadas que coinciden con backend
- ✅ Adaptadores de datos entre backend/frontend
- ✅ Manejo de errores centralizado

```typescript
// Métodos principales
getPreview(request: CsvPreviewRequest, proveedorId: number): Observable<CsvPreviewResponse>
confirmImport(auditId: number, previewToken: string, proveedorId: number, options: any): Observable<CsvConfirmApiResponse>
validateProduct(producto: any, proveedorId: number, strict: boolean): Observable<CsvValidateProductApiResponse>

// Estado reactivo
public csvFile$ = this.csvFileSubject.asObservable();
public previewData$ = this.previewDataSubject.asObservable();
public validationResult$ = this.validationResultSubject.asObservable();
public importResult$ = this.importResultSubject.asObservable();
```

## Flujo de Datos

```
1. SubirCsv → Backend (upload) → CsvImportService.setPreviewData()
                                    ↓
2. TablaPreview ← CsvImportService.previewData$ (reactive)
                                    ↓
3. Usuario edita/valida → processData() → Modal ConfirmarImportacion
                                    ↓
4. Confirmar → Backend (confirm) → Modal ResultadosImportacion
                                    ↓
5. Finalizar → Navegación de vuelta
```

## Características Técnicas

### ✅ Implementado
- Estado global reactivo con RxJS
- Interfaces tipadas frontend/backend
- Clases Response estructuradas en PHP
- Modales con componentes reutilizables
- Manejo de errores centralizado
- Loading states y UX optimizada
- Validación en tiempo real
- Edición inline de datos

### 🔄 Flujo de Navegación
```
/csv (SubirCsv) 
  → /preview (TablaPreview) 
    → Modal(ConfirmarImportacion) 
      → Loading + Backend Call 
        → Modal(ResultadosImportacion) 
          → /csv (vuelta al inicio)
```

### 📱 Responsive Design
- ✅ Tabla adaptativa para móvil/desktop
- ✅ Modales responsive con Ionic
- ✅ Componentes touch-friendly

## Conclusión

El flujo ha sido completamente reestructurado para seguir el patrón solicitado:
1. **SubirCsv**: Upload y preview
2. **TablaPreview**: Validación y edición con DatosFiscales
3. **ConfirmarImportacion**: Modal con resumen
4. **Loading**: Procesamiento con spinner
5. **ResultadosImportacion**: Modal con resultados

Todas las interfaces están tipadas y sincronizadas entre backend y frontend, proporcionando una experiencia de usuario fluida y mantenible.
