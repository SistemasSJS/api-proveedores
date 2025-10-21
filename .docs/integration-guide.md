# 🔧 Integration Guide - Adding New Provider Association Methods

## 📋 Step 1: Add New Interfaces

**Location**: Add these interfaces at the top of your service file, after the existing interfaces

```typescript
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

// Request para asociar proveedor
export interface AsociarProveedorRequest {
	proveedor_id: number;
}
```

## 📋 Step 2: Update RxJS Imports

**Location**: Add these imports to your existing RxJS imports at the top

```typescript
// Update your existing RxJS import to include:
import { Observable, throwError, forkJoin, of } from 'rxjs';
import { catchError, retry, map } from 'rxjs/operators';
```

## 📋 Step 3: Add New Methods

**Location**: Add these methods at the very end of your `SolicitudesPagoService` class, right before the closing brace `}`

```typescript
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
	 */
	asociarMultiplesProveedores(
		empresaId: number, 
		proveedoresIds: number[]
	): Observable<ApiResponse<any>[]> {
		const asociaciones = proveedoresIds.map(proveedorId => 
			this.asociarProveedorAEmpresa(empresaId, proveedorId)
		);

		return forkJoin(asociaciones);
	}

	/**
	 * 🆕 Obtiene resumen de proveedores por empresa
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

} // <-- Closing brace of SolicitudesPagoService class
```

## 📋 Step 4: Complete File Structure

Your final service structure should look like this:

```typescript
// Imports...
import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders, HttpParams, HttpErrorResponse } from '@angular/common/http';
import { Observable, throwError, forkJoin, of } from 'rxjs'; // 👈 Updated
import { catchError, retry, map } from 'rxjs/operators';    // 👈 Updated

// Existing interfaces...
export interface PaginatedResponse<T> { ... }
export interface ApiResponse<T = any> { ... }
export interface SolicitudPago { ... }
// ... other existing interfaces

// 👇 NEW: Add these interfaces
export interface ProveedorBasico {
	id: number;
	nombre_comercial: string;
	razon_social: string;
	rfc: string;
}

export interface AsociarProveedorRequest {
	proveedor_id: number;
}

@Injectable({
	providedIn: 'root',
})
export class SolicitudesPagoService {
	// Existing properties and constructor...
	
	// All existing methods...
	index() { ... }
	show() { ... }
	descargarComprobante() { ... }
	// ... all other existing methods
	
	getConfigInfo(): { apiUrl: string; hasApiKey: boolean } {
		return {
			apiUrl: this.apiUrl,
			hasApiKey: this.isApiKeyConfigured()
		};
	}

	// 👇 NEW: Add these methods here
	listarProveedoresAsociados(empresaId: number): Observable<ApiResponse<ProveedorBasico[]>> {
		// ... implementation
	}

	listarProveedoresNoAsociados(empresaId: number): Observable<ApiResponse<ProveedorBasico[]>> {
		// ... implementation
	}

	asociarProveedorAEmpresa(empresaId: number, proveedorId: number): Observable<ApiResponse<any>> {
		// ... implementation
	}

	asociarMultiplesProveedores(empresaId: number, proveedoresIds: number[]): Observable<ApiResponse<any>[]> {
		// ... implementation
	}

	obtenerResumenProveedoresPorEmpresa(empresaId: number): Observable<{...}> {
		// ... implementation
	}

} // 👈 End of class
```

## 🧪 Step 5: Quick Test

Add this test method to verify the integration:

```typescript
// In any component where you inject SolicitudesPagoService
testNewMethods() {
	const empresaId = 14;
	
	// Test 1: List unassociated providers
	this.solicitudService.listarProveedoresNoAsociados(empresaId).subscribe({
		next: (response) => console.log('✅ Providers not associated:', response.data),
		error: (error) => console.error('❌ Error:', error)
	});
	
	// Test 2: Get summary
	this.solicitudService.obtenerResumenProveedoresPorEmpresa(empresaId).subscribe({
		next: (summary) => console.log('✅ Summary:', summary),
		error: (error) => console.error('❌ Error:', error)
	});
}
```

## ✅ Verification Checklist

- [ ] Added new interfaces at the top
- [ ] Updated RxJS imports to include `forkJoin`, `of`, and `map`
- [ ] Added all 5 new methods at the end of the service class
- [ ] Verified no syntax errors
- [ ] Tested at least one method to confirm API connection

## 🚀 Usage Examples

```typescript
// In your component:
export class MyComponent {
	constructor(private solicitudService: SolicitudesPagoService) {}

	// Simple usage
	loadAvailableProviders() {
		this.solicitudService.listarProveedoresNoAsociados(14).subscribe({
			next: (response) => {
				console.log('Available providers:', response.data);
				// Use response.data in your component
			},
			error: (error) => console.error('Error loading providers:', error)
		});
	}

	// Associate a provider
	associateProvider(providerId: number) {
		this.solicitudService.asociarProveedorAEmpresa(14, providerId).subscribe({
			next: (response) => {
				console.log('Provider associated successfully:', response.message);
				// Refresh your lists
				this.loadAvailableProviders();
			},
			error: (error) => console.error('Error associating provider:', error)
		});
	}
}
```

---

🎉 **Ready to go!** Your service now has full support for provider-company association management.