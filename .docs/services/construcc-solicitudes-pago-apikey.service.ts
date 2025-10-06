import { Injectable } from '@angular/core';
import {
	HttpClient,
	HttpHeaders,
	HttpParams,
	HttpErrorResponse,
} from '@angular/common/http';
import { Observable, throwError } from 'rxjs';
import { catchError, retry } from 'rxjs/operators';
import { environment } from '../../environments/environment';

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

// Modelo principal de Solicitud de Pago
export interface SolicitudPago {
	id: number;
	numero_folio_solicitud: string;
	descripcion_concepto: string;
	estado_solicitud: EstadoSolicitud;
	ruta_archivo_factura_pdf?: string;
	ruta_archivo_factura_xml?: string;
	ruta_archivo_comprobante_pago?: string;
	ruta_archivo_cotizacion?: string;
	residente?: string;
	cotizacion_id?: number;
	sucursal_id?: number;
	
	// Fechas por estatus
	fecha_registro_pendiente?: string;
	fecha_inicio_procesamiento?: string;
	fecha_con_comprobante?: string;
	fecha_aprobado?: string;
	fecha_confirmacion_pago?: string;
	fecha_rechazado?: string;
	fecha_cancelado?: string;
	
	// Motivos
	motivo_rechazo?: string;
	motivo_cancelacion?: string;
	
	// Relaciones
	proveedor?: {
		id: number;
		nombre_comercial: string;
		razon_social: string;
		rfc: string;
		email: string;
		telefono: string;
		logo?: string;
	};
	
	empresa_construcc?: {
		id: number;
		nombre: string;
		razon_social: string;
		rfc: string;
		representante_legal?: string;
	};
	
	sucursal?: {
		id: number;
		nombre: string;
		direccion: string;
		telefono?: string;
	};
	
	created_at: string;
	updated_at: string;
}

// Estados posibles de una solicitud de pago
export type EstadoSolicitud = 
	| 'pendiente'
	| 'procesando' 
	| 'con_comprobante'
	| 'aprobado'
	| 'pagado'
	| 'rechazado'
	| 'cancelado';

// Empresa de construcción
export interface EmpresaConstrucc {
	id: number;
	nombre: string;
	razon_social: string;
	rfc: string;
	representante_legal?: string;
}

// Filtros para solicitudes de pago
export interface SolicitudesPagoFilters {
	// Filtros básicos
	numero_folio_solicitud?: string;
	descripcion_concepto?: string;
	estado_solicitud?: EstadoSolicitud | EstadoSolicitud[];
	proveedor_id?: number[] | string;
	empresa_construcc_id?: number[] | string;
	residente?: string;
	cotizacion_id?: number[] | string;
	
	// Filtros por fechas específicas
	fecha_registro_pendiente?: string;
	fecha_inicio_procesamiento?: string;
	fecha_con_comprobante?: string;
	fecha_aprobado?: string;
	fecha_confirmacion_pago?: string;
	fecha_rechazado?: string;
	fecha_cancelado?: string;
	
	// Filtros por rangos de fechas
	fecha_registro_pendiente_desde?: string;
	fecha_registro_pendiente_hasta?: string;
	fecha_inicio_procesamiento_desde?: string;
	fecha_inicio_procesamiento_hasta?: string;
	fecha_con_comprobante_desde?: string;
	fecha_con_comprobante_hasta?: string;
	fecha_aprobado_desde?: string;
	fecha_aprobado_hasta?: string;
	fecha_confirmacion_pago_desde?: string;
	fecha_confirmacion_pago_hasta?: string;
	fecha_rechazado_desde?: string;
	fecha_rechazado_hasta?: string;
	fecha_cancelado_desde?: string;
	fecha_cancelado_hasta?: string;
	
	// Parámetros de ordenamiento y paginación
	sort_by?: 'numero_folio_solicitud' | 'descripcion_concepto' | 'estado_solicitud' | 'created_at' | 'updated_at';
	order?: 'asc' | 'desc';
	per_page?: number;
	page?: number;
}

// Request para autorización
export interface AutorizarRequest {
	observaciones?: string;
	monto_autorizado?: number;
}

// Request para rechazo
export interface RechazarRequest {
	motivo_rechazo: string;
	observaciones?: string;
}

// Request para confirmación de pago
export interface ConfirmarPagoRequest {
	fecha_pago?: string;
	numero_transaccion?: string;
	observaciones?: string;
}

// Estadísticas de solicitudes de pago por rol
export interface EstadisticasSolicitudPagoRol {
	rol: string;
	total: number;
	pendientes: number;
	procesando: number;
	con_comprobante: number;
	aprobadas: number;
	pagadas: number;
	rechazadas: number;
	canceladas: number;
	monto_total_aprobado?: number;
	monto_total_pagado?: number;
}

// Estadísticas generales de solicitudes de pago
export interface EstadisticasSolicitudPago {
	total: number;
	por_estado: {
		pendientes: number;
		procesando: number;
		con_comprobante: number;
		aprobadas: number;
		pagadas: number;
		rechazadas: number;
		canceladas: number;
	};
	por_mes?: {
		mes: string;
		total: number;
		monto: number;
	}[];
	por_proveedor?: {
		proveedor_id: number;
		nombre: string;
		total: number;
		monto: number;
	}[];
}

// Respuesta de listado por rol
export interface ListadoPorRolResponse {
	success: boolean;
	message: string;
	data: {
		rol: string;
		solicitudes: SolicitudPago[];
		total: number;
	};
}

// Respuesta de listado por estado
export interface ListadoPorEstadoResponse {
	success: boolean;
	message: string;
	data: {
		estado: EstadoSolicitud;
		solicitudes: SolicitudPago[];
		total: number;
	};
}

@Injectable({
	providedIn: 'root',
})
export class ConstruccSolicitudesPagoApiKeyService {
	private readonly apiUrl = environment.apiUrl || 'http://localhost:8000/api';
	private readonly baseUrl = `${this.apiUrl}/construcc/solicitudes-pago`;
	private readonly apiKey = environment.apiKey || '';

	constructor(private http: HttpClient) {}

	// ==========================================
	// CONFIGURACIÓN DE HEADERS CON API KEY
	// ==========================================

	/**
	 * Obtiene los headers con autenticación API Key
	 */
	private getHeaders(): HttpHeaders {
		return new HttpHeaders({
			'Content-Type': 'application/json',
			'Accept': 'application/json',
			'X-API-KEY': this.apiKey,
		});
	}

	/**
	 * Obtiene headers para multipart/form-data con API Key
	 */
	private getMultipartHeaders(): HttpHeaders {
		return new HttpHeaders({
			'Accept': 'application/json',
			'X-API-KEY': this.apiKey,
			// No establecemos Content-Type para multipart, Angular lo hace automáticamente
		});
	}

	/**
	 * Obtiene headers para descarga de archivos con API Key
	 */
	private getDownloadHeaders(): HttpHeaders {
		return new HttpHeaders({
			'X-API-KEY': this.apiKey,
		});
	}

	/**
	 * Construye parámetros HTTP desde filtros
	 */
	private buildParams(filters: any): HttpParams {
		let params = new HttpParams();

		Object.keys(filters).forEach((key) => {
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

	/**
	 * Maneja errores HTTP
	 */
	private handleError(error: HttpErrorResponse) {
		let errorMessage = 'Ha ocurrido un error desconocido.';

		if (error.error instanceof ErrorEvent) {
			// Error del lado del cliente
			errorMessage = `Error: ${error.error.message}`;
		} else {
			// Error del lado del servidor
			if (error.status === 401) {
				errorMessage = 'API Key inválida o faltante. Verifica la configuración.';
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

		console.error('Error en ConstruccSolicitudesPagoApiKeyService:', error);
		return throwError(errorMessage);
	}

	// ==========================================
	// MÉTODOS CRUD BÁSICOS
	// ==========================================

	/**
	 * Obtiene lista paginada de solicitudes de pago con filtros
	 * GET /api/construcc/solicitudes-pago
	 */
	index(filters: SolicitudesPagoFilters = {}): Observable<PaginatedResponse<SolicitudPago>> {
		const params = this.buildParams(filters);

		return this.http
			.get<PaginatedResponse<SolicitudPago>>(
				`${this.baseUrl}`,
				{ headers: this.getHeaders(), params }
			)
			.pipe(retry(1), catchError(this.handleError));
	}

	/**
	 * Obtiene una solicitud de pago específica por ID
	 * GET /api/construcc/solicitudes-pago/{id}
	 */
	show(id: number): Observable<ApiResponse<SolicitudPago>> {
		return this.http
			.get<ApiResponse<SolicitudPago>>(
				`${this.baseUrl}/${id}`,
				{ headers: this.getHeaders() }
			)
			.pipe(retry(1), catchError(this.handleError));
	}

	// ==========================================
	// MÉTODOS PARA DESCARGA DE ARCHIVOS
	// ==========================================

	/**
	 * Descarga comprobante de pago
	 * GET /api/construcc/solicitudes-pago/{id}/comprobante/download
	 */
	descargarComprobante(id: number): Observable<Blob> {
		return this.http
			.get(
				`${this.baseUrl}/${id}/comprobante/download`,
				{ 
					headers: this.getDownloadHeaders(),
					responseType: 'blob'
				}
			)
			.pipe(retry(1), catchError(this.handleError));
	}

	/**
	 * Descarga factura PDF
	 * GET /api/construcc/solicitudes-pago/{id}/factura-pdf/download
	 */
	descargarFacturaPdf(id: number): Observable<Blob> {
		return this.http
			.get(
				`${this.baseUrl}/${id}/factura-pdf/download`,
				{ 
					headers: this.getDownloadHeaders(),
					responseType: 'blob'
				}
			)
			.pipe(retry(1), catchError(this.handleError));
	}

	/**
	 * Descarga factura XML
	 * GET /api/construcc/solicitudes-pago/{id}/factura-xml/download
	 */
	descargarFacturaXml(id: number): Observable<Blob> {
		return this.http
			.get(
				`${this.baseUrl}/${id}/factura-xml/download`,
				{ 
					headers: this.getDownloadHeaders(),
					responseType: 'blob'
				}
			)
			.pipe(retry(1), catchError(this.handleError));
	}

	/**
	 * Descarga cotización asociada
	 * GET /api/construcc/solicitudes-pago/{id}/cotizacion/download
	 */
	descargarCotizacion(id: number): Observable<Blob> {
		return this.http
			.get(
				`${this.baseUrl}/${id}/cotizacion/download`,
				{ 
					headers: this.getDownloadHeaders(),
					responseType: 'blob'
				}
			)
			.pipe(retry(1), catchError(this.handleError));
	}

	// ==========================================
	// MÉTODOS PARA CAMBIOS DE ESTATUS
	// ==========================================

	/**
	 * Autoriza una solicitud de pago
	 * POST /api/construcc/solicitudes-pago/{id}/autorizar
	 */
	autorizar(id: number, data?: AutorizarRequest): Observable<ApiResponse<SolicitudPago>> {
		return this.http
			.post<ApiResponse<SolicitudPago>>(
				`${this.baseUrl}/${id}/autorizar`,
				data || {},
				{ headers: this.getHeaders() }
			)
			.pipe(retry(1), catchError(this.handleError));
	}

	/**
	 * Rechaza una solicitud de pago
	 * POST /api/construcc/solicitudes-pago/{id}/rechazar
	 */
	rechazar(id: number, data: RechazarRequest): Observable<ApiResponse<SolicitudPago>> {
		return this.http
			.post<ApiResponse<SolicitudPago>>(
				`${this.baseUrl}/${id}/rechazar`,
				data,
				{ headers: this.getHeaders() }
			)
			.pipe(retry(1), catchError(this.handleError));
	}

	/**
	 * Confirma el pago de una solicitud
	 * POST /api/construcc/solicitudes-pago/{id}/confirmar-pago
	 */
	confirmarPago(id: number, data?: ConfirmarPagoRequest): Observable<ApiResponse<SolicitudPago>> {
		return this.http
			.post<ApiResponse<SolicitudPago>>(
				`${this.baseUrl}/${id}/confirmar-pago`,
				data || {},
				{ headers: this.getHeaders() }
			)
			.pipe(retry(1), catchError(this.handleError));
	}

	// ==========================================
	// MÉTODOS DE LISTADOS ESPECIALIZADOS
	// ==========================================

	/**
	 * Obtiene solicitudes filtradas por rol
	 * GET /api/construcc/solicitudes-pago/por-rol
	 */
	listarPorRol(params?: { rol?: string; page?: number; per_page?: number }): Observable<ListadoPorRolResponse> {
		const httpParams = this.buildParams(params);
		
		return this.http
			.get<ListadoPorRolResponse>(
				`${this.baseUrl}/por-rol`,
				{ headers: this.getHeaders(), params: httpParams }
			)
			.pipe(retry(1), catchError(this.handleError));
	}

	/**
	 * Obtiene solicitudes filtradas por estado
	 * GET /api/construcc/solicitudes-pago/por-estado
	 */
	listarPorEstado(params?: { estado?: EstadoSolicitud; page?: number; per_page?: number }): Observable<ListadoPorEstadoResponse> {
		const httpParams = this.buildParams(params);
		
		return this.http
			.get<ListadoPorEstadoResponse>(
				`${this.baseUrl}/por-estado`,
				{ headers: this.getHeaders(), params: httpParams }
			)
			.pipe(retry(1), catchError(this.handleError));
	}

	/**
	 * Obtiene estadísticas por rol
	 * GET /api/construcc/solicitudes-pago/estadisticas-rol
	 */
	estadisticasPorRol(params?: { rol?: string }): Observable<ApiResponse<EstadisticasSolicitudPagoRol>> {
		const httpParams = this.buildParams(params);
		
		return this.http
			.get<ApiResponse<EstadisticasSolicitudPagoRol>>(
				`${this.baseUrl}/estadisticas-rol`,
				{ headers: this.getHeaders(), params: httpParams }
			)
			.pipe(retry(1), catchError(this.handleError));
	}

	/**
	 * Busca empresas constructoras para autocompletado
	 * GET /api/construcc/solicitudes-pago/empresas-constructoras/search
	 */
	buscarEmpresasConstructoras(
		search = '', 
		limit = 20
	): Observable<ApiResponse<EmpresaConstrucc[]>> {
		let params = new HttpParams()
			.set('limit', limit.toString());

		if (search) {
			params = params.set('search', search);
		}

		return this.http
			.get<ApiResponse<EmpresaConstrucc[]>>(
				`${this.baseUrl}/empresas-constructoras/search`,
				{ headers: this.getHeaders(), params }
			)
			.pipe(retry(1), catchError(this.handleError));
	}

	/**
	 * Obtiene estadísticas generales
	 * GET /api/construcc/solicitudes-pago/estadisticas
	 */
	estadisticas(params?: { 
		empresa_construcc_id?: number; 
		proveedor_id?: number;
		fecha_desde?: string;
		fecha_hasta?: string;
	}): Observable<ApiResponse<EstadisticasSolicitudPago>> {
		const httpParams = this.buildParams(params);
		
		return this.http
			.get<ApiResponse<EstadisticasSolicitudPago>>(
				`${this.baseUrl}/estadisticas`,
				{ headers: this.getHeaders(), params: httpParams }
			)
			.pipe(retry(1), catchError(this.handleError));
	}

	// ==========================================
	// MÉTODOS DE UTILIDAD
	// ==========================================

	/**
	 * Convierte un blob a URL para descarga
	 */
	downloadFile(blob: Blob, filename: string): void {
		const url = window.URL.createObjectURL(blob);
		const link = document.createElement('a');
		link.href = url;
		link.download = filename;
		link.click();
		window.URL.revokeObjectURL(url);
	}

	/**
	 * Obtiene el color asociado a un estado
	 */
	getEstadoColor(estado: EstadoSolicitud): string {
		const colors: Record<EstadoSolicitud, string> = {
			pendiente: '#FFA726',       // Orange
			procesando: '#42A5F5',      // Blue  
			con_comprobante: '#AB47BC', // Purple
			aprobado: '#66BB6A',        // Green
			pagado: '#4CAF50',          // Dark Green
			rechazado: '#F44336',       // Red
			cancelado: '#9E9E9E'        // Gray
		};
		return colors[estado] || '#9E9E9E';
	}

	/**
	 * Obtiene el texto legible de un estado
	 */
	getEstadoTexto(estado: EstadoSolicitud): string {
		const textos: Record<EstadoSolicitud, string> = {
			pendiente: 'Pendiente',
			procesando: 'Procesando',
			con_comprobante: 'Con Comprobante',
			aprobado: 'Aprobado',
			pagado: 'Pagado',
			rechazado: 'Rechazado',
			cancelado: 'Cancelado'
		};
		return textos[estado] || estado;
	}

	/**
	 * Obtiene el ícono asociado a un estado
	 */
	getEstadoIcon(estado: EstadoSolicitud): string {
		const icons: Record<EstadoSolicitud, string> = {
			pendiente: 'schedule',
			procesando: 'cached',
			con_comprobante: 'attach_file',
			aprobado: 'check_circle',
			pagado: 'payment',
			rechazado: 'cancel',
			cancelado: 'block'
		};
		return icons[estado] || 'help_outline';
	}

	/**
	 * Verifica si una solicitud puede cambiar a un estado específico
	 */
	puedeCambiarEstado(estadoActual: EstadoSolicitud, estadoDestino: EstadoSolicitud): boolean {
		const transicionesValidas: Record<EstadoSolicitud, EstadoSolicitud[]> = {
			pendiente: ['procesando', 'rechazado', 'cancelado'],
			procesando: ['con_comprobante', 'aprobado', 'rechazado', 'cancelado'],
			con_comprobante: ['aprobado', 'rechazado', 'cancelado'],
			aprobado: ['pagado', 'cancelado'],
			pagado: [], // Estado final
			rechazado: ['procesando'], // Puede reprocesarse
			cancelado: [] // Estado final
		};

		return transicionesValidas[estadoActual]?.includes(estadoDestino) || false;
	}

	/**
	 * Valida si la API Key está configurada
	 */
	isApiKeyConfigured(): boolean {
		return !!this.apiKey;
	}

	/**
	 * Obtiene información de la configuración actual
	 */
	getConfigInfo(): { apiUrl: string; hasApiKey: boolean } {
		return {
			apiUrl: this.apiUrl,
			hasApiKey: this.isApiKeyConfigured()
		};
	}
}

// ==========================================
// EJEMPLOS DE USO
// ==========================================

/*

// En tu componente Angular:

import { Component, OnInit } from '@angular/core';
import { ConstruccSolicitudesPagoApiKeyService } from './construcc-solicitudes-pago-apikey.service';

@Component({
  selector: 'app-solicitudes-pago',
  templateUrl: './solicitudes-pago.component.html'
})
export class SolicitudesPagoComponent implements OnInit {
  solicitudes: SolicitudPago[] = [];
  loading = false;
  error: string | null = null;
  
  constructor(
    private solicitudService: ConstruccSolicitudesPagoApiKeyService
  ) {}
  
  ngOnInit() {
    // Verificar configuración de API Key
    if (!this.solicitudService.isApiKeyConfigured()) {
      this.error = 'API Key no configurada. Verifica el archivo environment.ts';
      return;
    }
    
    this.cargarSolicitudes();
  }
  
  // 1️⃣ CARGAR LISTADO CON FILTROS
  cargarSolicitudes() {
    this.loading = true;
    this.error = null;
    
    this.solicitudService.index({
      estado_solicitud: ['pendiente', 'procesando'],
      page: 1,
      per_page: 20,
      sort_by: 'created_at',
      order: 'desc'
    }).subscribe({
      next: (response) => {
        this.solicitudes = response.data;
        console.log('Total de solicitudes:', response.pagination.total);
        this.loading = false;
      },
      error: (error) => {
        this.error = error;
        this.loading = false;
      }
    });
  }
  
  // 2️⃣ VER DETALLE DE SOLICITUD
  verDetalle(id: number) {
    this.solicitudService.show(id).subscribe({
      next: (response) => {
        console.log('Detalle de solicitud:', response.data);
        // Navegar al detalle o mostrar en modal
      },
      error: (error) => console.error('Error al obtener detalle:', error)
    });
  }
  
  // 3️⃣ AUTORIZAR SOLICITUD
  autorizarSolicitud(solicitud: SolicitudPago) {
    if (!this.solicitudService.puedeCambiarEstado(solicitud.estado_solicitud, 'aprobado')) {
      this.error = 'Esta solicitud no puede ser autorizada en su estado actual';
      return;
    }
    
    this.solicitudService.autorizar(solicitud.id, {
      observaciones: 'Autorizado por el supervisor',
      monto_autorizado: 50000
    }).subscribe({
      next: (response) => {
        console.log('Solicitud autorizada:', response.data);
        this.cargarSolicitudes(); // Recargar lista
      },
      error: (error) => this.error = error
    });
  }
  
  // 4️⃣ RECHAZAR SOLICITUD
  rechazarSolicitud(solicitud: SolicitudPago, motivo: string) {
    this.solicitudService.rechazar(solicitud.id, {
      motivo_rechazo: motivo,
      observaciones: 'Rechazado por incumplimiento de requisitos'
    }).subscribe({
      next: (response) => {
        console.log('Solicitud rechazada:', response.data);
        this.cargarSolicitudes();
      },
      error: (error) => this.error = error
    });
  }
  
  // 5️⃣ CONFIRMAR PAGO
  confirmarPago(solicitud: SolicitudPago) {
    this.solicitudService.confirmarPago(solicitud.id, {
      fecha_pago: new Date().toISOString().split('T')[0],
      numero_transaccion: 'TRX-2024-001234',
      observaciones: 'Pago realizado por transferencia bancaria'
    }).subscribe({
      next: (response) => {
        console.log('Pago confirmado:', response.data);
        this.cargarSolicitudes();
      },
      error: (error) => this.error = error
    });
  }
  
  // 6️⃣ DESCARGAR ARCHIVOS
  descargarFacturaPDF(solicitud: SolicitudPago) {
    this.solicitudService.descargarFacturaPdf(solicitud.id).subscribe({
      next: (blob) => {
        const filename = `factura-${solicitud.numero_folio_solicitud}.pdf`;
        this.solicitudService.downloadFile(blob, filename);
      },
      error: (error) => console.error('Error al descargar factura:', error)
    });
  }
  
  descargarFacturaXML(solicitud: SolicitudPago) {
    this.solicitudService.descargarFacturaXml(solicitud.id).subscribe({
      next: (blob) => {
        const filename = `factura-${solicitud.numero_folio_solicitud}.xml`;
        this.solicitudService.downloadFile(blob, filename);
      },
      error: (error) => console.error('Error al descargar XML:', error)
    });
  }
  
  descargarComprobante(solicitud: SolicitudPago) {
    this.solicitudService.descargarComprobante(solicitud.id).subscribe({
      next: (blob) => {
        const filename = `comprobante-${solicitud.numero_folio_solicitud}.pdf`;
        this.solicitudService.downloadFile(blob, filename);
      },
      error: (error) => console.error('Error al descargar comprobante:', error)
    });
  }
  
  // 7️⃣ OBTENER ESTADÍSTICAS PARA DASHBOARD
  cargarEstadisticas() {
    this.solicitudService.estadisticas({
      fecha_desde: '2024-01-01',
      fecha_hasta: '2024-12-31'
    }).subscribe({
      next: (response) => {
        const stats = response.data;
        console.log('Estadísticas generales:', stats);
        
        // Usar para gráficos
        this.prepararGraficos(stats);
      },
      error: (error) => console.error('Error al obtener estadísticas:', error)
    });
  }
  
  // 8️⃣ OBTENER ESTADÍSTICAS POR ROL
  cargarEstadisticasPorRol() {
    this.solicitudService.estadisticasPorRol({
      rol: 'supervisor'
    }).subscribe({
      next: (response) => {
        console.log('Estadísticas del rol:', response.data);
      },
      error: (error) => console.error('Error:', error)
    });
  }
  
  // 9️⃣ BUSCAR EMPRESAS PARA AUTOCOMPLETADO
  buscarEmpresas(termino: string) {
    if (termino.length < 2) return;
    
    this.solicitudService.buscarEmpresasConstructoras(termino, 10).subscribe({
      next: (response) => {
        console.log('Empresas encontradas:', response.data);
        // Mostrar en dropdown de autocompletado
      },
      error: (error) => console.error('Error en búsqueda:', error)
    });
  }
  
  // 🔟 LISTAR POR ESTADO ESPECÍFICO
  filtrarPorEstado(estado: EstadoSolicitud) {
    this.solicitudService.listarPorEstado({
      estado: estado,
      page: 1,
      per_page: 20
    }).subscribe({
      next: (response) => {
        console.log(`Solicitudes en estado ${estado}:`, response.data);
        this.solicitudes = response.data.solicitudes;
      },
      error: (error) => this.error = error
    });
  }
  
  // Método auxiliar para preparar datos de gráficos
  private prepararGraficos(stats: EstadisticasSolicitudPago) {
    // Datos para gráfico de pastel (estados)
    const datosEstados = [
      { name: 'Pendientes', value: stats.por_estado.pendientes },
      { name: 'Procesando', value: stats.por_estado.procesando },
      { name: 'Aprobadas', value: stats.por_estado.aprobadas },
      { name: 'Pagadas', value: stats.por_estado.pagadas },
      { name: 'Rechazadas', value: stats.por_estado.rechazadas }
    ];
    
    // Datos para gráfico de barras (por mes)
    const datosPorMes = stats.por_mes?.map(mes => ({
      mes: mes.mes,
      total: mes.total,
      monto: mes.monto
    }));
    
    console.log('Datos para gráficos preparados');
  }
  
  // Método para obtener el color del estado
  getColorEstado(estado: EstadoSolicitud): string {
    return this.solicitudService.getEstadoColor(estado);
  }
  
  // Método para obtener el ícono del estado
  getIconoEstado(estado: EstadoSolicitud): string {
    return this.solicitudService.getEstadoIcon(estado);
  }
}

*/