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

// ==========================================
// MÉTODOS A AGREGAR AL FINAL DEL SERVICIO
// (Después del método getConfigInfo())
// ==========================================

	// ==========================================
	// MÉTODOS DE GESTIÓN DE PROVEEDORES POR EMPRESA
	// ==========================================

	/**
	 * Lista proveedores asociados a una empresa constructora
	 * GET /api/construcc/solicitudes-pago/empresa/{empresaId}/proveedores
	 */
	listarProveedoresAsociados(empresaId: number): Observable<ApiResponse<ProveedorBasico[]>> {
		return this.http
			.get<ApiResponse<ProveedorBasico[]>>(
				`${this.baseUrl}/empresa/${empresaId}/proveedores`,
				{ headers: this.getHeaders() }
			)
			.pipe(retry(1), catchError(this.handleError));
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

	/**
	 * 🆕 Método de conveniencia: Asocia múltiples proveedores a una empresa
	 * Ejecuta múltiples asociaciones de manera secuencial
	 */
	asociarMultiplesProveedores(
		empresaId: number, 
		proveedoresIds: number[]
	): Observable<ApiResponse<any>[]> {
		const asociaciones = proveedoresIds.map(proveedorId => 
			this.asociarProveedorAEmpresa(empresaId, proveedorId)
		);

		// Ejecutar todas las asociaciones en paralelo
		return forkJoin(asociaciones);
	}

	/**
	 * 🆕 Obtiene resumen de proveedores por empresa
	 * Combina proveedores asociados y no asociados en una sola respuesta
	 */
	obtenerResumenProveedoresPorEmpresa(empresaId: number): Observable<{
		asociados: ProveedorBasico[];
		noAsociados: ProveedorBasico[];
		totalAsociados: number;
		totalNoAsociados: number;
	}> {
		return forkJoin({
			asociados: this.listarProveedoresAsociados(empresaId),
			noAsociados: this.listarProveedoresNoAsociados(empresaId)
		}).pipe(
			map(results => ({
				asociados: results.asociados.data || [],
				noAsociados: results.noAsociados.data || [],
				totalAsociados: results.asociados.data?.length || 0,
				totalNoAsociados: results.noAsociados.data?.length || 0
			})),
			catchError(error => {
				console.error('Error al obtener resumen de proveedores:', error);
				return of({
					asociados: [],
					noAsociados: [],
					totalAsociados: 0,
					totalNoAsociados: 0
				});
			})
		);
	}

// ==========================================
// IMPORTACIONES ADICIONALES REQUERIDAS
// (Agregar al inicio del archivo, junto con las otras importaciones de RxJS)
// ==========================================

import { forkJoin, of } from 'rxjs';
import { map } from 'rxjs/operators';

// ==========================================
// EJEMPLOS DE USO DE LOS NUEVOS MÉTODOS
// ==========================================

/*

// En tu componente Angular:

export class GestionProveedoresComponent implements OnInit {
	empresaId = 14; // ID de la empresa constructora
	proveedoresAsociados: ProveedorBasico[] = [];
	proveedoresDisponibles: ProveedorBasico[] = [];
	loading = false;
	error: string | null = null;

	constructor(
		private solicitudService: SolicitudesPagoService
	) {}

	ngOnInit() {
		this.cargarProveedores();
	}

	// 1️⃣ CARGAR PROVEEDORES ASOCIADOS Y DISPONIBLES
	cargarProveedores() {
		this.loading = true;
		this.error = null;

		// Usando el método de resumen (recomendado)
		this.solicitudService.obtenerResumenProveedoresPorEmpresa(this.empresaId)
			.subscribe({
				next: (resumen) => {
					this.proveedoresAsociados = resumen.asociados;
					this.proveedoresDisponibles = resumen.noAsociados;
					
					console.log(`Empresa ${this.empresaId}:`);
					console.log(`- Proveedores asociados: ${resumen.totalAsociados}`);
					console.log(`- Proveedores disponibles: ${resumen.totalNoAsociados}`);
					
					this.loading = false;
				},
				error: (error) => {
					this.error = error;
					this.loading = false;
				}
			});
	}

	// 2️⃣ CARGAR SOLO PROVEEDORES NO ASOCIADOS
	cargarProveedoresDisponibles() {
		this.solicitudService.listarProveedoresNoAsociados(this.empresaId)
			.subscribe({
				next: (response) => {
					this.proveedoresDisponibles = response.data;
					console.log('Proveedores disponibles:', response.data);
				},
				error: (error) => {
					console.error('Error al cargar proveedores disponibles:', error);
					this.error = error;
				}
			});
	}

	// 3️⃣ ASOCIAR UN PROVEEDOR INDIVIDUAL
	asociarProveedor(proveedorId: number) {
		this.solicitudService.asociarProveedorAEmpresa(this.empresaId, proveedorId)
			.subscribe({
				next: (response) => {
					console.log('Proveedor asociado exitosamente:', response);
					
					// Mostrar mensaje de éxito
					this.mostrarMensaje(response.message, 'success');
					
					// Recargar listas
					this.cargarProveedores();
				},
				error: (error) => {
					console.error('Error al asociar proveedor:', error);
					this.error = error;
					this.mostrarMensaje('Error al asociar el proveedor', 'error');
				}
			});
	}

	// 4️⃣ ASOCIAR MÚLTIPLES PROVEEDORES
	asociarVariosProveedores(proveedoresIds: number[]) {
		if (proveedoresIds.length === 0) {
			this.mostrarMensaje('Selecciona al menos un proveedor', 'warning');
			return;
		}

		this.loading = true;
		
		this.solicitudService.asociarMultiplesProveedores(this.empresaId, proveedoresIds)
			.subscribe({
				next: (responses) => {
					const exitosos = responses.filter(r => r.success).length;
					const fallidos = responses.length - exitosos;
					
					if (fallidos === 0) {
						this.mostrarMensaje(
							`${exitosos} proveedores asociados exitosamente`, 
							'success'
						);
					} else {
						this.mostrarMensaje(
							`${exitosos} exitosos, ${fallidos} fallidos`, 
							'warning'
						);
					}
					
					this.cargarProveedores();
					this.loading = false;
				},
				error: (error) => {
					console.error('Error en asociaciones múltiples:', error);
					this.error = error;
					this.loading = false;
				}
			});
	}

	// 5️⃣ BUSCAR PROVEEDORES DISPONIBLES PARA UNA EMPRESA
	buscarProveedoresDisponibles(terminoBusqueda: string) {
		// Primero cargar proveedores no asociados
		this.solicitudService.listarProveedoresNoAsociados(this.empresaId)
			.subscribe({
				next: (response) => {
					// Filtrar localmente por el término de búsqueda
					const proveedoresFiltrados = response.data.filter(proveedor => 
						proveedor.nombre_comercial.toLowerCase().includes(terminoBusqueda.toLowerCase()) ||
						proveedor.razon_social.toLowerCase().includes(terminoBusqueda.toLowerCase()) ||
						proveedor.rfc.toLowerCase().includes(terminoBusqueda.toLowerCase())
					);
					
					this.proveedoresDisponibles = proveedoresFiltrados;
				},
				error: (error) => console.error('Error en búsqueda:', error)
			});
	}

	// 6️⃣ VALIDAR ESTADO ANTES DE ASOCIAR
	puedeAsociarProveedor(proveedorId: number): boolean {
		// Verificar que el proveedor no esté ya en la lista de asociados
		return !this.proveedoresAsociados.some(p => p.id === proveedorId);
	}

	// 7️⃣ OBTENER ESTADÍSTICAS DE ASOCIACIONES
	obtenerEstadisticasAsociaciones() {
		this.solicitudService.obtenerResumenProveedoresPorEmpresa(this.empresaId)
			.subscribe({
				next: (resumen) => {
					const estadisticas = {
						totalProveedores: resumen.totalAsociados + resumen.totalNoAsociados,
						porcentajeAsociados: resumen.totalAsociados > 0 
							? (resumen.totalAsociados / (resumen.totalAsociados + resumen.totalNoAsociados) * 100).toFixed(1)
							: 0,
						proveedoresPendientes: resumen.totalNoAsociados
					};
					
					console.log('Estadísticas de asociaciones:', estadisticas);
					// Usar para mostrar en dashboard o gráficos
				},
				error: (error) => console.error('Error al obtener estadísticas:', error)
			});
	}

	// 8️⃣ MÉTODO AUXILIAR PARA MOSTRAR MENSAJES
	private mostrarMensaje(mensaje: string, tipo: 'success' | 'error' | 'warning' | 'info') {
		// Implementar según tu sistema de notificaciones (Toast, Snackbar, etc.)
		console.log(`[${tipo.toUpperCase()}] ${mensaje}`);
	}

	// 9️⃣ MANEJO DE SELECCIÓN MÚLTIPLE EN UI
	proveedoresSeleccionados: number[] = [];
	
	toggleSeleccionProveedor(proveedorId: number) {
		const index = this.proveedoresSeleccionados.indexOf(proveedorId);
		if (index > -1) {
			this.proveedoresSeleccionados.splice(index, 1);
		} else {
			this.proveedoresSeleccionados.push(proveedorId);
		}
	}

	asociarSeleccionados() {
		if (this.proveedoresSeleccionados.length > 0) {
			this.asociarVariosProveedores(this.proveedoresSeleccionados);
			this.proveedoresSeleccionados = []; // Limpiar selección
		}
	}

	// 🔟 EXPORTAR DATOS PARA REPORTES
	exportarResumenProveedores() {
		this.solicitudService.obtenerResumenProveedoresPorEmpresa(this.empresaId)
			.subscribe({
				next: (resumen) => {
					const datosExportacion = {
						empresa_id: this.empresaId,
						fecha_reporte: new Date().toISOString(),
						resumen: {
							total_asociados: resumen.totalAsociados,
							total_disponibles: resumen.totalNoAsociados
						},
						proveedores_asociados: resumen.asociados,
						proveedores_disponibles: resumen.noAsociados
					};
					
					// Crear y descargar archivo JSON
					const blob = new Blob(
						[JSON.stringify(datosExportacion, null, 2)], 
						{ type: 'application/json' }
					);
					
					this.solicitudService.downloadFile(
						blob, 
						`resumen_proveedores_empresa_${this.empresaId}_${new Date().getTime()}.json`
					);
				},
				error: (error) => console.error('Error al exportar:', error)
			});
	}
}

*/