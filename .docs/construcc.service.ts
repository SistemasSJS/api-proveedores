import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders, HttpParams, HttpErrorResponse } from '@angular/common/http';
import { Observable, throwError } from 'rxjs';
import { catchError, retry } from 'rxjs/operators';

// ==========================================
// INTERFACES PARA TIPADO TYPESCRIPT
// ==========================================

// Respuesta paginada genérica
export interface PaginatedResponse<T> {
  success: boolean;
  message: string;
  data: T[];
  pagination: {
    current_page: number;
    from: number;
    last_page: number;
    per_page: number;
    to: number;
    total: number;
    first_page_url?: string;
    last_page_url?: string;
    next_page_url?: string;
    prev_page_url?: string;
  };
}

// Respuesta simple
export interface ApiResponse<T = any> {
  success: boolean;
  message: string;
  data: T;
}

// Modelos principales
export interface ConstruccProveedor {
  id: number;
  nombre_comercial: string;
  razon_social: string;
  rfc: string;
  email: string;
  telefono: string;
  pagina_web?: string;
  contacto: {
    nombre: string;
    cargo: string;
    telefono: string;
    correo: string;
  };
  ubicacion: {
    estado: string;
    municipio: string;
    codigo_postal: string;
    direccion_fiscal: string;
    direccion_empresa: string;
  };
  logo?: string;
  empresa: {
    tipo_persona: string;
    tipos_empresa_id: number;
    tipos_empresa_otro?: string;
    descripcion_giro: string;
    nombre_propietario: string;
  };
  estatus: string;
  principal: boolean;
  calificacion?: number;
  categoria?: string;
  estadisticas?: {
    total_productos: number;
    productos_activos: number;
    productos_con_stock: number;
    productos_destacados: number;
  };
  catalogos: {
    categorias_count: number;
    marcas_count: number;
    lineas_count: number;
    unidades_count: number;
  };
  created_at: string;
  updated_at: string;
}

export interface ConstruccProducto {
  id: number;
  sku: string;
  codigo_interno: string;
  nombre: string;
  descripcion?: string;
  modelo?: string;
  precios: {
    base: number;
    mayoreo: number;
    menudeo: number;
  };
  inventario: {
    stock: number;
    disponible: boolean;
    activo: boolean;
  };
  clasificacion: {
    destacado: boolean;
    principal: boolean;
    estatus: string;
  };
  imagen_principal?: string;
  proveedor?: {
    id: number;
    nombre_comercial: string;
    razon_social: string;
    logo?: string;
  };
  categoria?: {
    id: number;
    nombre: string;
    descripcion?: string;
  };
  subcategoria?: {
    id: number;
    nombre: string;
    descripcion?: string;
  };
  marca?: {
    id: number;
    nombre: string;
    descripcion?: string;
    logo?: string;
  };
  linea?: {
    id: number;
    nombre: string;
    descripcion?: string;
  };
  unidad_medida?: {
    id: number;
    nombre: string;
    clave: string;
    descripcion?: string;
  };
  created_at: string;
  updated_at: string;
}

export interface ConstruccCategoria {
  id: number;
  nombre: string;
  descripcion?: string;
  nivel: number;
  parent_id?: number;
  activo: boolean;
  proveedor_id: number;
  parent?: {
    id: number;
    nombre: string;
    descripcion?: string;
  };
  subcategorias?: ConstruccCategoria[];
  productos_count?: number;
  productos_activos_count?: number;
  created_at: string;
  updated_at: string;
}

export interface ConstruccMarca {
  id: number;
  nombre: string;
  descripcion?: string;
  activo: boolean;
  logo?: string;
  proveedor_id: number;
  productos_count?: number;
  productos_activos_count?: number;
  created_at: string;
  updated_at: string;
}

export interface ConstruccUnidad {
  id: number;
  nombre: string;
  clave: string;
  descripcion?: string;
  estatus: string;
  proveedor_id: number;
  productos_count?: number;
  productos_activos_count?: number;
  created_at: string;
  updated_at: string;
}

// Filtros para las consultas
export interface ProveedoresFilters {
  buscar?: string;
  estado?: string;
  municipio?: string;
  tipos_empresa_id?: number[] | string; // [1,2,3] o "1,2,3"
  categoria_id?: number[] | string;
  marca_id?: number[] | string;
  con_productos?: boolean;
  sort_by?: 'nombre_comercial' | 'razon_social' | 'created_at' | 'updated_at';
  order?: 'asc' | 'desc';
  per_page?: number;
  page?: number;
}

export interface ProductosFilters {
  buscar?: string;
  proveedor_id?: number[] | string;
  categoria_id?: number[] | string;
  subcategoria_id?: number[] | string;
  marca_id?: number[] | string;
  linea_id?: number[] | string;
  unidad_medida_id?: number[] | string;
  precio_min?: number;
  precio_max?: number;
  con_stock?: boolean;
  destacado?: boolean;
  sort_by?: 'nombre' | 'precio_base' | 'stock' | 'created_at' | 'updated_at';
  order?: 'asc' | 'desc';
  per_page?: number;
  page?: number;
}

export interface SugerenciaProducto {
  id: number;
  texto: string;
  sku: string;
  codigo: string;
  precio_base: number;
  proveedor_id: number;
}

@Injectable({
  providedIn: 'root'
})
export class ConstruccService {
  private readonly apiUrl = 'http://localhost:8000/api'; // Ajustar según tu configuración
  private readonly baseUrl = `${this.apiUrl}/construcc`;

  constructor(private http: HttpClient) { }

  // ==========================================
  // CONFIGURACIÓN DE HEADERS
  // ==========================================

  private getHeaders(): HttpHeaders {
    const token = this.getToken();
    return new HttpHeaders({
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      ...(token && { 'Authorization': `Bearer ${token}` })
    });
  }

  private getToken(): string | null {
    // Ajustar según donde guardes el token (localStorage, sessionStorage, etc.)
    return localStorage.getItem('api_token') || sessionStorage.getItem('api_token');
  }

  private buildParams(filters: any): HttpParams {
    let params = new HttpParams();

    Object.keys(filters).forEach(key => {
      const value = filters[key];
      if (value !== undefined && value !== null && value !== '') {
        if (Array.isArray(value)) {
          // Convertir arrays a string separado por comas
          params = params.set(key, value.join(','));
        } else {
          params = params.set(key, value.toString());
        }
      }
    });

    return params;
  }

  private handleError(error: HttpErrorResponse) {
    let errorMessage = 'Ha ocurrido un error desconocido.';

    if (error.error instanceof ErrorEvent) {
      // Error del lado del cliente
      errorMessage = `Error: ${error.error.message}`;
    } else {
      // Error del lado del servidor
      if (error.status === 401) {
        errorMessage = 'No autorizado. Por favor, verifica tu token de acceso.';
      } else if (error.status === 403) {
        errorMessage = 'No tienes permisos para realizar esta acción.';
      } else if (error.status === 404) {
        errorMessage = 'Recurso no encontrado.';
      } else if (error.status === 422 && error.error.errors) {
        // Errores de validación
        const validationErrors = Object.values(error.error.errors).flat();
        errorMessage = validationErrors.join(', ');
      } else if (error.error?.message) {
        errorMessage = error.error.message;
      } else {
        errorMessage = `Error del servidor: ${error.status}`;
      }
    }

    console.error('Error en ConstruccService:', error);
    return throwError(errorMessage);
  }

  // ==========================================
  // MÉTODOS PARA PROVEEDORES
  // ==========================================

  /**
   * Obtiene lista paginada de proveedores con filtros
   */
  getProveedores(filters: ProveedoresFilters = {}): Observable<PaginatedResponse<ConstruccProveedor>> {
    const params = this.buildParams(filters);

    return this.http.get<PaginatedResponse<ConstruccProveedor>>(
      `${this.baseUrl}/proveedores`,
      { headers: this.getHeaders(), params }
    ).pipe(
      retry(1),
      catchError(this.handleError)
    );
  }

  /**
   * Búsqueda avanzada de proveedores
   */
  buscarProveedores(filters: ProveedoresFilters = {}): Observable<PaginatedResponse<ConstruccProveedor>> {
    const params = this.buildParams(filters);

    return this.http.get<PaginatedResponse<ConstruccProveedor>>(
      `${this.baseUrl}/proveedores/buscar`,
      { headers: this.getHeaders(), params }
    ).pipe(
      retry(1),
      catchError(this.handleError)
    );
  }

  /**
   * Obtiene productos de un proveedor específico
   */
  getProductosProveedor(proveedorId: number, filters: ProductosFilters = {}): Observable<PaginatedResponse<ConstruccProducto>> {
    const params = this.buildParams(filters);

    return this.http.get<PaginatedResponse<ConstruccProducto>>(
      `${this.baseUrl}/proveedores/${proveedorId}/productos`,
      { headers: this.getHeaders(), params }
    ).pipe(
      retry(1),
      catchError(this.handleError)
    );
  }

  /**
   * Búsqueda en productos de un proveedor específico
   */
  buscarProductosProveedor(proveedorId: number, filters: ProductosFilters = {}): Observable<PaginatedResponse<ConstruccProducto>> {
    const params = this.buildParams(filters);

    return this.http.get<PaginatedResponse<ConstruccProducto>>(
      `${this.baseUrl}/proveedores/${proveedorId}/productos/buscar`,
      { headers: this.getHeaders(), params }
    ).pipe(
      retry(1),
      catchError(this.handleError)
    );
  }

  // ==========================================
  // MÉTODOS PARA PRODUCTOS
  // ==========================================

  /**
   * Búsqueda general de productos con filtros múltiples
   */
  buscarProductos(filters: ProductosFilters = {}): Observable<PaginatedResponse<ConstruccProducto>> {
    const params = this.buildParams(filters);

    return this.http.get<PaginatedResponse<ConstruccProducto>>(
      `${this.baseUrl}/productos/buscar`,
      { headers: this.getHeaders(), params }
    ).pipe(
      retry(1),
      catchError(this.handleError)
    );
  }

  /**
   * Obtiene filtros disponibles para productos
   */
  getFiltrosProductos(): Observable<ApiResponse<any>> {
    return this.http.get<ApiResponse<any>>(
      `${this.baseUrl}/productos/filtros`,
      { headers: this.getHeaders() }
    ).pipe(
      retry(1),
      catchError(this.handleError)
    );
  }

  /**
   * Obtiene sugerencias de productos para autocompletado
   */
  getSugerenciasProductos(termino: string, proveedorId?: number, limite = 10): Observable<ApiResponse<{ sugerencias: SugerenciaProducto[] }>> {
    let params = new HttpParams()
      .set('termino', termino)
      .set('limite', limite.toString());

    if (proveedorId) {
      params = params.set('proveedor_id', proveedorId.toString());
    }

    return this.http.get<ApiResponse<{ sugerencias: SugerenciaProducto[] }>>(
      `${this.baseUrl}/productos/sugerencias`,
      { headers: this.getHeaders(), params }
    ).pipe(
      retry(1),
      catchError(this.handleError)
    );
  }

  // ==========================================
  // MÉTODOS PARA CATÁLOGOS (Sin paginación)
  // ==========================================

  /**
   * Obtiene marcas de un proveedor específico
   */
  getMarcasProveedor(proveedorId: number): Observable<ApiResponse<{ marcas: ConstruccMarca[], total: number }>> {
    return this.http.get<ApiResponse<{ marcas: ConstruccMarca[], total: number }>>(
      `${this.baseUrl}/catalogos/proveedores/${proveedorId}/marcas`,
      { headers: this.getHeaders() }
    ).pipe(
      retry(1),
      catchError(this.handleError)
    );
  }

  /**
   * Obtiene categorías de un proveedor con opción de subcategorías
   */
  getCategoriasProveedor(
    proveedorId: number,
    incluirSubcategorias = false,
    soloPadres = false
  ): Observable<ApiResponse<{ categorias: ConstruccCategoria[], total: number }>> {
    let params = new HttpParams();

    if (incluirSubcategorias) {
      params = params.set('incluir_subcategorias', 'true');
    }

    if (soloPadres) {
      params = params.set('solo_padres', 'true');
    }

    return this.http.get<ApiResponse<{ categorias: ConstruccCategoria[], total: number }>>(
      `${this.baseUrl}/catalogos/proveedores/${proveedorId}/categorias`,
      { headers: this.getHeaders(), params }
    ).pipe(
      retry(1),
      catchError(this.handleError)
    );
  }

  /**
   * Obtiene líneas de un proveedor específico
   */
  getLineasProveedor(proveedorId: number): Observable<ApiResponse<{ lineas: any[], total: number }>> {
    return this.http.get<ApiResponse<{ lineas: any[], total: number }>>(
      `${this.baseUrl}/catalogos/proveedores/${proveedorId}/lineas`,
      { headers: this.getHeaders() }
    ).pipe(
      retry(1),
      catchError(this.handleError)
    );
  }

  /**
   * Obtiene unidades de medida de un proveedor específico
   */
  getUnidadesProveedor(proveedorId: number): Observable<ApiResponse<{ unidades: ConstruccUnidad[], total: number }>> {
    return this.http.get<ApiResponse<{ unidades: ConstruccUnidad[], total: number }>>(
      `${this.baseUrl}/catalogos/proveedores/${proveedorId}/unidades`,
      { headers: this.getHeaders() }
    ).pipe(
      retry(1),
      catchError(this.handleError)
    );
  }

  /**
   * Obtiene todos los catálogos de un proveedor en una sola petición
   */
  getCatalogosCompletos(proveedorId: number): Observable<ApiResponse<{
    marcas: ConstruccMarca[];
    categorias: ConstruccCategoria[];
    lineas: any[];
    unidades: ConstruccUnidad[];
  }>> {
    return this.http.get<ApiResponse<any>>(
      `${this.baseUrl}/catalogos/proveedores/${proveedorId}/completos`,
      { headers: this.getHeaders() }
    ).pipe(
      retry(1),
      catchError(this.handleError)
    );
  }

  // ==========================================
  // MÉTODOS PARA ESTADÍSTICAS Y REPORTES
  // ==========================================

  /**
   * Obtiene estadísticas generales del módulo
   */
  getEstadisticas(): Observable<ApiResponse<any>> {
    return this.http.get<ApiResponse<any>>(
      `${this.baseUrl}/reportes/estadisticas`,
      { headers: this.getHeaders() }
    ).pipe(
      retry(1),
      catchError(this.handleError)
    );
  }

  /**
   * Obtiene resumen específico de un proveedor
   */
  getResumenProveedor(proveedorId: number): Observable<ApiResponse<any>> {
    return this.http.get<ApiResponse<any>>(
      `${this.baseUrl}/reportes/proveedores/${proveedorId}/resumen`,
      { headers: this.getHeaders() }
    ).pipe(
      retry(1),
      catchError(this.handleError)
    );
  }

  // ==========================================
  // MÉTODOS PARA CONFIGURACIÓN
  // ==========================================

  /**
   * Obtiene filtros disponibles globalmente
   */
  getFiltrosDisponibles(): Observable<ApiResponse<any>> {
    return this.http.get<ApiResponse<any>>(
      `${this.baseUrl}/config/filtros-disponibles`,
      { headers: this.getHeaders() }
    ).pipe(
      retry(1),
      catchError(this.handleError)
    );
  }

  /**
   * Obtiene opciones de ordenamiento disponibles
   */
  getOpcionesOrdenamiento(): Observable<ApiResponse<any>> {
    return this.http.get<ApiResponse<any>>(
      `${this.baseUrl}/config/opciones-ordenamiento`,
      { headers: this.getHeaders() }
    ).pipe(
      retry(1),
      catchError(this.handleError)
    );
  }
}

// ==========================================
// EJEMPLOS DE USO
// ==========================================

/*

// 1. EJEMPLO BÁSICO - Obtener proveedores
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

// 2. EJEMPLO CON FILTROS MÚLTIPLES - Buscar productos
this.construccService.buscarProductos({
  buscar: 'cemento',
  categoria_id: [1, 2, 3], // Se convertirá a "1,2,3"
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

// 3. EJEMPLO PARA AUTOCOMPLETADO
this.construccService.getSugerenciasProductos('cem', 1, 5).subscribe({
  next: (response) => {
    console.log('Sugerencias:', response.data.sugerencias);
    // Mostrar en dropdown de autocompletado
  }
});

// 4. EJEMPLO PARA CARGAR CATÁLOGOS
this.construccService.getCatalogosCompletos(1).subscribe({
  next: (response) => {
    const catalogos = response.data;
    console.log('Marcas:', catalogos.marcas);
    console.log('Categorías:', catalogos.categorias);
    console.log('Líneas:', catalogos.lineas);
    console.log('Unidades:', catalogos.unidades);
  }
});

// 5. EJEMPLO CON PAGINACIÓN EN COMPONENTE
export class ProductosComponent {
  productos: ConstruccProducto[] = [];
  currentPage = 1;
  totalPages = 1;
  
  loadProductos(page = 1) {
    this.construccService.buscarProductos({
      page: page,
      per_page: 20
    }).subscribe({
      next: (response) => {
        this.productos = response.data;
        this.currentPage = response.pagination.current_page;
        this.totalPages = response.pagination.last_page;
      }
    });
  }
}

// 6. EJEMPLO PARA MANEJAR ERRORES
this.construccService.getProveedores().subscribe({
  next: (response) => {
    // Éxito
  },
  error: (errorMessage) => {
    // Mostrar mensaje de error al usuario
    this.showErrorMessage(errorMessage);
  }
});

*/
