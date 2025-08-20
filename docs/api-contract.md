# API Contract: Product Import System

## Overview
This document outlines the API contract for the product import system that supports multi-format file uploads (CSV, JSON, Excel) with validation, preview, and execution phases.

## Import Endpoints

### 1. Upload Endpoint
**Verb:** `POST`  
**Path:** `/api/proveedores/{proveedorId}/import`  
**Description:** Upload a file for import processing

#### Required Parameters:
- `file`: File upload (multipart/form-data)
  - **MIME Types:** `text/csv`, `application/json`, `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`, `application/vnd.ms-excel`
  - **Extensions:** `.csv`, `.txt`, `.json`, `.xlsx`, `.xls`
  - **Max Size:** 10MB
- `tipo`: Import type (string)
  - **Values:** `productos`, `marcas`, `lineas`, `categorias`

#### Response Shape:
```json
{
  "message": "Archivo cargado correctamente",
  "audit_id": 123,
  "job_id": "uuid-string",
  "formato": "csv|json|xlsx|xls|txt",
  "estado": "pendiente"
}
```

---

## API Audit Summary

### Discovered Endpoints:

1. **Upload Endpoint**: `POST /api/proveedores/{proveedorId}/import`
   - ✅ Found in routes/api.php line 88
   - ✅ Controller: ProductoImportController::upload
   - ✅ Supports: CSV, JSON, Excel files (max 10MB)
   - ✅ Required params: file, tipo

2. **Status Endpoint**: `GET /api/proveedores/{proveedorId}/import/{auditId}/status`
   - ✅ Found in routes/api.php line 89
   - ✅ Controller: ProductoImportController::status
   - ✅ Returns: Complete import audit with progress, logs, preview data

3. **Confirmation Endpoint**: `POST /api/proveedores/{proveedorId}/import/{auditId}/confirm`
   - ✅ Found in routes/api.php line 90
   - ✅ Controller: ProductoImportController::confirm
   - ✅ Executes import after preview approval

4. **Import History**: `GET /api/proveedores/{proveedorId}/imports`
   - ✅ Found in routes/api.php line 91
   - ✅ Controller: ProductoImportController::list
   - ✅ Returns: Paginated list of imports

5. **Template Download**: `GET /api/import/template/{tipo}`
   - ✅ Found in routes/api.php line 94
   - ✅ Controller: ProductoImportController::downloadTemplate
   - ✅ Returns: CSV template for import type

### Data Models Identified:

- **ImportAudit**: Main audit table with 20+ fields including progress tracking, logs, and validation results
- **Processing Phases**: parse → validate → preview → confirm → execute
- **Import States**: pendiente → procesando → preview → confirmado → completado/error
- **File Formats**: CSV, TXT, JSON, XLSX, XLS with auto-detection
- **Validation System**: Headers validation, row-by-row validation, business rules

### TypeScript Interfaces Created:

- ✅ `ImportAudit` - Main audit interface
- ✅ `ImportUploadRequest/Response` - Upload API contracts
- ✅ `ImportStatusResponse` - Status API contract
- ✅ `ValidationResults` - Error and warning structures
- ✅ `PreviewData` - Preview data structures
- ✅ `ImportService` - Service interface for frontend integration
- ✅ Complete type definitions for all enums and constants

### Implementation Status:

- ✅ **Backend API**: Fully implemented with Laravel
- ✅ **Job Processing**: Asynchronous with ImportarProductosJob
- ✅ **File Parsing**: Multi-format support (CSV, JSON, Excel)
- ✅ **Validation**: Comprehensive validation system
- ✅ **Database**: Complete migration schema
- ✅ **TypeScript Types**: Production-ready interfaces
- ✅ **Service Layer**: Complete implementation with validation

### Acceptable File Formats & Limits:

- **MIME Types**: `text/csv`, `application/json`, `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`, `application/vnd.ms-excel`
- **Extensions**: `.csv`, `.txt`, `.json`, `.xlsx`, `.xls`
- **Max File Size**: 10MB
- **CSV Delimiters**: Auto-detected (comma, semicolon, tab, pipe)
- **Encodings**: UTF-8, Latin-1

### Integration Points:

- **Authentication**: Requires Laravel Sanctum token
- **Authorization**: Provider-scoped access control
- **Real-time Updates**: Polling-based status monitoring
- **Error Handling**: Structured error responses with codes
- **File Storage**: Local storage with configurable paths

#### Error Responses:
- `422`: Validation error (file type, size, etc.)
- `500`: Server error during upload

---

### 2. Status/History Endpoint
**Verb:** `GET`  
**Path:** `/api/proveedores/{proveedorId}/import/{auditId}/status`  
**Description:** Get import status and detailed information

#### Response Shape:
```json
{
  "id": 123,
  "job_id": "uuid-string",
  "tipo": "productos",
  "formato": "csv",
  "estado": "pendiente|procesando|preview|confirmado|completado|error",
  "fase": "parse|validate|preview|confirm|execute|rollback",
  "progreso": 85,
  "total_registros": 1500,
  "nuevos": 1200,
  "actualizados": 280,
  "eliminados": 0,
  "errores": 20,
  "preview_data": {
    "resumen": {
      "productos_nuevos": 1200,
      "productos_actualizados": 280,
      "marcas_nuevas": ["Nike", "Adidas"],
      "lineas_nuevas": ["Deportivo", "Casual"],
      "categorias_nuevas": ["Calzado", "Ropa"]
    },
    "muestra_filas": [
      {
        "fila": 1,
        "datos": {
          "sku": "PROD-001",
          "nombre_producto": "Zapato deportivo",
          "precio_base": 150.00
        },
        "validacion": {
          "valido": true,
          "errores": [],
          "advertencias": ["Precio alto para la categoría"]
        }
      }
    ]
  },
  "errores_detalle": {
    "errors": [
      {
        "row": 5,
        "sku": "PROD-005",
        "errors": ["SKU duplicado", "Precio requerido"]
      }
    ],
    "warnings": [
      {
        "row": 10,
        "sku": "PROD-010", 
        "warnings": ["Descripción muy corta"]
      }
    ],
    "headers_validation": {
      "required_missing": ["precio_base"],
      "optional_missing": ["descripcion_producto"],
      "unknown_columns": ["extra_field"]
    }
  },
  "logs": [
    {
      "timestamp": "2024-01-15T10:30:00Z",
      "message": "Iniciando fase de parsing",
      "context": {}
    }
  ],
  "eta_seconds": 120,
  "mem_peak_mb": 45,
  "inicio_proceso": "2024-01-15T10:30:00Z",
  "fin_proceso": null
}
```

#### Error Responses:
- `404`: Import audit not found

---

### 3. Import Confirmation Endpoint
**Verb:** `POST`  
**Path:** `/api/proveedores/{proveedorId}/import/{auditId}/confirm`  
**Description:** Confirm and execute the import after preview

#### Response Shape:
```json
{
  "message": "Importación confirmada",
  "audit_id": 123
}
```

#### Error Responses:
- `400`: Cannot confirm this import (wrong state)
- `404`: Import audit not found

---

### 4. Import History List Endpoint
**Verb:** `GET`  
**Path:** `/api/proveedores/{proveedorId}/imports`  
**Description:** List all imports for a provider with pagination

#### Response Shape:
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 123,
      "job_id": "uuid-string",
      "tipo": "productos",
      "formato": "csv",
      "estado": "completado",
      "total_registros": 1500,
      "nuevos": 1200,
      "actualizados": 280,
      "errores": 20,
      "created_at": "2024-01-15T10:30:00Z",
      "fin_proceso": "2024-01-15T10:45:00Z"
    }
  ],
  "first_page_url": "...",
  "last_page": 5,
  "per_page": 10,
  "total": 47
}
```

---

### 5. Template Download Endpoint
**Verb:** `GET`  
**Path:** `/api/import/template/{tipo}`  
**Description:** Download CSV template for specific import type

#### Path Parameters:
- `tipo`: Template type (`productos`, `marcas`, `lineas`, `categorias`)

#### Response:
- **Content-Type:** `text/csv`
- **Content-Disposition:** `attachment; filename=template_{tipo}.csv`

#### Error Responses:
- `400`: Invalid template type

---

## Data Types and Enums

### Import States (Estado)
- `pendiente`: File uploaded, waiting to start
- `procesando`: Currently being processed
- `preview`: Preview generated, waiting for confirmation
- `confirmado`: Confirmed by user, starting execution
- `completado`: Successfully completed
- `error`: Failed with error

### Import Phases (Fase)
- `parse`: File parsing phase (0-20%)
- `validate`: Data validation phase (20-40%) 
- `preview`: Preview generation phase (40-60%)
- `confirm`: User confirmation received
- `execute`: Import execution phase (60-100%)
- `rollback`: Rolling back due to error

### Import Types (Tipo)
- `productos`: Product import
- `marcas`: Brand import
- `lineas`: Product line import
- `categorias`: Category import

### File Formats (Formato)
- `csv`: Comma-separated values
- `txt`: Tab-separated text file
- `json`: JSON format
- `xlsx`: Excel 2007+ format
- `xls`: Legacy Excel format

---

## CSV Column Requirements

### Required Columns (Productos):
- `sku`: Product identifier (string, unique)
- `nombre_producto`: Product name (string)
- `proveedor_id`: Provider ID (integer, auto-filled)

### Optional Columns (Productos):
- `nombre_modelo`: Model name (string)
- `codigo_interno`: Internal code (string)
- `descripcion_producto`: Product description (text)
- `nombre_marca`: Brand name (string)
- `nombre_linea`: Product line name (string)
- `nombre_categoria_nivel_1`: Category level 1 (string)
- `nombre_categoria_nivel_2`: Category level 2 (string)
- `nombre_categoria_nivel_3`: Category level 3 (string)
- `precio_base`: Base price (decimal)
- `precio_menudeo`: Public price (decimal)
- `precio_mayoreo`: Wholesale price (decimal)

### Acceptable MIME Types:
- `text/csv`
- `text/plain` (for .txt files)
- `application/json`
- `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet` (xlsx)
- `application/vnd.ms-excel` (xls)

---

## File Size & Format Limits
- **Maximum file size:** 10MB (10,240 KB)
- **Supported encodings:** UTF-8, Latin-1
- **CSV delimiters:** Comma (,), Semicolon (;), Tab (\t), Pipe (|)
- **JSON structure:** Array of objects with consistent keys
- **Excel sheets:** First sheet only, first row as headers

---

## Processing Phases Detail

### Phase 1: Parse (0-20%)
- File format detection
- Content reading and structure validation
- Row counting
- Initial error detection

### Phase 2: Validate (20-40%)
- Header validation against required/optional columns
- Data type validation per field
- Business rule validation
- Duplicate detection
- Relationship validation (categories, brands, etc.)

### Phase 3: Preview (40-60%)
- Preview data generation
- Sample rows with validation results
- Statistics calculation
- New entities identification
- Error and warning compilation

### Phase 4: Execute (60-100%)
- Database transaction start
- Entity creation/update in chunks
- Progress tracking
- Transaction commit or rollback
- Final statistics compilation

---

## Error Handling

### File Upload Errors:
- Invalid file type
- File too large
- Corrupted file
- Missing required parameters

### Processing Errors:
- Parse errors (malformed data)
- Validation errors (business rules)
- Database errors (constraints, relationships)
- Memory/timeout errors

### Error Response Format:
```json
{
  "message": "Error description",
  "error": "Technical error details",
  "code": 400
}
```
