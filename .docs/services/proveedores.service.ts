import { Injectable } from '@angular/core';
import {
	HttpClient,
	HttpHeaders,
	HttpParams,
	HttpErrorResponse,
} from '@angular/common/http';
import { Observable, throwError } from 'rxjs';
import { catchError, retry } from 'rxjs/operators';
import { environment } from 'src/environments/environment';

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
	fecha_rechazo?: string;
	fecha_cancelado?: string;
	fecha_pago?: string;

	// Motivos
	motivo_rechazo?: string;
	motivo_cancelacion?: string;

	// Información de pago (actualizados según backend)
	monto_total?: string | number;
	monto_abonado?: number; // Campo actualizado del backend
	saldo_pendiente?: number; // Nuevo campo del backend
	pago_completo?: boolean; // Nuevo campo del backend
	porcentaje_pagado?: number; // Campo calculado del backend
	notas_abono?: string;
	observaciones_rechazo?: string;
	
	// Campos deprecated para compatibilidad
	monto_pagado?: number; // @deprecated - usar monto_abonado

	// URLs alternativas para compatibilidad
	url_factura_pdf?: string;
	url_factura_xml?: string;
	url_comprobante_pago?: string;

	// Relación con cotización
	cotizacion?: {
		id: number;
		fecha_cotizacion?: string;
		fecha_vencimiento?: string;
		observaciones?: string;
		detalles?: Array<{
			cantidad_cotizada: number;
			precio_unitario: number;
			subtotal: number;
			tiempo_entrega_dias: number;
			observaciones?: string;
		}>;
	};

	// Estados por rol (campos de la BD) - Valores numéricos del enum EstadoSolicitud
	dg?: number; // 0=PENDIENTE, 1=AUTORIZADA, 2=RECHAZADA, 3=PAGADO
	dt?: number;
	pc?: number; // CO (Coordinador)
	si?: number;
	da?: number;
	ro?: number; // Nuevo campo RO (Recursos Operativos)

	// Fechas por rol
	dg_fecha?: string;
	dt_fecha?: string;
	pc_fecha?: string;
	si_fecha?: string;
	da_fecha?: string;
	ro_fecha?: string; // Nueva fecha para RO

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

	// Cuentas bancarias del proveedor (desde el backend)
	cuentas_bancarias?: CuentaBancaria[];

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

	// Computed properties (added by frontend)
	roles_authorization_status?: string; // HTML formatted roles status
}

// Estados posibles de una solicitud de pago
export type EstadoSolicitud =
	'pendiente'
	| 'rechazada'
	| 'autorizada'
	| 'pagado';

// Empresa de construcción
export interface EmpresaConstrucc {
	id: number;
	nombre: string;
	razon_social: string;
	rfc: string;
	representante_legal?: string;
}

// Cuenta bancaria del proveedor
export interface CuentaBancaria {
	id: number;
	proveedor_id: number;
	alias?: string;
	banco_clave: string;
	banco_nombre: string;
	tipo_cuenta: string;
	campo_dependiente: string;
	titular_cuenta: string;
	referencia: string;
	estatus: number; // 0=INACTIVA, 1=ACTIVA, 2=SUSPENDIDA
	sucursal?: string;
	swift?: string;
	preferida: boolean;
	created_at: string;
	updated_at: string;
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
	fecha_rechazo?: string;
	fecha_cancelado?: string;
	fecha_pago?: string;

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
	fecha_rechazo_desde?: string;
	fecha_rechazo_hasta?: string;
	fecha_cancelado_desde?: string;
	fecha_cancelado_hasta?: string;
	fecha_pago_desde?: string;
	fecha_pago_hasta?: string;

	// Parámetros de ordenamiento y paginación
	sort_by?: 'numero_folio_solicitud' | 'descripcion_concepto' | 'estado_solicitud' | 'created_at' | 'updated_at';
	order?: 'asc' | 'desc';
	per_page?: number;
	page?: number;
}

// Request para autorización
export interface AutorizarRequest {
	rol: string; // Rol que autoriza: 'DG', 'DT', 'CO', 'SI'
	observaciones?: string;
	monto_autorizado?: number;
}

// Request para rechazo
export interface RechazarRequest {
	rol: string; // Rol que rechaza: 'DG', 'DT', 'CO', 'SI', 'DA'
	motivo_rechazo: string;
	observaciones?: string;
}

// Request para confirmación de pago
export interface ConfirmarPagoRequest {
	rol: string;
	comprobante: File; // Archivo de comprobante (required)
	monto_pagado: number; // Monto del abono (required)
	notas_abono?: string; // Notas del abono (opcional)
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

// ==========================================
// NUEVAS INTERFACES PARA LOS ENDPOINTS DE PROVEEDORES
// ==========================================

// Modelo de Proveedor para los endpoints de asociación
export interface ProveedorBasico {
	id: number;
	nombre_comercial: string;
	razon_social: string;
	rfc: string;
}

// Respuesta para listar proveedores asociados/no asociados
export interface ProveedoresResponse {
	success: boolean;
	message: string;
	data: ProveedorBasico[];
}

// Request para asociar proveedor
export interface AsociarProveedorRequest {
	proveedor_id: number;
}

// Respuesta para asociación de proveedor (retorna el proveedor actualizado)
export interface AsociarProveedorResponse {
	success: boolean;
	message: string;
	data: {
		id: number;
		nombre_comercial: string;
		razon_social: string;
		rfc: string;
		empresasConstrucc?: EmpresaConstrucc[];
		// otros campos del proveedor...
	};
}

@Injectable({
	providedIn: 'root',
})
export class ProveedoresService {
	private readonly apiUrl = environment.API_URL_CONSTRUCC_PROV || 'http://localhost:8000/api';
	private readonly baseUrl = `${this.apiUrl}/construcc/solicitudes-pago`;
	private readonly apiKey = environment.TOKEN_CONSTRUCC_APP || '';

	constructor(private http: HttpClient) { }

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
	autorizar(id: number, data: AutorizarRequest): Observable<ApiResponse<SolicitudPago>> {
		return this.http
			.post<ApiResponse<SolicitudPago>>(
				`${this.baseUrl}/${id}/autorizar`,
				data,
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
	confirmarPago(id: number, data: ConfirmarPagoRequest): Observable<ApiResponse<SolicitudPago>> {
		// Crear FormData para envío de archivo
		const formData = new FormData();
		formData.append('rol', data.rol);
		formData.append('comprobante', data.comprobante);
		formData.append('monto_pagado', data.monto_pagado.toString());

		if (data.notas_abono) {
			formData.append('notas_abono', data.notas_abono);
		}

		return this.http
			.post<ApiResponse<SolicitudPago>>(
				`${this.baseUrl}/${id}/confirmar-pago`,
				formData,
				{ headers: this.getMultipartHeaders() }
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

	/**
	 * Obtiene proveedores por empresa
	 * GET /api/construcc/solicitudes-pago/empresas-constructoras/{empresaId}/proveedores
	 */
	fetchProveedoresPorEmpresa(
		empresaId: number
	): Observable<ApiResponse<any[]>> {
		return this.http
			.get<ApiResponse<any[]>>(
				`${this.baseUrl}/empresa/${empresaId}/proveedores`,
				{ headers: this.getHeaders() }
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
			//procesando: '#42A5F5',      // Blue
			//con_comprobante: '#AB47BC', // Purple
			autorizada: '#66BB6A',        // Green
			pagado: '#4CAF50',          // Dark Green
			rechazada: '#F44336',       // Red
			//cancelado: '#9E9E9E'        // Gray
		};
		return colors[estado] || '#9E9E9E';
	}

	/**
	 * Obtiene el texto legible de un estado
	 */
	getEstadoTexto(estado: EstadoSolicitud): string {
		const textos: Record<EstadoSolicitud, string> = {
			pendiente: 'Pendiente',
			//	procesando: 'Procesando',
			//	con_comprobante: 'Con Comprobante',
			autorizada: 'Aprobado',
			pagado: 'Pagado',
			rechazada: 'Rechazado',
			//cancelado: 'Cancelado'
		};
		return textos[estado] || estado;
	}

	/**
	 * Obtiene el ícono asociado a un estado
	 */
	getEstadoIcon(estado: EstadoSolicitud): string {
		const icons: Record<EstadoSolicitud, string> = {
			pendiente: 'schedule',
			//procesando: 'cached',
			//con_comprobante: 'attach_file',
			autorizada: 'check_circle',
			pagado: 'payment',
			rechazada: 'cancel',
			//	cancelado: 'block'
		};
		return icons[estado] || 'help_outline';
	}

	/**
	 * Verifica si una solicitud puede cambiar a un estado específico
	 */
	puedeCambiarEstado(estadoActual: EstadoSolicitud, estadoDestino: EstadoSolicitud): boolean {
		const transicionesValidas: Record<EstadoSolicitud, EstadoSolicitud[]> = {
			pendiente: ['rechazada'],
			//	procesando: ['con_comprobante', 'aprobado', 'rechazado', 'cancelado'],
			//	con_comprobante: ['aprobado', 'rechazado', 'cancelado'],
			autorizada: ['pagado'],
			pagado: [], // Estado final
			rechazada: ['pendiente'], // Puede reprocesarse
			//	cancelado: [] // Estado final
		};

		// return transicionesValidas[estadoActual]?.includes(estadoDestino) || false;
		return true;
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

	// ==========================================
	// MÉTODOS DE UTILIDAD PARA PAGOS PARCIALES
	// ==========================================

	/**
	 * Calcula el porcentaje pagado de una solicitud
	 */
	calcularPorcentajePagado(solicitud: SolicitudPago): number {
		const montoTotal = Number(solicitud.monto_total) || 0;
		const montoAbonado = solicitud.monto_abonado || 0;
		return montoTotal > 0 ? Math.round((montoAbonado / montoTotal) * 100 * 100) / 100 : 0;
	}

	/**
	 * Verifica si una solicitud está pagada completamente
	 */
	esCompletamentePagada(solicitud: SolicitudPago): boolean {
		return solicitud.pago_completo === true || 
			   (solicitud.saldo_pendiente !== undefined && solicitud.saldo_pendiente <= 0);
	}

	/**
	 * Obtiene el estado de pago de una solicitud
	 */
	getEstadoPago(solicitud: SolicitudPago): 'sin_pago' | 'pago_parcial' | 'pago_completo' {
		const montoAbonado = solicitud.monto_abonado || 0;
		if (montoAbonado === 0) {
			return 'sin_pago';
		}
		return this.esCompletamentePagada(solicitud) ? 'pago_completo' : 'pago_parcial';
	}

	/**
	 * Formatea el estado de pago para mostrar en UI
	 */
	formatearEstadoPago(solicitud: SolicitudPago): string {
		const estadoPago = this.getEstadoPago(solicitud);
		const porcentaje = this.calcularPorcentajePagado(solicitud);
		
		switch (estadoPago) {
			case 'sin_pago':
				return 'Sin abonos';
			case 'pago_parcial':
				return `Parcial (${porcentaje}%)`;
			case 'pago_completo':
				return 'Pagado completamente';
			default:
				return 'Estado desconocido';
		}
	}

	/**
	 * Obtiene el color para el estado de pago
	 */
	getColorEstadoPago(solicitud: SolicitudPago): string {
		const estadoPago = this.getEstadoPago(solicitud);
		const colores = {
			sin_pago: '#F44336',      // Rojo
			pago_parcial: '#FF9800',  // Naranja
			pago_completo: '#4CAF50'  // Verde
		};
		return colores[estadoPago];
	}

	// ==========================================
	// MÉTODOS DE UTILIDAD PARA CUENTAS BANCARIAS
	// ==========================================

	/**
	 * Obtiene la cuenta bancaria preferida de un proveedor
	 */
	getCuentaPreferida(cuentasBancarias: CuentaBancaria[]): CuentaBancaria | null {
		const cuentaPreferida = cuentasBancarias.find(cuenta => cuenta.preferida && cuenta.estatus === 1);
		return cuentaPreferida || null;
	}

	/**
	 * Filtra cuentas bancarias activas
	 */
	getCuentasActivas(cuentasBancarias: CuentaBancaria[]): CuentaBancaria[] {
		return cuentasBancarias.filter(cuenta => cuenta.estatus === 1);
	}

	/**
	 * Formatea el nombre completo de una cuenta bancaria
	 */
	formatearNombreCuenta(cuenta: CuentaBancaria): string {
		const alias = cuenta.alias ? `${cuenta.alias} - ` : '';
		return `${alias}${cuenta.banco_nombre} (${cuenta.tipo_cuenta})`;
	}

	/**
	 * Obtiene el estado de una cuenta bancaria
	 */
	getEstadoCuentaBancaria(estatus: number): string {
		const estados = {
			0: 'Inactiva',
			1: 'Activa',
			2: 'Suspendida'
		};
		return estados[estatus as keyof typeof estados] || 'Desconocido';
	}

	/**
	 * Obtiene el color para el estado de cuenta bancaria
	 */
	getColorEstadoCuenta(estatus: number): string {
		const colores = {
			0: '#9E9E9E',    // Gris - Inactiva
			1: '#4CAF50',   // Verde - Activa  
			2: '#FF9800'    // Naranja - Suspendida
		};
		return colores[estatus as keyof typeof colores] || '#9E9E9E';
	}

	/**
	 * 🆕 Lista proveedores NO asociados a una empresa constructora
	 * GET /api/construcc/solicitudes-pago/empresa/{empresaId}/proveedores/no-asociados
	 */
	listarProveedoresNoAsociados(empresaId: number): Observable<ApiResponse<ProveedorBasico[]>> {
		return this.http
			.get<ApiResponse<ProveedorBasico[]>>(
				`${this.baseUrl}/empresa/${empresaId}/proveedores/no-asociados`,
				{ headers: this.getHeaders() }
			)
			.pipe(retry(1), catchError(this.handleError));
	}

	/**
	 * 🆕 Asocia un proveedor a una empresa constructora
	 * POST /api/construcc/solicitudes-pago/empresa/{empresaId}/proveedores/asociar
	 */
	asociarProveedorAEmpresa(
		empresaId: number,
		proveedorId: number
	): Observable<ApiResponse<any>> {
		const data: AsociarProveedorRequest = {
			proveedor_id: proveedorId
		};

		return this.http
			.post<ApiResponse<any>>(
				`${this.baseUrl}/empresa/${empresaId}/proveedores/asociar`,
				data,
				{ headers: this.getHeaders() }
			)
			.pipe(retry(1), catchError(this.handleError));
	}

	// ==========================================
	// 🆕 NUEVOS MÉTODOS PARA FILTRADO POR FECHA_RECHAZO Y FECHA_PAGO
	// ==========================================

	/**
	 * 🆕 Filtra solicitudes por fecha de rechazo específica
	 */
	filtrarPorFechaRechazo(
		fecha: string,
		params?: { page?: number; per_page?: number }
	): Observable<PaginatedResponse<SolicitudPago>> {
		const filters: SolicitudesPagoFilters = {
			fecha_rechazo: fecha,
			...params
		};
		return this.index(filters);
	}

	/**
	 * 🆕 Filtra solicitudes por rango de fechas de rechazo
	 */
	filtrarPorRangoFechaRechazo(
		fechaDesde: string,
		fechaHasta: string,
		params?: { page?: number; per_page?: number }
	): Observable<PaginatedResponse<SolicitudPago>> {
		const filters: SolicitudesPagoFilters = {
			fecha_rechazo_desde: fechaDesde,
			fecha_rechazo_hasta: fechaHasta,
			...params
		};
		return this.index(filters);
	}

	/**
	 * 🆕 Filtra solicitudes por fecha de pago específica
	 */
	filtrarPorFechaPago(
		fecha: string,
		params?: { page?: number; per_page?: number }
	): Observable<PaginatedResponse<SolicitudPago>> {
		const filters: SolicitudesPagoFilters = {
			fecha_pago: fecha,
			...params
		};
		return this.index(filters);
	}

	/**
	 * 🆕 Filtra solicitudes por rango de fechas de pago
	 */
	filtrarPorRangoFechaPago(
		fechaDesde: string,
		fechaHasta: string,
		params?: { page?: number; per_page?: number }
	): Observable<PaginatedResponse<SolicitudPago>> {
		const filters: SolicitudesPagoFilters = {
			fecha_pago_desde: fechaDesde,
			fecha_pago_hasta: fechaHasta,
			...params
		};
		return this.index(filters);
	}

	/**
	 * 🆕 Obtiene reporte de solicitudes rechazadas en un período
	 */
	reporteSolicitudesRechazadas(
		fechaDesde: string,
		fechaHasta: string
	): Observable<PaginatedResponse<SolicitudPago>> {
		const filters: SolicitudesPagoFilters = {
			estado_solicitud: 'rechazada',
			fecha_rechazo_desde: fechaDesde,
			fecha_rechazo_hasta: fechaHasta,
			sort_by: 'created_at',
			order: 'desc',
			per_page: 100
		};
		return this.index(filters);
	}

	/**
	 * 🆕 Obtiene reporte de pagos realizados en un período
	 */
	reportePagosRealizados(
		fechaDesde: string,
		fechaHasta: string
	): Observable<PaginatedResponse<SolicitudPago>> {
		const filters: SolicitudesPagoFilters = {
			estado_solicitud: 'pagado',
			fecha_pago_desde: fechaDesde,
			fecha_pago_hasta: fechaHasta,
			sort_by: 'created_at',
			order: 'desc',
			per_page: 100
		};
		return this.index(filters);
	}
}

// ==========================================
// 🆕 EJEMPLOS DE USO ACTUALIZADOS
// ==========================================

/*

// En tu componente Angular:

import { Component, OnInit } from '@angular/core';
import { ProveedoresService, SolicitudPago } from './proveedores.service';

@Component({
	selector: 'app-solicitudes-pago',
	templateUrl: './solicitudes-pago.component.html'
})
export class SolicitudesPagoComponent implements OnInit {
	solicitudes: SolicitudPago[] = [];
	loading = false;
	error: string | null = null;

	constructor(
		private solicitudService: ProveedoresService
	) {}

	ngOnInit() {
		this.cargarSolicitudes();
	}

	// 🆕 Ejemplos usando los nuevos campos de fecha

	// 1️⃣ FILTRAR POR FECHA DE RECHAZO
	filtrarRechazosDeHoy() {
		const hoy = new Date().toISOString().split('T')[0]; // Formato YYYY-MM-DD

		this.solicitudService.filtrarPorFechaRechazo(hoy, {
			page: 1,
			per_page: 20
		}).subscribe({
			next: (response) => {
				this.solicitudes = response.data;
				console.log('Solicitudes rechazadas hoy:', response.data);
			},
			error: (error) => this.error = error
		});
	}

	// 2️⃣ FILTRAR POR RANGO DE FECHAS DE PAGO
	filtrarPagosDelMes() {
		const inicioMes = new Date();
		inicioMes.setDate(1);
		const finMes = new Date();
		finMes.setMonth(finMes.getMonth() + 1);
		finMes.setDate(0);

		this.solicitudService.filtrarPorRangoFechaPago(
			inicioMes.toISOString().split('T')[0],
			finMes.toISOString().split('T')[0],
			{ per_page: 50 }
		).subscribe({
			next: (response) => {
				console.log('Pagos del mes:', response.data);
				// Calcular total pagado
				const totalPagado = response.data.reduce((sum, solicitud) =>
					sum + (Number(solicitud.monto_total) || 0), 0);
				console.log('Total pagado este mes:', totalPagado);
			},
			error: (error) => console.error('Error:', error)
		});
	}

	// 3️⃣ REPORTE DE RECHAZOS
	generarReporteRechazos() {
		const fechaDesde = '2024-01-01';
		const fechaHasta = '2024-12-31';

		this.solicitudService.reporteSolicitudesRechazadas(fechaDesde, fechaHasta)
			.subscribe({
				next: (response) => {
					const rechazos = response.data;
					console.log('Reporte de rechazos:', rechazos);

					// Agrupar por motivo de rechazo
					const porMotivo = rechazos.reduce((acc, solicitud) => {
						const motivo = solicitud.motivo_rechazo || 'Sin motivo';
						acc[motivo] = (acc[motivo] || 0) + 1;
						return acc;
					}, {} as Record<string, number>);

					console.log('Rechazos por motivo:', porMotivo);
				},
				error: (error) => console.error('Error en reporte:', error)
			});
	}

	// 4️⃣ REPORTE DE PAGOS
	generarReportePagos() {
		const fechaDesde = '2024-01-01';
		const fechaHasta = '2024-12-31';

		this.solicitudService.reportePagosRealizados(fechaDesde, fechaHasta)
			.subscribe({
				next: (response) => {
					const pagos = response.data;
					console.log('Reporte de pagos:', pagos);

					// Calcular estadísticas
					const estadisticas = {
						totalSolicitudes: pagos.length,
						montoTotalPagado: pagos.reduce((sum, p) => sum + (Number(p.monto_total) || 0), 0),
						promedioMonto: pagos.length > 0 ?
							pagos.reduce((sum, p) => sum + (Number(p.monto_total) || 0), 0) / pagos.length : 0,
						pagosConNotas: pagos.filter(p => p.notas_abono).length
					};

					console.log('Estadísticas de pagos:', estadisticas);
				},
				error: (error) => console.error('Error en reporte:', error)
			});
	}

	// 5️⃣ FILTROS COMBINADOS CON LOS NUEVOS CAMPOS
	busquedaAvanzada() {
		const filtros = {
			estado_solicitud: ['rechazada', 'pagado'] as any,
			fecha_rechazo_desde: '2024-01-01',
			fecha_pago_desde: '2024-01-01',
			proveedor_id: [1, 2, 3],
			sort_by: 'created_at' as any,
			order: 'desc' as any,
			per_page: 25
		};

		this.solicitudService.index(filtros).subscribe({
			next: (response) => {
				console.log('Búsqueda avanzada:', response.data);
				
				// Separar por tipo
				const rechazadas = response.data.filter(s => s.estado_solicitud === 'rechazada');
				const pagadas = response.data.filter(s => s.estado_solicitud === 'pagado');

				console.log(`Encontradas: ${rechazadas.length} rechazadas, ${pagadas.length} pagadas`);
			},
			error: (error) => console.error('Error en búsqueda:', error)
		});
	}

	// 6️⃣ MOSTRAR INFORMACIÓN DE FECHAS EN EL HTML
	formatearFecha(fecha?: string): string {
		if (!fecha) return 'No disponible';
		return new Date(fecha).toLocaleDateString('es-MX', {
			year: 'numeric',
			month: 'long',
			day: 'numeric',
			hour: '2-digit',
			minute: '2-digit'
		});
	}

	// 7️⃣ TRABAJAR CON LOS NUEVOS CAMPOS DE PAGO
	mostrarInformacionPago(solicitud: SolicitudPago) {
		console.log('=== INFORMACIÓN DE PAGO ===');
		console.log('Monto Total:', solicitud.monto_total);
		console.log('Monto Abonado:', solicitud.monto_abonado);
		console.log('Saldo Pendiente:', solicitud.saldo_pendiente);
		console.log('Pago Completo:', solicitud.pago_completo);
		console.log('Porcentaje Pagado:', solicitud.porcentaje_pagado + '%');
		console.log('Estado de Pago:', this.solicitudService.formatearEstadoPago(solicitud));
		console.log('Notas de Abono:', solicitud.notas_abono || 'Sin notas');
	}

	// 8️⃣ TRABAJAR CON CUENTAS BANCARIAS
	mostrarCuentasBancarias(solicitud: SolicitudPago) {
		console.log('=== CUENTAS BANCARIAS DEL PROVEEDOR ===');
		if (!solicitud.cuentas_bancarias || solicitud.cuentas_bancarias.length === 0) {
			console.log('No hay cuentas bancarias registradas');
			return;
		}

		// Mostrar cuenta preferida
		const cuentaPreferida = this.solicitudService.getCuentaPreferida(solicitud.cuentas_bancarias);
		if (cuentaPreferida) {
			console.log('Cuenta Preferida:', this.solicitudService.formatearNombreCuenta(cuentaPreferida));
			console.log('Referencia:', cuentaPreferida.referencia);
			console.log('Titular:', cuentaPreferida.titular_cuenta);
		}

		// Listar todas las cuentas activas
		const cuentasActivas = this.solicitudService.getCuentasActivas(solicitud.cuentas_bancarias);
		console.log(`Cuentas Activas (${cuentasActivas.length}):`);
		cuentasActivas.forEach((cuenta, index) => {
			console.log(`  ${index + 1}. ${this.solicitudService.formatearNombreCuenta(cuenta)}`);
			console.log(`     Referencia: ${cuenta.referencia}`);
			console.log(`     Estado: ${this.solicitudService.getEstadoCuentaBancaria(cuenta.estatus)}`);
			console.log(`     Preferida: ${cuenta.preferida ? 'Sí' : 'No'}`);
		});
	}

	// 9️⃣ TRABAJAR CON EL NUEVO CAMPO RO
	mostrarEstadosAprobacion(solicitud: SolicitudPago) {
		console.log('=== ESTADOS DE APROBACIÓN POR ROL ===');
		const roles = [
			{ campo: 'dg', fecha: 'dg_fecha', nombre: 'Director General' },
			{ campo: 'dt', fecha: 'dt_fecha', nombre: 'Director Técnico' },
			{ campo: 'pc', fecha: 'pc_fecha', nombre: 'Coordinador de Proyecto' },
			{ campo: 'si', fecha: 'si_fecha', nombre: 'Superintendente' },
			{ campo: 'da', fecha: 'da_fecha', nombre: 'Director Administrativo' },
			{ campo: 'ro', fecha: 'ro_fecha', nombre: 'Recursos Operativos' } // NUEVO
		];

		roles.forEach(rol => {
			const estado = (solicitud as any)[rol.campo];
			const fecha = (solicitud as any)[rol.fecha];
			const estadoTexto = this.getEstadoTextoNumerico(estado);
			console.log(`${rol.nombre}: ${estadoTexto} ${fecha ? `(${this.formatearFecha(fecha)})` : ''}`);
		});
	}

	// Método auxiliar para convertir valores numéricos a texto
	getEstadoTextoNumerico(valor?: number): string {
		if (valor === undefined || valor === null) return 'Pendiente';
		const estados = {
			0: 'Pendiente',
			1: 'Autorizada',
			2: 'Rechazada',
			3: 'Pagado'
		};
		return estados[valor as keyof typeof estados] || 'Desconocido';
	}

	// Otros métodos originales...
	cargarSolicitudes() {
		this.loading = true;
		this.error = null;

		this.solicitudService.index({
			sort_by: 'created_at',
			order: 'desc',
			per_page: 20
		}).subscribe({
			next: (response) => {
				this.solicitudes = response.data;
				this.loading = false;
			},
			error: (error) => {
				this.error = error;
				this.loading = false;
			}
		});
	}
}

*/

/*
<!-- En tu template HTML: -->

<div class="solicitudes-container">
	<!-- Botones de filtros rápidos -->
	<div class="filtros-rapidos">
		<button (click)="filtrarRechazosDeHoy()">
			Rechazos de Hoy
		</button>
		<button (click)="filtrarPagosDelMes()">
			Pagos del Mes
		</button>
		<button (click)="generarReporteRechazos()">
			Reporte Rechazos
		</button>
		<button (click)="generarReportePagos()">
			Reporte Pagos
		</button>
	</div>

	<!-- Tabla de solicitudes -->
	<table class="solicitudes-table">
		<thead>
			<tr>
				<th>Folio</th>
				<th>Estado</th>
				<th>Monto</th>
				<th>🆕 Estado Pago</th>
				<th>🆕 Cuenta Preferida</th>
				<th>Fecha Creación</th>
				<th>🆕 Fecha Rechazo</th>
				<th>🆕 Fecha Pago</th>
				<th>Acciones</th>
			</tr>
		</thead>
		<tbody>
			<tr *ngFor="let solicitud of solicitudes">
				<td>{{ solicitud.numero_folio_solicitud }}</td>
				<td>
					<span [style.color]="solicitudService.getEstadoColor(solicitud.estado_solicitud)">
						{{ solicitudService.getEstadoTexto(solicitud.estado_solicitud) }}
					</span>
				</td>
				<td>{{ solicitud.monto_total | currency:'MXN':'symbol':'1.2-2' }}</td>
				<td>
					<span [style.color]="solicitudService.getColorEstadoPago(solicitud)"
						  [title]="'Abonado: ' + (solicitud.monto_abonado | currency:'MXN':'symbol':'1.2-2') + ' | Saldo: ' + (solicitud.saldo_pendiente | currency:'MXN':'symbol':'1.2-2')">
						{{ solicitudService.formatearEstadoPago(solicitud) }}
					</span>
				</td>
				<td>
					<span *ngIf="solicitudService.getCuentaPreferida(solicitud.cuentas_bancarias || []) as cuentaPreferida; else noCuenta">
						{{ solicitudService.formatearNombreCuenta(cuentaPreferida) }}
						<br><small>{{ cuentaPreferida.referencia }}</small>
					</span>
					<ng-template #noCuenta>
						<span class="no-disponible">Sin cuenta</span>
					</ng-template>
				</td>
				<td>{{ formatearFecha(solicitud.created_at) }}</td>
				<td>
					<span *ngIf="solicitud.fecha_rechazo; else noFechaRechazo"
							class="fecha-rechazo">
						{{ formatearFecha(solicitud.fecha_rechazo) }}
					</span>
					<ng-template #noFechaRechazo>
						<span class="no-disponible">-</span>
					</ng-template>
				</td>
				<td>
					<span *ngIf="solicitud.fecha_pago; else noFechaPago"
							class="fecha-pago">
						{{ formatearFecha(solicitud.fecha_pago) }}
					</span>
					<ng-template #noFechaPago>
						<span class="no-disponible">-</span>
					</ng-template>
				</td>
				<td>
					<button (click)="verDetalle(solicitud.id)">Ver</button>
				</td>
			</tr>
		</tbody>
	</table>
</div>

<style>
.fecha-rechazo {
	color: #f44336;
	font-weight: 500;
}

.fecha-pago {
	color: #4caf50;
	font-weight: 500;
}

.no-disponible {
	color: #9e9e9e;
	font-style: italic;
}
</style>
*/
