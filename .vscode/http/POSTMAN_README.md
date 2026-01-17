# Colección Postman - Construcción Proveedores y SPP

## 📦 Archivo de Colección

**Ubicación**: `Postman_Construcc_Proveedores_Collection.json`

## 🚀 Cómo Importar en Postman

### Método 1: Importar desde Archivo
1. Abre Postman
2. Click en **"Import"** (esquina superior izquierda)
3. Selecciona el archivo `Postman_Construcc_Proveedores_Collection.json`
4. Click en **"Import"**

### Método 2: Arrastrar y Soltar
1. Abre Postman
2. Arrastra el archivo JSON directamente a la ventana de Postman
3. La colección se importará automáticamente

## ⚙️ Configuración de Variables

La colección incluye 4 variables pre-configuradas:

| Variable | Valor por Defecto | Descripción |
|----------|-------------------|-------------|
| `baseUrl` | `http://localhost:80/api` | URL base del API |
| `apiKey` | `7f2wnCyn7ctmTE7B3mrtDPKCPVF9z8pYseihsHA6` | API Key para autenticación |
| `proveedorId` | `1` | ID del proveedor para pruebas |
| `cuentaId` | `1` | ID de cuenta bancaria para pruebas |

### Cómo Editar Variables
1. Click derecho en la colección
2. Selecciona **"Edit"**
3. Ve a la pestaña **"Variables"**
4. Modifica los valores según tu entorno

## 📂 Estructura de la Colección

```
📁 Construcción - Proveedores y SPP
│
├── 📁 1. PROVEEDORES (tipo_alta=2)
│   ├── 1.1 Listar Proveedores
│   ├── 1.2 Listar Proveedores con Filtros
│   ├── 1.3 Obtener Detalle de Proveedor
│   ├── 1.4 Crear Proveedor con Cuenta
│   ├── 1.5 Actualizar Proveedor (sin cuentas)
│   ├── 1.6 Actualizar Proveedor CON Cuentas
│   └── 1.7 Eliminar Proveedor
│
├── 📁 2. CUENTAS BANCARIAS
│   ├── 2.1 Listar Cuentas del Proveedor
│   ├── 2.2 Obtener Detalle de Cuenta
│   ├── 2.3 Crear Cuenta Bancaria
│   ├── 2.4 Actualizar Cuenta Bancaria
│   ├── 2.5 Eliminar Cuenta Bancaria
│   └── 2.6 Marcar Cuenta como Favorita
│
├── 📁 3. SPP - Proveedor Existente
│   ├── 3.1 Generar SPP - Residente (Pendiente)
│   ├── 3.2 Generar SPP - Director General (Autorizada)
│   └── 3.3 Generar SPP - Director Administrativo (Directo a Pago)
│
├── 📁 4. SPP - Crear Proveedor + Cuenta + SPP
│   ├── 4.1 Crear Proveedor Nuevo + SPP - Residente
│   └── 4.2 Crear Proveedor Nuevo + SPP - Director
│
└── 📁 5. Registro y Validación
    ├── 5.1 Registrar Proveedor Nuevo
    ├── 5.2 Intentar Registro Duplicado (tipo_alta=1)
    ├── 5.3 Registro con Proveedor tipo_alta=2 (Redirige)
    └── 5.4 Completar Registro
```

## 🎯 Campos Adicionales en SPP

Todos los endpoints de SPP incluyen estos campos opcionales:

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `obra_id` | integer | ID de la obra asociada |
| `tipo` | string | Tipo de solicitud (Material, Servicio, etc.) |
| `tipo_id` | integer | ID del tipo |
| `notas` | string | Notas adicionales (max 1000 caracteres) |
| `utilizara` | string | Para qué se utilizará |
| `equipo` | string | Nombre del equipo |
| `equipo_id` | integer | ID del equipo |

## 👥 Niveles de Usuario

| nivel_id | Rol | Comportamiento SPP |
|----------|-----|-------------------|
| 0 | Admin | Requiere aprobación (como residente) |
| 1 | Director General (DG) | ✅ Auto-aprueba → **AUTORIZADA** |
| 2 | Director Técnico (DT) | ✅ Auto-aprueba → **AUTORIZADA** |
| 3 | Director Administrativo (DA) | ⭐ Directo a pago → **AUTORIZADA** + da/pc |
| 4 | Superintendente (SI) | Solo verifica |
| 5 | Programación y Control (PC) | ✅ Auto-aprueba → **AUTORIZADA** |
| 6 | Residente de Obra (RO) | ⏳ Requiere aprobación → **PENDIENTE** |

## 📋 Flujos de Prueba Recomendados

### Flujo A: Proveedor Existente
1. Crear proveedor (1.4)
2. Ver detalle (1.3)
3. Crear cuenta adicional (2.3)
4. Generar SPP según nivel (3.1, 3.2 o 3.3)

### Flujo B: Proveedor Nuevo (Todo en Uno)
1. Crear proveedor + cuenta + SPP (4.1 o 4.2)
2. Verificar que se creó todo correctamente

### Flujo C: Validación de Registro
1. Intentar registro con teléfono tipo_alta=2 (5.3)
2. Completar registro (5.4)

## 🔧 Notas Importantes sobre Archivos

⚠️ **Para requests con archivos (form-data):**

Los requests de SPP (secciones 3 y 4) requieren archivos. En Postman:

1. Ve a la pestaña **"Body"**
2. Selecciona **"form-data"**
3. Los campos de archivo ya están configurados, pero debes:
   - Click en "Select Files" en cada campo de archivo
   - Navegar a: `C:/repositorio/app/api-proveedores/.vscode/http/tests/facturas/`
   - Seleccionar el archivo correspondiente

**Archivos necesarios:**
- `factura_pdf`: Cfdi-4539.pdf
- `factura_xml`: Cfdi-4539.xml
- `cotizacion`: Cfdi-4539.pdf (o cualquier archivo válido)

## 🔐 Autenticación

Todos los requests incluyen el header:
```
X-API-KEY: {{apiKey}}
```

El API Key está configurado como variable de colección.

## 📊 Códigos de Respuesta

| Código | Descripción |
|--------|-------------|
| 200 | OK - Operación exitosa (GET, PUT) |
| 201 | Created - Recurso creado exitosamente |
| 403 | Forbidden - Sin permisos |
| 404 | Not Found - Recurso no encontrado |
| 409 | Conflict - RFC/email/teléfono duplicado |
| 422 | Unprocessable Entity - Validación fallida |
| 500 | Internal Server Error |

## 🆘 Soporte

Si tienes problemas:
1. Verifica que las variables estén configuradas correctamente
2. Confirma que el servidor esté corriendo en `http://localhost:80`
3. Revisa que el `apiKey` sea válido
4. Para requests con archivos, asegúrate de seleccionar los archivos manualmente

## 📝 Cambios Recientes

### v1.0 (2026-01-13)
- ✅ Agregados campos adicionales en SPP: `obra_id`, `tipo`, `tipo_id`, `notas`, `utilizara`, `equipo`, `equipo_id`
- ✅ Incluidos ambos flujos: proveedor existente y creación completa
- ✅ Agregadas validaciones por nivel de usuario
- ✅ Documentación completa de endpoints

---

**Creado por**: Sistema de Gestión de Proveedores Construcción  
**Fecha**: Enero 2026  
**Versión**: 1.0
