import {
  ImportService,
  ImportUploadRequest,
  ImportUploadResponse,
  ImportStatusResponse,
  ImportConfirmResponse,
  ImportListResponse,
  ImportType,
  ImportErrorResponse,
  ACCEPTED_MIME_TYPES,
  ACCEPTED_EXTENSIONS,
  IMPORT_CONSTANTS,
  FileValidationResult,
  ImportValidator
} from '../types/import.models';

/**
 * HTTP client wrapper for API calls
 */
interface HttpClient {
  post<T>(url: string, data?: any, config?: any): Promise<T>;
  get<T>(url: string, config?: any): Promise<T>;
}

/**
 * Import Service Implementation
 * Handles all import-related API operations
 */
export class ImportServiceImpl implements ImportService {
  private readonly httpClient: HttpClient;
  private readonly baseUrl: string;

  constructor(httpClient: HttpClient, baseUrl: string = '/api') {
    this.httpClient = httpClient;
    this.baseUrl = baseUrl;
  }

  /**
   * Upload a file for import processing
   */
  async upload(proveedorId: number, request: ImportUploadRequest): Promise<ImportUploadResponse> {
    const url = `${this.baseUrl}/proveedores/${proveedorId}/import`;
    
    const formData = new FormData();
    formData.append('file', request.file);
    formData.append('tipo', request.tipo);

    try {
      const response = await this.httpClient.post<ImportUploadResponse>(url, formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      });
      
      return response;
    } catch (error) {
      throw this.handleError(error);
    }
  }

  /**
   * Get import status and detailed information
   */
  async getStatus(proveedorId: number, auditId: number): Promise<ImportStatusResponse> {
    const url = `${this.baseUrl}/proveedores/${proveedorId}/import/${auditId}/status`;
    
    try {
      return await this.httpClient.get<ImportStatusResponse>(url);
    } catch (error) {
      throw this.handleError(error);
    }
  }

  /**
   * Confirm and execute the import after preview
   */
  async confirm(proveedorId: number, auditId: number): Promise<ImportConfirmResponse> {
    const url = `${this.baseUrl}/proveedores/${proveedorId}/import/${auditId}/confirm`;
    
    try {
      return await this.httpClient.post<ImportConfirmResponse>(url);
    } catch (error) {
      throw this.handleError(error);
    }
  }

  /**
   * List all imports for a provider with pagination
   */
  async list(proveedorId: number, page: number = 1): Promise<ImportListResponse> {
    const url = `${this.baseUrl}/proveedores/${proveedorId}/imports`;
    
    try {
      return await this.httpClient.get<ImportListResponse>(url, {
        params: { page }
      });
    } catch (error) {
      throw this.handleError(error);
    }
  }

  /**
   * Download CSV template for specific import type
   */
  async downloadTemplate(tipo: ImportType): Promise<Blob> {
    const url = `${this.baseUrl}/import/template/${tipo}`;
    
    try {
      const response = await this.httpClient.get<Blob>(url, {
        responseType: 'blob'
      });
      
      return response;
    } catch (error) {
      throw this.handleError(error);
    }
  }

  /**
   * Poll import status until completion
   */
  async pollStatus(
    proveedorId: number, 
    auditId: number, 
    onProgress?: (status: ImportStatusResponse) => void,
    intervalMs: number = 2000
  ): Promise<ImportStatusResponse> {
    return new Promise((resolve, reject) => {
      const poll = async () => {
        try {
          const status = await this.getStatus(proveedorId, auditId);
          
          if (onProgress) {
            onProgress(status);
          }

          // Check if import is complete
          if (['completado', 'error', 'preview'].includes(status.estado)) {
            resolve(status);
            return;
          }

          // Continue polling
          setTimeout(poll, intervalMs);
        } catch (error) {
          reject(error);
        }
      };

      poll();
    });
  }

  /**
   * Handle and transform API errors
   */
  private handleError(error: any): ImportErrorResponse {
    if (error.response?.data) {
      return error.response.data as ImportErrorResponse;
    }

    return {
      message: error.message || 'Error desconocido en la importación',
      error: error.toString(),
      code: error.response?.status || 500
    };
  }
}

/**
 * File Validator Implementation
 * Validates files before upload
 */
export class FileValidatorImpl implements ImportValidator {
  
  /**
   * Validate file before upload
   */
  async validateFile(file: File): Promise<FileValidationResult> {
    const errors: string[] = [];
    const warnings: string[] = [];

    // Check file size
    if (!this.validateFileSize(file.size)) {
      errors.push(`El archivo excede el tamaño máximo de ${IMPORT_CONSTANTS.MAX_FILE_SIZE / (1024 * 1024)}MB`);
    }

    // Check MIME type
    if (!this.validateMimeType(file.type)) {
      errors.push(`Tipo de archivo no soportado: ${file.type}`);
    }

    // Check file extension
    if (!this.validateExtension(file.name)) {
      errors.push(`Extensión de archivo no soportada`);
    }

    // Detect format
    const detectedFormat = this.detectFormat(file);

    // Check if file is empty
    if (file.size === 0) {
      errors.push('El archivo está vacío');
    }

    // Add warnings for large files
    if (file.size > IMPORT_CONSTANTS.MAX_FILE_SIZE * 0.8) {
      warnings.push('El archivo es muy grande, el procesamiento puede ser lento');
    }

    return {
      valid: errors.length === 0,
      errors,
      warnings,
      detectedFormat
    };
  }

  /**
   * Validate MIME type
   */
  validateMimeType(mimeType: string): boolean {
    return ACCEPTED_MIME_TYPES.includes(mimeType as any);
  }

  /**
   * Validate file size
   */
  validateFileSize(size: number): boolean {
    return size <= IMPORT_CONSTANTS.MAX_FILE_SIZE && size > 0;
  }

  /**
   * Validate file extension
   */
  validateExtension(filename: string): boolean {
    const extension = this.getFileExtension(filename);
    return ACCEPTED_EXTENSIONS.includes(extension as any);
  }

  /**
   * Detect file format based on extension and MIME type
   */
  private detectFormat(file: File) {
    const extension = this.getFileExtension(file.name).toLowerCase();
    
    switch (extension) {
      case '.csv':
        return 'csv';
      case '.txt':
        return 'txt';
      case '.json':
        return 'json';
      case '.xlsx':
        return 'xlsx';
      case '.xls':
        return 'xls';
      default:
        // Fallback to MIME type detection
        if (file.type.includes('csv')) return 'csv';
        if (file.type.includes('json')) return 'json';
        if (file.type.includes('spreadsheetml')) return 'xlsx';
        if (file.type.includes('ms-excel')) return 'xls';
        if (file.type.includes('text/plain')) return 'txt';
        return 'unknown';
    }
  }

  /**
   * Extract file extension from filename
   */
  private getFileExtension(filename: string): string {
    return filename.substring(filename.lastIndexOf('.'));
  }
}

/**
 * Import utilities and helper functions
 */
export class ImportUtils {
  
  /**
   * Format file size for display
   */
  static formatFileSize(bytes: number): string {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  }

  /**
   * Calculate ETA from progress percentage
   */
  static calculateETA(progress: number, startTime: Date): number | null {
    if (progress <= 0) return null;
    
    const elapsed = Date.now() - startTime.getTime();
    const rate = progress / elapsed;
    const remaining = 100 - progress;
    
    return remaining / rate;
  }

  /**
   * Format ETA for display
   */
  static formatETA(etaMs: number | null): string {
    if (!etaMs) return 'Calculando...';
    
    const seconds = Math.floor(etaMs / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);
    
    if (hours > 0) {
      return `${hours}h ${minutes % 60}m`;
    } else if (minutes > 0) {
      return `${minutes}m ${seconds % 60}s`;
    } else {
      return `${seconds}s`;
    }
  }

  /**
   * Get import type display name
   */
  static getImportTypeDisplayName(tipo: ImportType): string {
    const names = {
      productos: 'Productos',
      marcas: 'Marcas', 
      lineas: 'Líneas de Producto',
      categorias: 'Categorías'
    };
    
    return names[tipo] || tipo;
  }

  /**
   * Get import state display name and color
   */
  static getImportStateDisplay(estado: string) {
    const states = {
      pendiente: { name: 'Pendiente', color: 'orange', icon: 'clock' },
      procesando: { name: 'Procesando', color: 'blue', icon: 'loading' },
      preview: { name: 'Vista Previa', color: 'purple', icon: 'eye' },
      confirmado: { name: 'Confirmado', color: 'blue', icon: 'check' },
      completado: { name: 'Completado', color: 'green', icon: 'check-circle' },
      error: { name: 'Error', color: 'red', icon: 'exclamation-triangle' }
    };
    
    return states[estado] || { name: estado, color: 'gray', icon: 'question' };
  }

  /**
   * Validate CSV headers against expected columns
   */
  static validateHeaders(headers: string[], requiredColumns: string[], optionalColumns: string[] = []) {
    const missing = requiredColumns.filter(col => !headers.includes(col));
    const unknown = headers.filter(col => 
      !requiredColumns.includes(col) && !optionalColumns.includes(col)
    );
    
    return {
      valid: missing.length === 0,
      missing,
      unknown,
      total: headers.length
    };
  }

  /**
   * Create FormData for file upload
   */
  static createUploadFormData(file: File, tipo: ImportType): FormData {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('tipo', tipo);
    return formData;
  }
}

/**
 * Export configured service instance
 */
export function createImportService(httpClient: HttpClient, baseUrl?: string): ImportService {
  return new ImportServiceImpl(httpClient, baseUrl);
}

/**
 * Export configured validator instance  
 */
export function createFileValidator(): ImportValidator {
  return new FileValidatorImpl();
}
