// ==========================================
// INTERFACES PARA CUENTAS BANCARIAS
// ==========================================

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

// Agregar a la interface SolicitudPago existente:
export interface SolicitudPago {
	// ... otros campos existentes ...
	
	// Cuentas bancarias del proveedor (desde el backend)
	cuentas_bancarias?: CuentaBancaria[];
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
		1: '#4CAF50',    // Verde - Activa  
		2: '#FF9800'     // Naranja - Suspendida
	};
	return colores[estatus as keyof typeof colores] || '#9E9E9E';
}

// ==========================================
// EJEMPLOS DE USO
// ==========================================

// En tu componente:
mostrarCuentasBancarias(solicitud: SolicitudPago) {
	if (!solicitud.cuentas_bancarias || solicitud.cuentas_bancarias.length === 0) {
		console.log('No hay cuentas bancarias registradas');
		return;
	}

	// Mostrar cuenta preferida
	const cuentaPreferida = this.getCuentaPreferida(solicitud.cuentas_bancarias);
	if (cuentaPreferida) {
		console.log('Cuenta Preferida:', this.formatearNombreCuenta(cuentaPreferida));
		console.log('Referencia:', cuentaPreferida.referencia);
		console.log('Titular:', cuentaPreferida.titular_cuenta);
	}

	// Listar todas las cuentas activas
	const cuentasActivas = this.getCuentasActivas(solicitud.cuentas_bancarias);
	console.log(`Cuentas Activas (${cuentasActivas.length}):`);
	cuentasActivas.forEach((cuenta, index) => {
		console.log(`  ${index + 1}. ${this.formatearNombreCuenta(cuenta)}`);
		console.log(`     Referencia: ${cuenta.referencia}`);
		console.log(`     Estado: ${this.getEstadoCuentaBancaria(cuenta.estatus)}`);
		console.log(`     Preferida: ${cuenta.preferida ? 'Sí' : 'No'}`);
	});
}

// ==========================================
// TEMPLATE HTML PARA MOSTRAR CUENTAS
// ==========================================

/*
<!-- Mostrar cuenta bancaria preferida -->
<td>
	<span *ngIf="getCuentaPreferida(solicitud.cuentas_bancarias || []) as cuentaPreferida; else noCuenta">
		{{ formatearNombreCuenta(cuentaPreferida) }}
		<br><small>{{ cuentaPreferida.referencia }}</small>
	</span>
	<ng-template #noCuenta>
		<span class="no-disponible">Sin cuenta</span>
	</ng-template>
</td>

<!-- Lista de todas las cuentas bancarias -->
<div *ngFor="let cuenta of getCuentasActivas(solicitud.cuentas_bancarias || [])">
	<div class="cuenta-bancaria">
		<h4>{{ formatearNombreCuenta(cuenta) }}</h4>
		<p>
			<strong>Referencia:</strong> {{ cuenta.referencia }}<br>
			<strong>Titular:</strong> {{ cuenta.titular_cuenta }}<br>
			<strong>Estado:</strong> 
			<span [style.color]="getColorEstadoCuenta(cuenta.estatus)">
				{{ getEstadoCuentaBancaria(cuenta.estatus) }}
			</span><br>
			<strong>Preferida:</strong> {{ cuenta.preferida ? 'Sí' : 'No' }}
		</p>
	</div>
</div>
*/