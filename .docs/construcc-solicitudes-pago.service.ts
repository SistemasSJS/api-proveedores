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
	estado_solicitud: EstadoSolicitud;
	ruta_archivo_factura_pdf?: string;
	ruta_archivo_factura_xml?: string;
	ruta_archivo_comprobante_pago?: string;
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
	| "pendiente"
	| "procesando" 
	| "con_comprobante"
	| "aprobado"
	| "pagado"
	| "rechazado"
	| "cancelado";

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
	sort_by?: "numero_folio_solicitud" | "descripcion_concepto" | "estado_solicitud" | "created_at" | "updated_at";
	order?: "asc" | "desc";
	per_page?: number;
	page?: number;
}

// Request para cambio de estatus con motivo
export interface CambioEstatusRequest {
	motivo_rechazo?: string;
	motivo_cancelacion?: string;
}

// Estadísticas de solicitudes de pago
export interface EstadisticasSP {
	total: number;
	pendientes: number;
	procesando: number;
	con_comprobante: number;
	aprobadas: number;
	pagadas: number;
	rechazadas: number;
	canceladas: number;
}

@Injectable({
	providedIn: "root",
})
export class ConstruccSolicitudesPagoService {
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

	private getMultipartHeaders(): HttpHeaders {
		const token = this.getToken();
		return new HttpHeaders({
			Accept: "application/json",
			...(token && { Authorization: `Bearer ${token}` }),
			// No establecemos Content-Type para multipart, Angular lo hace automáticamente
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

		console.error("Error en ConstruccSolicitudesPagoService:", error);
		return throwError(errorMessage);
	}

	// ==========================================
	// MÉTODOS CRUD BÁSICOS
	// ==========================================

	/**
	 * Obtiene lista paginada de solicitudes de pago con filtros
	 */
	getSolicitudesPago(
		filters: SolicitudesPagoFilters = {}
	): Observable<PaginatedResponse<SolicitudPago>> {
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
	 */
	getSolicitudPago(id: number): Observable<ApiResponse<SolicitudPago>> {
		return this.http
			.get<ApiResponse<SolicitudPago>>(
				`${this.baseUrl}/${id}`,
				{ headers: this.getHeaders() }
			)
			.pipe(retry(1), catchError(this.handleError));
	}

	// ==========================================
	// MÉTODOS PARA GESTIÓN DE ARCHIVOS
	// ==========================================

	/**
	 * Sube comprobante de pago
	 */
	subirComprobantePago(
		solicitudId: number, 
		archivo: File
	): Observable<ApiResponse<SolicitudPago>> {
		const formData = new FormData();
		formData.append('comprobante', archivo);

		return this.http
			.post<ApiResponse<SolicitudPago>>(
				`${this.baseUrl}/${solicitudId}/comprobante`,
				formData,
				{ headers: this.getMultipartHeaders() }
			)
			.pipe(retry(1), catchError(this.handleError));
	}

	/**
	 * Descarga comprobante de pago
	 */
	descargarComprobante(solicitudId: number): Observable<Blob> {
		return this.http
			.get(
				`${this.baseUrl}/${solicitudId}/comprobante/download`,
				{ 
					headers: this.getHeaders(),
					responseType: 'blob'
				}
			)
			.pipe(retry(1), catchError(this.handleError));
	}

	/**
	 * Descarga factura PDF
	 */
	descargarFacturaPdf(solicitudId: number): Observable<Blob> {
		return this.http
			.get(
				`${this.baseUrl}/${solicitudId}/factura-pdf/download`,
				{ 
					headers: this.getHeaders(),
					responseType: 'blob'
				}
			)
			.pipe(retry(1), catchError(this.handleError));
	}

	/**
	 * Descarga factura XML
	 */
	descargarFacturaXml(solicitudId: number): Observable<Blob> {
		return this.http
			.get(
				`${this.baseUrl}/${solicitudId}/factura-xml/download`,
				{ 
					headers: this.getHeaders(),
					responseType: 'blob'
				}
			)
			.pipe(retry(1), catchError(this.handleError));
	}

	// ==========================================
	// MÉTODOS PARA CAMBIOS DE ESTATUS
	// ==========================================

	/**
	 * Cambia estado a "procesando"
	 */
	cambiarAProcesando(solicitudId: number): Observable<ApiResponse<SolicitudPago>> {
		return this.http
			.patch<ApiResponse<SolicitudPago>>(
				`${this.baseUrl}/${solicitudId}/procesando`,
				{},
				{ headers: this.getHeaders() }
			)
			.pipe(retry(1), catchError(this.handleError));
	}

	/**
	 * Cambia estado a "aprobado"
	 */
	aprobarSolicitud(solicitudId: number): Observable<ApiResponse<SolicitudPago>> {
		return this.http
			.patch<ApiResponse<SolicitudPago>>(
				`${this.baseUrl}/${solicitudId}/aprobar`,
				{},
				{ headers: this.getHeaders() }
			)
			.pipe(retry(1), catchError(this.handleError));
	}

	/**
	 * Cambia estado a "rechazado" con motivo
	 */
	rechazarSolicitud(
		solicitudId: number, 
		motivo: string
	): Observable<ApiResponse<SolicitudPago>> {
		const body: CambioEstatusRequest = {
			motivo_rechazo: motivo
		};

		return this.http
			.patch<ApiResponse<SolicitudPago>>(
				`${this.baseUrl}/${solicitudId}/rechazar`,
				body,
				{ headers: this.getHeaders() }
			)
			.pipe(retry(1), catchError(this.handleError));
	}

	/**
	 * Cambia estado a "cancelado" con motivo
	 */
	cancelarSolicitud(
		solicitudId: number, 
		motivo: string
	): Observable<ApiResponse<SolicitudPago>> {
		const body: CambioEstatusRequest = {
			motivo_cancelacion: motivo
		};

		return this.http
			.patch<ApiResponse<SolicitudPago>>(
				`${this.baseUrl}/${solicitudId}/cancelar`,
				body,
				{ headers: this.getHeaders() }
			)
			.pipe(retry(1), catchError(this.handleError));
	}

	/**
	 * Confirma el pago de una solicitud
	 */
	confirmarPago(solicitudId: number): Observable<ApiResponse<SolicitudPago>> {
		return this.http
			.patch<ApiResponse<SolicitudPago>>(
				`${this.baseUrl}/${solicitudId}/confirmar-pago`,
				{},
				{ headers: this.getHeaders() }
			)
			.pipe(retry(1), catchError(this.handleError));
	}

	// ==========================================
	// MÉTODOS AUXILIARES
	// ==========================================

	/**
	 * Busca empresas constructoras para autocompletado
	 */
	buscarEmpresasConstructoras(
		search = "", 
		limit = 20
	): Observable<ApiResponse<EmpresaConstrucc[]>> {
		let params = new HttpParams()
			.set("limit", limit.toString());

		if (search) {
			params = params.set("search", search);
		}

		return this.http
			.get<ApiResponse<EmpresaConstrucc[]>>(
				`${this.baseUrl}/empresas-constructoras/search`,
				{ headers: this.getHeaders(), params }
			)
			.pipe(retry(1), catchError(this.handleError));
	}

	/**
	 * Obtiene estadísticas de solicitudes de pago
	 */
	getEstadisticas(empresaConstructId?: number): Observable<ApiResponse<EstadisticasSP>> {
		let params = new HttpParams();
		
		if (empresaConstructId) {
			params = params.set("empresa_construcc_id", empresaConstructId.toString());
		}

		return this.http
			.get<ApiResponse<EstadisticasSP>>(
				`${this.baseUrl}/estadisticas`,
				{ headers: this.getHeaders(), params }
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
		const colors = {
			pendiente: "#FFA726",      // Orange
			procesando: "#42A5F5",     // Blue  
			con_comprobante: "#AB47BC", // Purple
			aprobado: "#66BB6A",       // Green
			pagado: "#4CAF50",         // Dark Green
			rechazado: "#F44336",      // Red
			cancelado: "#9E9E9E"       // Gray
		};
		return colors[estado] || "#9E9E9E";
	}

	/**
	 * Obtiene el texto legible de un estado
	 */
	getEstadoTexto(estado: EstadoSolicitud): string {
		const textos = {
			pendiente: "Pendiente",
			procesando: "Procesando",
			con_comprobante: "Con Comprobante",
			aprobado: "Aprobado",
			pagado: "Pagado",
			rechazado: "Rechazado",
			cancelado: "Cancelado"
		};
		return textos[estado] || estado;
	}

	/**
	 * Verifica si una solicitud puede cambiar a un estado específico
	 */
	puedecambiarEstado(estadoActual: EstadoSolicitud, estadoDestino: EstadoSolicitud): boolean {
		const transicionesValidas: Record<EstadoSolicitud, EstadoSolicitud[]> = {
			pendiente: ["procesando", "rechazado", "cancelado"],
			procesando: ["con_comprobante", "aprobado", "rechazado", "cancelado"],
			con_comprobante: ["aprobado", "rechazado", "cancelado"],
			aprobado: ["pagado", "cancelado"],
			pagado: [], // Estado final
			rechazado: ["procesando"], // Puede reprocesarse
			cancelado: [] // Estado final
		};

		return transicionesValidas[estadoActual]?.includes(estadoDestino) || false;
	}
}

// ==========================================
// EJEMPLOS DE USO
// ==========================================

/*

// 1. EJEMPLO BÁSICO - Obtener solicitudes de pago
this.spService.getSolicitudesPago({
  empresa_construcc_id: [1],
  estado_solicitud: ["pendiente", "procesando"],
  per_page: 20,
  page: 1
}).subscribe({
  next: (response) => {
    console.log('Solicitudes:', response.data);
    console.log('Total:', response.pagination.total);
  },
  error: (error) => console.error('Error:', error)
});

// 2. EJEMPLO CON FILTROS DE FECHA - Solicitudes del mes actual
const inicioMes = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0];
const finMes = new Date(new Date().getFullYear(), new Date().getMonth() + 1, 0).toISOString().split('T')[0];

this.spService.getSolicitudesPago({
  fecha_registro_pendiente_desde: inicioMes,
  fecha_registro_pendiente_hasta: finMes,
  empresa_construcc_id: [1],
  sort_by: 'created_at',
  order: 'desc'
}).subscribe({
  next: (response) => {
    console.log('Solicitudes del mes:', response.data);
  }
});

// 3. EJEMPLO PARA CAMBIAR ESTATUS - Aprobar solicitud
this.spService.aprobarSolicitud(solicitudId).subscribe({
  next: (response) => {
    console.log('Solicitud aprobada:', response.data);
    // Actualizar la lista local
    this.actualizarSolicitudEnLista(response.data);
  },
  error: (error) => {
    console.error('Error al aprobar:', error);
    // Mostrar mensaje de error al usuario
  }
});

// 4. EJEMPLO PARA RECHAZAR CON MOTIVO
this.spService.rechazarSolicitud(
  solicitudId, 
  "La factura no cumple con los requisitos fiscales"
).subscribe({
  next: (response) => {
    console.log('Solicitud rechazada:', response.data);
    this.mostrarMensaje('Solicitud rechazada correctamente');
  }
});

// 5. EJEMPLO PARA SUBIR COMPROBANTE
onFileSelected(event: any, solicitudId: number) {
  const file = event.target.files[0];
  if (file) {
    this.spService.subirComprobantePago(solicitudId, file).subscribe({
      next: (response) => {
        console.log('Comprobante subido:', response.data);
        this.mostrarMensaje('Comprobante subido correctamente');
      },
      error: (error) => {
        console.error('Error al subir comprobante:', error);
      }
    });
  }
}

// 6. EJEMPLO PARA DESCARGAR ARCHIVO
descargarComprobante(solicitudId: number, nombreArchivo: string) {
  this.spService.descargarComprobante(solicitudId).subscribe({
    next: (blob) => {
      this.spService.downloadFile(blob, nombreArchivo);
    },
    error: (error) => {
      console.error('Error al descargar:', error);
    }
  });
}

// 7. EJEMPLO PARA ESTADÍSTICAS - Dashboard
loadEstadisticas() {
  this.spService.getEstadisticas(this.empresaId).subscribe({
    next: (response) => {
      const stats = response.data;
      console.log('Estadísticas:', stats);
      
      // Usar en gráficos o cards del dashboard
      this.totalSolicitudes = stats.total;
      this.solicitudesPendientes = stats.pendientes;
      this.solicitudesPagadas = stats.pagadas;
    }
  });
}

// 8. EJEMPLO CON VALIDACIÓN DE TRANSICIONES DE ESTADO
cambiarEstado(solicitud: SolicitudPago, nuevoEstado: EstadoSolicitud) {
  if (this.spService.puedecambiarEstado(solicitud.estado_solicitud, nuevoEstado)) {
    switch(nuevoEstado) {
      case 'procesando':
        this.spService.cambiarAProcesando(solicitud.id).subscribe(/*...*/);
        break;
      case 'aprobado':
        this.spService.aprobarSolicitud(solicitud.id).subscribe(/*...*/);
        break;
      // ... otros casos
    }
  } else {
    console.error('Transición de estado no válida');
  }
}

// 9. EJEMPLO PARA COMPONENTE CON PAGINACIÓN
export class SolicitudesPagoComponent {
  solicitudes: SolicitudPago[] = [];
  currentPage = 1;
  totalPages = 1;
  filters: SolicitudesPagoFilters = {};
  
  loadSolicitudes(page = 1) {
    this.spService.getSolicitudesPago({
      ...this.filters,
      page: page,
      per_page: 20
    }).subscribe({
      next: (response) => {
        this.solicitudes = response.data;
        this.currentPage = response.pagination.current_page;
        this.totalPages = response.pagination.last_page;
      }
    });
  }
  
  filtrarPorEstado(estado: EstadoSolicitud) {
    this.filters.estado_solicitud = estado;
    this.loadSolicitudes(1);
  }
  
  filtrarPorEmpresa(empresaId: number) {
    this.filters.empresa_construcc_id = [empresaId];
    this.loadSolicitudes(1);
  }
}

// 10. EJEMPLO PARA BÚSQUEDA DE EMPRESAS EN AUTOCOMPLETADO
searchEmpresas(term: string) {
  if (term.length >= 2) {
    this.spService.buscarEmpresasConstructoras(term, 10).subscribe({
      next: (response) => {
        this.empresasSugeridas = response.data;
      }
    });
  }
}

*/

/**
 * Clase para probar todos los endpoints del servicio
 */
export class TestConstruccSPService {
	constructor(private spService: ConstruccSolicitudesPagoService) {}

	testAllEndpoints() {
		const solicitudId = 1;
		const empresaId = 1;

		// =============================
		// 1️⃣ CRUD Básico
		// =============================
		this.spService.getSolicitudesPago({}).subscribe({
			next: (res) => console.log("getSolicitudesPago:", res),
			error: (err) => console.error("Error getSolicitudesPago:", err),
		});

		this.spService.getSolicitudPago(solicitudId).subscribe({
			next: (res) => console.log("getSolicitudPago:", res),
			error: (err) => console.error("Error getSolicitudPago:", err),
		});

		// =============================
		// 2️⃣ Cambios de Estatus
		// =============================
		this.spService.cambiarAProcesando(solicitudId).subscribe({
			next: (res) => console.log("cambiarAProcesando:", res),
			error: (err) => console.error("Error cambiarAProcesando:", err),
		});

		this.spService.aprobarSolicitud(solicitudId).subscribe({
			next: (res) => console.log("aprobarSolicitud:", res),
			error: (err) => console.error("Error aprobarSolicitud:", err),
		});

		this.spService.rechazarSolicitud(solicitudId, "Motivo de prueba").subscribe({
			next: (res) => console.log("rechazarSolicitud:", res),
			error: (err) => console.error("Error rechazarSolicitud:", err),
		});

		this.spService.cancelarSolicitud(solicitudId, "Cancelación de prueba").subscribe({
			next: (res) => console.log("cancelarSolicitud:", res),
			error: (err) => console.error("Error cancelarSolicitud:", err),
		});

		this.spService.confirmarPago(solicitudId).subscribe({
			next: (res) => console.log("confirmarPago:", res),
			error: (err) => console.error("Error confirmarPago:", err),
		});

		// =============================
		// 3️⃣ Auxiliares
		// =============================
		this.spService.buscarEmpresasConstructoras("construcción", 10).subscribe({
			next: (res) => console.log("buscarEmpresasConstructoras:", res),
			error: (err) => console.error("Error buscarEmpresasConstructoras:", err),
		});

		this.spService.getEstadisticas(empresaId).subscribe({
			next: (res) => console.log("getEstadisticas:", res),
			error: (err) => console.error("Error getEstadisticas:", err),
		});

		// =============================
		// 4️⃣ Filtros Avanzados
		// =============================
		this.spService.getSolicitudesPago({
			estado_solicitud: ["pendiente", "procesando"],
			empresa_construcc_id: [empresaId],
			fecha_registro_pendiente_desde: "2024-01-01",
			fecha_registro_pendiente_hasta: "2024-12-31"
		}).subscribe({
			next: (res) => console.log("getSolicitudesPago con filtros:", res),
			error: (err) => console.error("Error filtros avanzados:", err),
		});
	}
}