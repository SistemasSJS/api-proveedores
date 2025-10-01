import { Injectable } from "@angular/core";
import {
	HttpClient,
	HttpHeaders,
	HttpParams,
	HttpErrorResponse,
} from "@angular/common/http";
import { Observable, throwError } from "rxjs";
import { catchError, retry } from "rxjs/operators";

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
	monto_total: number;
	estado_solicitud: 'pendiente' | 'procesando' | 'con_comprobante' | 'pagado' | 'autorizada' | 'rechazada';
	residente?: string;
	fecha_registro_pendiente?: string;
	fecha_inicio_procesamiento?: string;
	fecha_confirmacion_pago?: string;
	
	// Archivos
	ruta_archivo_factura_pdf?: string;
	ruta_archivo_factura_xml?: string;
	ruta_archivo_comprobante_pago?: string;
	
	// Relaciones
	empresaConstrucc?: {
		id: number;
		nombre: string;
		razon_social?: string;
	};
	sucursal?: {
		id: number;
		nombre: string;
	};
	
	created_at: string;
	updated_at: string;
}

// Empresa Constructora para búsquedas
export interface EmpresaConstructora {
	id: number;
	nombre: string;
	razon_social?: string;
	rfc?: string;
	activa: boolean;
}

// Estadísticas de SP
export interface EstadisticasSP {
	total_solicitudes: number;
	pendientes: number;
	procesando: number;
	con_comprobante: number;
	pagadas: number;
	autorizadas: number;
	rechazadas: number;
	monto_total: number;
	monto_pendiente: number;
	monto_pagado: number;
}

// Filtros para las consultas
export interface SolicitudesPagoFilters {
	buscar?: string;
	estado_solicitud?: string[] | string;
	empresa_id?: number[] | string;
	fecha_desde?: string;
	fecha_hasta?: string;
	monto_min?: number;
	monto_max?: number;
	con_comprobante?: boolean;
	sort_by?: "numero_folio_solicitud" | "monto_total" | "fecha_registro_pendiente" | "created_at" | "updated_at";
	order?: "asc" | "desc";
	per_page?: number;
	page?: number;
}

@Injectable({
	providedIn: "root",
})
export class SolicitudesPagoService {
	private readonly apiUrl = "http://localhost:8000/api"; // Ajustar según tu configuración
	private readonly baseUrl = `${this.apiUrl}/construcc/solicitudes-pago`;

	constructor(private http: HttpClient) {}

	// ==========================================
	// CONFIGURACIÓN DE HEADERS
	// ==========================================

	private getHeaders(): HttpHeaders {
		const token = this.getToken();
		return new HttpHeaders({
			"Content-Type": "application/json",
			Accept: "application/json",
			...(token && { Authorization: `Bearer ${token}` }),
		});
	}

	private getFileHeaders(): HttpHeaders {
		const token = this.getToken();
		return new HttpHeaders({
			Accept: "application/json",
			...(token && { Authorization: `Bearer ${token}` }),
		});
	}

	private getToken(): string | null {
		// Ajustar según donde guardes el token (localStorage, sessionStorage, etc.)
		return (
			localStorage.getItem("api_token") || sessionStorage.getItem("api_token")
		);
	}

	private buildParams(filters: any): HttpParams {
		let params = new HttpParams();

		Object.keys(filters).forEach((key) => {
			const value = filters[key];
			if (value !== undefined && value !== null && value !== "") {
				if (Array.isArray(value)) {
					// Convertir arrays a string separado por comas
					params = params.set(key, value.join(","));
				} else {
					params = params.set(key, value.toString());
				}
			}
		});

		return params;
	}

	private handleError(error: HttpErrorResponse) {
		let errorMessage = "Ha ocurrido un error desconocido.";

		if (error.error instanceof ErrorEvent) {
			// Error del lado del cliente
			errorMessage = `Error: ${error.error.message}`;
		} else {
			// Error del lado del servidor
			if (error.status === 401) {
				errorMessage = "No autorizado. Por favor, verifica tu token de acceso.";
			} else if (error.status === 403) {
				errorMessage = "No tienes permisos para realizar esta acción.";
			} else if (error.status === 404) {
				errorMessage = "Recurso no encontrado.";
			} else if (error.status === 422 && error.error.errors) {
				// Errores de validación
				const validationErrors = Object.values(error.error.errors).flat();
				errorMessage = validationErrors.join(", ");
			} else if (error.error?.message) {
				errorMessage = error.error.message;
			} else {
				errorMessage = `Error del servidor: ${error.status}`;
			}
		}

		console.error("Error en SolicitudesPagoService:", error);
		return throwError(errorMessage);
	}

	// ==========================================
	// MÉTODOS PRINCIPALES - LISTADO Y DETALLE
	// ==========================================

	/**
	 * Obtiene lista paginada de solicitudes de pago con filtros
	 * GET /api/construcc/solicitudes-pago
	 */
	getSolicitudesPago(
		filters: SolicitudesPagoFilters = {}
	): Observable<PaginatedResponse<SolicitudPago>> {
		const params = this.buildParams(filters);

		return this.http
			.get<PaginatedResponse<SolicitudPago>>(
				this.baseUrl,
				{ headers: this.getHeaders(), params }
			)
			.pipe(retry(1), catchError(this.handleError));
	}

	/**
	 * Obtiene detalle de una solicitud específica
	 * GET /api/construcc/solicitudes-pago/{id}
	 */
	getSolicitudPago(solicitudId: number): Observable<ApiResponse<SolicitudPago>> {
		return this.http
			.get<ApiResponse<SolicitudPago>>(
				`${this.baseUrl}/${solicitudId}`,
				{ headers: this.getHeaders() }
			)
			.pipe(retry(1), catchError(this.handleError));
	}

	// ==========================================
	// GESTIÓN DE ARCHIVOS
	// ==========================================

	/**
	 * Subir comprobante de pago
	 * POST /api/construcc/solicitudes-pago/{id}/comprobante
	 */
	subirComprobantePago(
		solicitudId: number,
		archivo: File,
		observaciones?: string
	): Observable<ApiResponse<any>> {
		const formData = new FormData();
		formData.append('comprobante', archivo);
		if (observaciones) {
			formData.append('observaciones', observaciones);
		}

		return this.http
			.post<ApiResponse<any>>(
				`${this.baseUrl}/${solicitudId}/comprobante`,
				formData,
				{ headers: this.getFileHeaders() }
			)
			.pipe(retry(1), catchError(this.handleError));
	}

	/**
	 * Descargar comprobante de pago
	 * GET /api/construcc/solicitudes-pago/{id}/comprobante/download
	 */
	descargarComprobante(solicitudId: number): Observable<Blob> {
		return this.http
			.get(
				`${this.baseUrl}/${solicitudId}/comprobante/download`,
				{
					headers: this.getFileHeaders(),
					responseType: 'blob'
				}
			)
			.pipe(retry(1), catchError(this.handleError));
	}

	/**
	 * Descargar factura PDF
	 * GET /api/construcc/solicitudes-pago/{id}/factura-pdf/download
	 */
	descargarFacturaPdf(solicitudId: number): Observable<Blob> {
		return this.http
			.get(
				`${this.baseUrl}/${solicitudId}/factura-pdf/download`,
				{
					headers: this.getFileHeaders(),
					responseType: 'blob'
				}
			)
			.pipe(retry(1), catchError(this.handleError));
	}

	/**
	 * Descargar factura XML
	 * GET /api/construcc/solicitudes-pago/{id}/factura-xml/download
	 */
	descargarFacturaXml(solicitudId: number): Observable<Blob> {
		return this.http
			.get(
				`${this.baseUrl}/${solicitudId}/factura-xml/download`,
				{
					headers: this.getFileHeaders(),
					responseType: 'blob'
				}
			)
			.pipe(retry(1), catchError(this.handleError));
	}

	// ==========================================
	// CAMBIOS DE ESTATUS
	// ==========================================

	/**
	 * Autorizar solicitud de pago
	 * PATCH /api/construcc/solicitudes-pago/{id}/autorizar
	 */
	autorizarSolicitud(
		solicitudId: number,
		observaciones?: string
	): Observable<ApiResponse<SolicitudPago>> {
		const data = observaciones ? { observaciones } : {};

		return this.http
			.patch<ApiResponse<SolicitudPago>>(
				`${this.baseUrl}/${solicitudId}/autorizar`,
				data,
				{ headers: this.getHeaders() }
			)
			.pipe(retry(1), catchError(this.handleError));
	}

	/**
	 * Rechazar solicitud de pago
	 * PATCH /api/construcc/solicitudes-pago/{id}/rechazar
	 */
	rechazarSolicitud(
		solicitudId: number,
		motivo: string
	): Observable<ApiResponse<SolicitudPago>> {
		return this.http
			.patch<ApiResponse<SolicitudPago>>(
				`${this.baseUrl}/${solicitudId}/rechazar`,
				{ motivo },
				{ headers: this.getHeaders() }
			)
			.pipe(retry(1), catchError(this.handleError));
	}

	/**
	 * Confirmar pago de solicitud
	 * PATCH /api/construcc/solicitudes-pago/{id}/confirmar-pago
	 */
	confirmarPago(
		solicitudId: number,
		datos_pago: {
			fecha_pago?: string;
			referencia_pago?: string;
			monto_pagado?: number;
			observaciones?: string;
		}
	): Observable<ApiResponse<SolicitudPago>> {
		return this.http
			.patch<ApiResponse<SolicitudPago>>(
				`${this.baseUrl}/${solicitudId}/confirmar-pago`,
				datos_pago,
				{ headers: this.getHeaders() }
			)
			.pipe(retry(1), catchError(this.handleError));
	}

	// ==========================================
	// ENDPOINTS AUXILIARES
	// ==========================================

	/**
	 * Buscar empresas constructoras
	 * GET /api/construcc/solicitudes-pago/empresas-constructoras/search
	 */
	buscarEmpresasConstructoras(
		termino: string,
		limite = 10
	): Observable<ApiResponse<{ empresas: EmpresaConstructora[] }>> {
		const params = new HttpParams()
			.set('termino', termino)
			.set('limite', limite.toString());

		return this.http
			.get<ApiResponse<{ empresas: EmpresaConstructora[] }>>(
				`${this.baseUrl}/empresas-constructoras/search`,
				{ headers: this.getHeaders(), params }
			)
			.pipe(retry(1), catchError(this.handleError));
	}

	/**
	 * Obtener estadísticas de solicitudes de pago
	 * GET /api/construcc/solicitudes-pago/estadisticas
	 */
	getEstadisticas(): Observable<ApiResponse<EstadisticasSP>> {
		return this.http
			.get<ApiResponse<EstadisticasSP>>(
				`${this.baseUrl}/estadisticas`,
				{ headers: this.getHeaders() }
			)
			.pipe(retry(1), catchError(this.handleError));
	}

	// ==========================================
	// MÉTODOS DE UTILIDAD
	// ==========================================

	/**
	 * Generar URL para visualizar documento en nueva ventana
	 */
	generarUrlVisualizacion(solicitudId: number, tipo: 'pdf' | 'xml' | 'comprobante'): string {
		const token = this.getToken();
		let endpoint = '';
		
		switch (tipo) {
			case 'pdf':
				endpoint = `${this.baseUrl}/${solicitudId}/factura-pdf/download`;
				break;
			case 'xml':
				endpoint = `${this.baseUrl}/${solicitudId}/factura-xml/download`;
				break;
			case 'comprobante':
				endpoint = `${this.baseUrl}/${solicitudId}/comprobante/download`;
				break;
		}
		
		return token ? `${endpoint}?token=${token}` : endpoint;
	}

	/**
	 * Abrir documento en nueva ventana
	 */
	abrirDocumento(solicitudId: number, tipo: 'pdf' | 'xml' | 'comprobante'): void {
		const url = this.generarUrlVisualizacion(solicitudId, tipo);
		window.open(url, '_blank');
	}

	/**
	 * Formatear monto a moneda mexicana
	 */
	formatearMonto(monto: number): string {
		return new Intl.NumberFormat('es-MX', {
			style: 'currency',
			currency: 'MXN',
			minimumFractionDigits: 2,
			maximumFractionDigits: 2,
		}).format(monto);
	}

	/**
	 * Obtener color del estado
	 */
	getColorEstado(estado: string): string {
		const colores: { [key: string]: string } = {
			pendiente: 'warning',
			procesando: 'tertiary',
			con_comprobante: 'primary',
			pagado: 'success',
			autorizada: 'success',
			rechazada: 'danger',
		};
		return colores[estado] || 'medium';
	}

	/**
	 * Obtener ícono del estado
	 */
	getIconoEstado(estado: string): string {
		const iconos: { [key: string]: string } = {
			pendiente: 'time-outline',
			procesando: 'sync-outline',
			con_comprobante: 'document-attach-outline',
			pagado: 'checkmark-circle-outline',
			autorizada: 'shield-checkmark-outline',
			rechazada: 'close-circle-outline',
		};
		return iconos[estado] || 'help-circle-outline';
	}
}

// ==========================================
// EJEMPLOS DE USO
// ==========================================

/*

// 1. EJEMPLO BÁSICO - Obtener solicitudes de pago
this.solicitudesPagoService.getSolicitudesPago({
  buscar: 'construccion',
  estado_solicitud: ['pendiente', 'procesando'],
  per_page: 20,
  page: 1
}).subscribe({
  next: (response) => {
    console.log('Solicitudes:', response.data);
    console.log('Total:', response.pagination.total);
  },
  error: (error) => console.error('Error:', error)
});

// 2. EJEMPLO - Obtener detalle de solicitud
this.solicitudesPagoService.getSolicitudPago(1).subscribe({
  next: (response) => {
    console.log('Detalle solicitud:', response.data);
  },
  error: (error) => console.error('Error:', error)
});

// 3. EJEMPLO - Subir comprobante de pago
const archivo = event.target.files[0]; // Del input file
this.solicitudesPagoService.subirComprobantePago(1, archivo, 'Comprobante de transferencia').subscribe({
  next: (response) => {
    console.log('Comprobante subido:', response.message);
  },
  error: (error) => console.error('Error:', error)
});

// 4. EJEMPLO - Descargar documento
this.solicitudesPagoService.descargarFacturaPdf(1).subscribe({
  next: (blob) => {
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'factura.pdf';
    a.click();
    window.URL.revokeObjectURL(url);
  },
  error: (error) => console.error('Error:', error)
});

// 5. EJEMPLO - Cambiar estado de solicitud
this.solicitudesPagoService.autorizarSolicitud(1, 'Autorizada por director').subscribe({
  next: (response) => {
    console.log('Solicitud autorizada:', response.data);
  },
  error: (error) => console.error('Error:', error)
});

// 6. EJEMPLO - Buscar empresas para autocompletado
this.solicitudesPagoService.buscarEmpresasConstructoras('ACME', 5).subscribe({
  next: (response) => {
    console.log('Empresas encontradas:', response.data.empresas);
  },
  error: (error) => console.error('Error:', error)
});

// 7. EJEMPLO - Obtener estadísticas
this.solicitudesPagoService.getEstadisticas().subscribe({
  next: (response) => {
    const stats = response.data;
    console.log('Total solicitudes:', stats.total_solicitudes);
    console.log('Monto total:', this.solicitudesPagoService.formatearMonto(stats.monto_total));
  },
  error: (error) => console.error('Error:', error)
});

// 8. EJEMPLO - Abrir documento en nueva ventana
this.solicitudesPagoService.abrirDocumento(1, 'pdf');

// 9. EJEMPLO EN COMPONENTE CON PAGINACIÓN
export class SolicitudesPagoComponent {
  solicitudes: SolicitudPago[] = [];
  currentPage = 1;
  totalPages = 1;
  
  loadSolicitudes(page = 1) {
    this.solicitudesPagoService.getSolicitudesPago({
      page: page,
      per_page: 20,
      sort_by: 'fecha_registro_pendiente',
      order: 'desc'
    }).subscribe({
      next: (response) => {
        this.solicitudes = response.data;
        this.currentPage = response.pagination.current_page;
        this.totalPages = response.pagination.last_page;
      }
    });
  }
  
  getColorEstado(estado: string): string {
    return this.solicitudesPagoService.getColorEstado(estado);
  }
  
  formatearMonto(monto: number): string {
    return this.solicitudesPagoService.formatearMonto(monto);
  }
}

*/