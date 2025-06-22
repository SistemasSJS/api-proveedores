// =====================================
// Import System TypeScript Interfaces
// =====================================

// Enums for import system
export type ImportState = 
  | 'pendiente' 
  | 'procesando' 
  | 'preview' 
  | 'confirmado' 
  | 'completado' 
  | 'error';

export type ImportPhase = 
  | 'parse' 
  | 'validate' 
  | 'preview' 
  | 'confirm' 
  | 'execute' 
  | 'rollback';

export type ImportType = 
  | 'productos' 
  | 'marcas' 
  | 'lineas' 
  | 'categorias';

export type FileFormat = 
  | 'csv' 
  | 'txt' 
  | 'json' 
  | 'xlsx' 
  | 'xls' 
  | 'unknown';

// Acceptable MIME types for file uploads
export const ACCEPTED_MIME_TYPES = [
  'text/csv',
  'text/plain',
  'application/json',
  'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
  'application/vnd.ms-excel'
] as const;

export type AcceptedMimeType = typeof ACCEPTED_MIME_TYPES[number];

// File extensions
export const ACCEPTED_EXTENSIONS = [
  '.csv',
  '.txt', 
  '.json',
  '.xlsx',
  '.xls'
] as const;

export type AcceptedExtension = typeof ACCEPTED_EXTENSIONS[number];

// =====================================
// Core Import Interfaces
// =====================================

/**
 * Import Log Entry
 */
export interface ImportLog {
  timestamp: string;
  message: string;
  context?: Record<string, any>;
}

/**
 * Row validation result
 */
export interface RowValidation {
  valido: boolean;
  errores: string[];
  advertencias: string[];
}

/**
 * Sample row for preview
 */
export interface PreviewRow {
  fila: number;
  datos: Record<string, any>;
  validacion: RowValidation;
}

/**
 * Preview data summary
 */
export interface PreviewSummary {
  productos_nuevos: number;
  productos_actualizados: number;
  marcas_nuevas: string[];
  lineas_nuevas: string[];
  categorias_nuevas: string[];
}

/**
 * Complete preview data structure
 */
export interface PreviewData {
  resumen: PreviewSummary;
  muestra_filas: PreviewRow[];
}

/**
 * Validation error for specific row
 */
export interface ValidationError {
  row: number;
  sku: string;
  errors: string[];
}

/**
 * Validation warning for specific row
 */
export interface ValidationWarning {
  row: number;
  sku: string;
  warnings: string[];
}

/**
 * Headers validation result
 */
export interface HeadersValidation {
  required_missing: string[];
  optional_missing: string[];
  unknown_columns: string[];
}

/**
 * Complete validation results
 */
export interface ValidationResults {
  errors: ValidationError[];
  warnings: ValidationWarning[];
  headers_validation: HeadersValidation;
}

/**
 * Main Import Audit interface
 */
export interface ImportAudit {
  id: number;
  job_id: string;
  proveedor_id?: number;
  tipo: ImportType;
  archivo?: string;
  formato: FileFormat;
  estado: ImportState;
  fase?: ImportPhase;
  logs?: ImportLog[];
  eta_seconds?: number;
  mem_peak_mb?: number;
  total_registros: number;
  nuevos: number;
  actualizados: number;
  eliminados: number;
  errores: number;
  preview_data?: PreviewData;
  errores_detalle?: ValidationResults;
  progreso: number;
  inicio_proceso?: string;
  fin_proceso?: string;
  created_at?: string;
  updated_at?: string;
}

// =====================================
// API Request/Response Interfaces
// =====================================

/**
 * Upload request parameters
 */
export interface ImportUploadRequest {
  file: File;
  tipo: ImportType;
}

/**
 * Upload response
 */
export interface ImportUploadResponse {
  message: string;
  audit_id: number;
  job_id: string;
  formato: FileFormat;
  estado: ImportState;
}

/**
 * Status response (same as ImportAudit)
 */
export type ImportStatusResponse = ImportAudit;

/**
 * Confirmation response
 */
export interface ImportConfirmResponse {
  message: string;
  audit_id: number;
}

/**
 * Import list item (simplified ImportAudit)
 */
export interface ImportListItem {
  id: number;
  job_id: string;
  tipo: ImportType;
  formato: FileFormat;
  estado: ImportState;
  total_registros: number;
  nuevos: number;
  actualizados: number;
  errores: number;
  created_at: string;
  fin_proceso?: string;
}

/**
 * Paginated import list response
 */
export interface ImportListResponse {
  current_page: number;
  data: ImportListItem[];
  first_page_url: string;
  from?: number;
  last_page: number;
  last_page_url: string;
  next_page_url?: string;
  path: string;
  per_page: number;
  prev_page_url?: string;
  to?: number;
  total: number;
}

/**
 * Error response format
 */
export interface ImportErrorResponse {
  message: string;
  error?: string;
  code: number;
  errors?: Record<string, string[]>;
}

// =====================================
// Product Import Specific Interfaces
// =====================================

/**
 * Required CSV columns for product import
 */
export interface ProductRequiredColumns {
  sku: string;
  nombre_producto: string;
  // proveedor_id is auto-filled, not in CSV
}

/**
 * Optional CSV columns for product import
 */
export interface ProductOptionalColumns {
  nombre_modelo?: string;
  codigo_interno?: string;
  descripcion_producto?: string;
  nombre_marca?: string;
  nombre_linea?: string;
  nombre_categoria_nivel_1?: string;
  nombre_categoria_nivel_2?: string;
  nombre_categoria_nivel_3?: string;
  precio_base?: number;
  precio_de_lista?: number;
  precio_público?: number;
  precio_mayoreo?: number;
  precio_con_IVA?: number;
  precio_sin_IVA?: number;
  precio_promocional?: number;
  precio_distribuidor?: number;
  precio_especial?: number;
}

/**
 * Complete product import row
 */
export type ProductImportRow = ProductRequiredColumns & ProductOptionalColumns;

/**
 * Template headers by import type
 */
export interface ImportTemplateHeaders {
  productos: (keyof ProductImportRow)[];
  marcas: string[];
  lineas: string[];
  categorias: string[];
}

// =====================================
// Service Interfaces
// =====================================

/**
 * Import service interface
 */
export interface ImportService {
  upload(proveedorId: number, request: ImportUploadRequest): Promise<ImportUploadResponse>;
  getStatus(proveedorId: number, auditId: number): Promise<ImportStatusResponse>;
  confirm(proveedorId: number, auditId: number): Promise<ImportConfirmResponse>;
  list(proveedorId: number, page?: number): Promise<ImportListResponse>;
  downloadTemplate(tipo: ImportType): Promise<Blob>;
}

/**
 * Import validator interface
 */
export interface ImportValidator {
  validateFile(file: File): Promise<FileValidationResult>;
  validateMimeType(mimeType: string): boolean;
  validateFileSize(size: number): boolean;
  validateExtension(filename: string): boolean;
}

/**
 * File validation result
 */
export interface FileValidationResult {
  valid: boolean;
  errors: string[];
  warnings: string[];
  detectedFormat?: FileFormat;
}

// =====================================
// Utility Types
// =====================================

/**
 * Import phase progress mapping
 */
export interface PhaseProgress {
  parse: { min: 0; max: 20 };
  validate: { min: 20; max: 40 };
  preview: { min: 40; max: 60 };
  confirm: { min: 60; max: 60 };
  execute: { min: 60; max: 100 };
  rollback: { min: 0; max: 100 };
}

/**
 * Import constants
 */
export const IMPORT_CONSTANTS = {
  MAX_FILE_SIZE: 10 * 1024 * 1024, // 10MB in bytes
  CHUNK_SIZE: 100, // Rows per processing chunk
  PREVIEW_SAMPLE_SIZE: 10, // Number of sample rows in preview
  PHASE_PROGRESS: {
    parse: { min: 0, max: 20 },
    validate: { min: 20, max: 40 },
    preview: { min: 40, max: 60 },
    confirm: { min: 60, max: 60 },
    execute: { min: 60, max: 100 },
    rollback: { min: 0, max: 100 }
  } as PhaseProgress
} as const;

/**
 * CSV delimiter options
 */
export const CSV_DELIMITERS = [',', ';', '\t', '|'] as const;
export type CsvDelimiter = typeof CSV_DELIMITERS[number];

/**
 * Supported text encodings
 */
export const SUPPORTED_ENCODINGS = ['UTF-8', 'Latin-1'] as const;
export type SupportedEncoding = typeof SUPPORTED_ENCODINGS[number];

// =====================================
// Frontend Helper Types
// =====================================

/**
 * Import wizard step
 */
export type ImportWizardStep = 
  | 'upload' 
  | 'processing' 
  | 'preview' 
  | 'confirmation' 
  | 'complete' 
  | 'error';

/**
 * Import wizard state
 */
export interface ImportWizardState {
  currentStep: ImportWizardStep;
  auditId?: number;
  jobId?: string;
  uploadedFile?: File;
  importType?: ImportType;
  progress: number;
  error?: string;
  canRetry: boolean;
}

/**
 * Real-time import status update
 */
export interface ImportStatusUpdate {
  auditId: number;
  estado: ImportState;
  fase?: ImportPhase;
  progreso: number;
  timestamp: string;
  hasErrors: boolean;
  errorCount: number;
}
