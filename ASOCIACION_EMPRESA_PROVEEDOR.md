# Asociación Empresa-Proveedor

Documentación de los componentes creados para la funcionalidad de asociación entre empresas constructoras y proveedores.

## 📁 Archivos Creados

### Backend (Laravel)

#### 1. Notificación
**Ubicación:** `app/Notifications/SolicitudPago/ProveedorAsociadoAEmpresa.php`

Notificación multicanal que se envía al proveedor cuando es asociado a una empresa constructora.

**Características:**
- ✅ Notificación en base de datos
- ✅ Envío de email
- ✅ Push notification via FCM
- ✅ Broadcasting en tiempo real

**Parámetros:**
- `proveedorId`: ID del proveedor
- `proveedorNombre`: Nombre comercial del proveedor
- `empresaId`: ID de la empresa constructora
- `empresaNombre`: Nombre de la empresa
- `empresaRfc`: RFC de la empresa
- `usuarioConstruccId`: ID del usuario de Construcc que realizó la vinculación
- `usuarioConstruccNombre`: Nombre del usuario de Construcc

#### 2. Vista de Email
**Ubicación:** `resources/views/emails/asociacion-empresa/nueva-asociacion.blade.php`

Email HTML responsivo con diseño moderno que informa al proveedor sobre la nueva asociación.

**Elementos visuales:**
- Header con gradiente azul
- Card de información de la empresa
- Badges de éxito
- Botón de acción para ver detalles
- Diseño responsive para móviles

#### 3. Integración en Controlador
**Archivo modificado:** `app/Http/Controllers/ConstruccSolicitudPagoController.php`

Se agregó el envío de notificación en el método `asociarProveedorAEmpresa()` (línea ~937-961).

**Cambios:**
- Import de la clase `ProveedorAsociadoAEmpresa`
- Try-catch para envío robusto de notificaciones
- Logging de errores

### Frontend (Angular/Ionic)

#### 4. Componente de Empresas Vinculadas

**Ubicación:** `src/app/pages/proveedor/empresas-vinculadas/`

Componente completo para mostrar el listado de empresas asociadas a un proveedor.

**Archivos:**
- `empresas-vinculadas.page.html` - Template con cards y searchbar
- `empresas-vinculadas.page.ts` - Lógica del componente
- `empresas-vinculadas.page.scss` - Estilos basados en design-system
- `empresas-vinculadas.module.ts` - Módulo Angular
- `empresas-vinculadas.page.spec.ts` - Tests

**Características del componente:**
- 🔍 Searchbar con filtrado en tiempo real
- 📱 Cards responsive y modernas
- 🎨 Iconos y badges informativos
- ♻️ Pull-to-refresh
- 🔄 Estados de carga
- 📭 Empty states personalizados
- 🌙 Soporte para modo oscuro

**Elementos visuales:**
- Toolbar con mensaje explicativo
- Cards con gradiente azul
- Información de empresa (nombre, RFC, razón social)
- Badges con contador de solicitudes y usuario que vinculó
- Animaciones suaves en hover
- Estados vacíos ilustrados

## 🎨 Diseño

El diseño sigue los patrones establecidos en:
- `design-system.scss` - Sistema de diseño base
- `shared-styles.scss` - Estilos compartidos
- `proveedores-list.page.scss` - Referencia de listados

### Colores utilizados:
- Primary: `#3880ff` (Azul)
- Success: `#4CAF50` (Verde)
- Medium: `#92949c` (Gris)
- Light: `#f8f9fa` (Gris claro)

### Características de diseño:
- Border radius: `12px` para cards
- Sombras suaves con elevación
- Transiciones de `0.3s`
- Iconos de Ionicons
- Tipografía responsive

## 🔌 Integración

### Para usar el componente Angular:

1. **Agregar ruta en el routing:**
```typescript
{
  path: 'empresas-vinculadas',
  loadChildren: () => import('./empresas-vinculadas/empresas-vinculadas.module').then(m => m.EmpresasVinculadasPageModule)
}
```

2. **Navegar al componente:**
```typescript
this.router.navigate(['/pages/proveedor/empresas-vinculadas']);
```

3. **Implementar servicio backend:**
El componente tiene datos de ejemplo. Debes crear un servicio para conectar con el endpoint:
```typescript
// TODO en empresas-vinculadas.page.ts línea 44
this.empresasService.getEmpresasVinculadas()
```

### Endpoint sugerido:
```
GET /api/proveedor/empresas-vinculadas
```

Respuesta esperada:
```json
{
  "data": [
    {
      "id": 1,
      "nombre": "Constructora ABC",
      "razon_social": "ABC Construcciones SA de CV",
      "rfc": "ABC123456ABC",
      "solicitudes_count": 15,
      "vinculado_por": "Juan Pérez",
      "usuario_construcc_id": 123,
      "fecha_vinculacion": "2025-01-10"
    }
  ]
}
```

## 🧪 Testing

### Backend
Probar el envío de notificación:
```bash
php artisan tinker
$proveedor = App\Models\Proveedor::find(1);
$proveedor->notify(new App\Notifications\SolicitudPago\ProveedorAsociadoAEmpresa(...));
```

### Frontend
```bash
npm test
```

## 📋 Tareas Pendientes

- [ ] Crear endpoint backend para obtener empresas vinculadas
- [ ] Crear servicio Angular `EmpresasVinculadasService`
- [ ] Agregar ruta en el routing principal
- [ ] Agregar botón de navegación en el menú/dashboard
- [ ] Crear tests unitarios completos
- [ ] Agregar analytics/tracking
- [ ] Implementar paginación si hay muchas empresas

## 📝 Notas

- La notificación se envía automáticamente cuando se asocia un proveedor a una empresa
- El email usa Blade templates de Laravel
- El componente Angular está listo para recibir datos reales
- Los estilos son totalmente responsive
- Compatible con modo oscuro
