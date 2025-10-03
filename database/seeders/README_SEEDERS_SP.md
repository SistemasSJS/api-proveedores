# 📋 Seeders para Proveedores SP (Solicitudes de Pago)

## 📖 Descripción

Esta documentación describe los nuevos seeders creados específicamente para proveedores de tipo **SP (Solicitudes de Pago)** configurados para **Los Mochis, Sinaloa, México**.

## 🗂️ Seeders Incluidos

### 1. **ProveedoresSPSeeder.php**
- ✅ Agrega 4 proveedores adicionales tipo SP
- 🏢 Empresas específicas de Los Mochis, Sinaloa
- 📍 Direcciones reales de la zona metropolitana
- ☎️ Teléfonos con lada 668 (Los Mochis)

### 2. **CotizacionesSPSeeder.php**
- ✅ Genera cotizaciones para todos los proveedores SP
- 📊 2-4 cotizaciones por proveedor
- 🕐 Fechas configuradas para zona horaria America/Mazatlan
- 💰 Montos realistas entre $50.00 y $5,000.00

### 3. **CotizacionDetalleSeeder.php**
- ✅ Crea detalles para cada cotización SP
- 🔗 Relaciona productos con cotizaciones
- 💲 Calcula totales automáticamente
- ⏰ Tiempos de entrega realistas

### 4. **SolicitudPagoSeeder.php**
- ✅ Genera solicitudes basadas en cotizaciones aceptadas
- 📋 Folios con formato SP-YYYY-XXXXXX
- 🏗️ Relaciona con empresas constructoras
- 📁 Rutas de archivos simuladas (PDF/XML)

## 🚀 Orden de Ejecución

**IMPORTANTE:** Los seeders deben ejecutarse en este orden específico:

```bash
# 1. Seeders base (deben existir primero)
php artisan db:seed --class=TipoEmpresaSeeder
php artisan db:seed --class=EmpresaConstruccSeeder
php artisan db:seed --class=SucursalSeeder
php artisan db:seed --class=ProductoSeeder

# 2. Proveedores originales (si no existen)
php artisan db:seed --class=ProveedorSeeder

# 3. Nuevos seeders SP (en orden)
php artisan db:seed --class=ProveedoresSPSeeder
php artisan db:seed --class=CotizacionesSPSeeder
php artisan db:seed --class=CotizacionDetalleSeeder
php artisan db:seed --class=SolicitudPagoSeeder
```

## 📊 Datos Generados

### Proveedores SP Agregados:
1. **Materiales de Construcción Los Mochis**
   - Tipo: Comercial
   - Ubicación: Av. Gabriel Leyva 450, Centro

2. **Construcciones y Servicios Sinaloa**
   - Tipo: Obra Civil
   - Ubicación: Carretera Los Mochis-Topolobampo Km 8.5

3. **Ferretería y Plomería del Pacífico**
   - Tipo: Industrial
   - Ubicación: Blvd. Centenario 1250, Col. Scally

4. **Transportes y Maquinaria Noroeste**
   - Tipo: Industrial
   - Ubicación: Periférico Norte 2100, Parque Industrial

### Estadísticas Aproximadas:
- 🏢 **Proveedores SP**: 7 total (3 originales + 4 nuevos)
- 📄 **Cotizaciones**: ~21-28 cotizaciones
- 📋 **Detalles**: ~63-168 detalles de cotización
- 💳 **Solicitudes**: ~15-20 solicitudes de pago

## 🔧 Comandos Útiles

### Ejecutar todos los seeders SP de una vez:
```bash
php artisan db:seed --class=ProveedoresSPSeeder
php artisan db:seed --class=CotizacionesSPSeeder
php artisan db:seed --class=CotizacionDetalleSeeder
php artisan db:seed --class=SolicitudPagoSeeder
```

### Verificar datos generados:
```bash
# Contar proveedores SP
php artisan tinker --execute="echo App\Models\Proveedor::where('is_proveedor_sp', true)->count() . ' proveedores SP';"

# Contar cotizaciones
php artisan tinker --execute="echo App\Models\Cotizacion::count() . ' cotizaciones';"

# Contar solicitudes de pago
php artisan tinker --execute="echo App\Models\SolicitudPago::count() . ' solicitudes de pago';"
```

### Limpiar datos SP (si es necesario):
```bash
# ⚠️ CUIDADO: Esto eliminará TODOS los datos
php artisan tinker --execute="App\Models\SolicitudPago::truncate();"
php artisan tinker --execute="App\Models\CotizacionDetalle::truncate();"
php artisan tinker --execute="App\Models\Cotizacion::truncate();"
# NO eliminar proveedores ya que pueden tener otras relaciones
```

## 🌍 Configuración Regional

### Zona Horaria: America/Mazatlan
- ✅ Todas las fechas se generan en zona horaria correcta
- 🕐 Horario de Los Mochis, Sinaloa (MST/MDT)

### Datos Locales:
- 📞 Teléfonos con lada 668 (Los Mochis)
- 🏠 Direcciones reales de la ciudad
- 👥 Nombres mexicanos comunes en la región
- 🏢 Empresas con giros típicos de Sinaloa

## 📋 Estados de Solicitudes Generadas

| Estado | Probabilidad | Descripción |
|--------|--------------|-------------|
| Pendiente | 30% | Recién creadas |
| Procesando | 25% | En revisión |
| Autorizada | 20% | Aprobadas para pago |
| Rechazada | 10% | Con motivo de rechazo |
| Cancelada | 5% | Canceladas |
| Pagada | 10% | Completadas |

## 🔗 Relaciones Creadas

```
Proveedor (SP)
    ├── Cotizaciones (2-4 por proveedor)
    │   └── CotizacionDetalles (2-6 por cotización)
    │       └── Productos (relacionados)
    └── SolicitudesPago (basadas en cotizaciones aceptadas)
        ├── EmpresaConstrucc (asignada aleatoriamente)
        ├── Sucursal (si existe)
        └── Estados por departamento (DG, DT, PC, SI, RO)
```

## 📁 Archivos Simulados

Los seeders generan rutas de archivos simuladas con estructura realista:

```
uploads/
├── facturas/
│   ├── pdf/2025/10/factura_001.pdf
│   └── xml/2025/10/factura_001.xml
└── comprobantes/
    └── 2025/10/comprobante_pago_001.pdf
```

## ⚠️ Notas Importantes

1. **No modifica seeders existentes** - Solo agrega nuevos
2. **Respeta relaciones existentes** - No altera datos previos  
3. **Configurado para producción** - Datos realistas para Los Mochis
4. **Zona horaria correcta** - America/Mazatlan
5. **Caracteres especiales** - Soporte completo UTF-8

## 🐛 Solución de Problemas

### Error: "No se encontraron proveedores SP"
```bash
# Ejecutar primero los seeders base
php artisan db:seed --class=ProveedorSeeder
php artisan db:seed --class=ProveedoresSPSeeder
```

### Error: "No se encontraron empresas constructoras"
```bash
php artisan db:seed --class=EmpresaConstruccSeeder
```

### Error: "No se encontraron productos"
```bash
php artisan db:seed --class=ProductoSeeder
```

---

**📧 Para soporte técnico con estos seeders, revisar la documentación del proyecto en `/docs/`**

*Seeders desarrollados específicamente para Los Mochis, Sinaloa, México* 🇲🇽