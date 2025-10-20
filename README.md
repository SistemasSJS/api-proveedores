# API Proveedores - Documentación Completa del Proyecto

## 📋 Descripción
API backend para el sistema de gestión de proveedores de SJS Construcciones con sistema de notificaciones en tiempo real.

## 🚀 Requisitos Previos

- **Node.js** (versión 14 o superior)
- **PHP** (versión 7.4 o superior)
- **Composer** (gestor de paquetes de PHP)
- **Git** (control de versiones)
- **MySQL/MariaDB** (base de datos)
- **Soketi** (WebSocket server para notificaciones en tiempo real)

## 📁 Estructura del Proyecto

```
api-proveedores/
├── src/
│   ├── app/
│   │   ├── Jobs/           # Jobs de Laravel para procesamiento asíncrono
│   │   └── pages/
│   │       └── proveedor/
│   │           └── csv-import/
│   │               └── pages/
│   │                   └── resultados-importacion/
├── notificaciones/         # Sistema de notificaciones
├── vendor/                 # Dependencias PHP (generado por Composer)
├── node_modules/           # Dependencias Node.js (generado por npm)
├── composer.json
├── package.json
├── start-soketi-env.ps1    # Script para iniciar Soketi
└── .env                    # Configuración de entorno
```

## 🔧 Instalación y Configuración Completa

### 1. Clonar el Repositorio
```bash
git clone [URL_DEL_REPOSITORIO]
cd C:\repositorio\sjsconstrucciones\app\api-proveedores
```

### 2. Instalar Dependencias de PHP
```bash
composer install
```
Este comando instalará:
- Laravel/Lumen framework
- Sistema de colas y jobs
- Librerías de notificación
- Pusher PHP Server
- Otras dependencias del backend

### 3. Instalar Dependencias de Node.js
```bash
npm install
```

### 4. Configurar Variables de Entorno
Crear o verificar el archivo `.env` con las siguientes configuraciones:

```env
# Configuración de Base de Datos
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nombre_base_datos
DB_USERNAME=usuario
DB_PASSWORD=contraseña

# Configuración de la Aplicación
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_KEY=base64:XXXXXXXXXXXXXXXXXXXXXXXXXXXXX

# Configuración de Colas/Jobs
QUEUE_CONNECTION=database
QUEUE_DRIVER=database

# Configuración de Broadcasting/WebSockets
BROADCAST_DRIVER=pusher

# Configuración de Soketi (WebSocket Server)
PUSHER_APP_ID=app-id
PUSHER_APP_KEY=app-key
PUSHER_APP_SECRET=app-secret
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http
PUSHER_APP_CLUSTER=mt1

# Configuración adicional de Soketi
SOKETI_DEBUG=true
SOKETI_DEFAULT_APP_ID=app-id
SOKETI_DEFAULT_APP_KEY=app-key
SOKETI_DEFAULT_APP_SECRET=app-secret
```

### 5. Configurar Base de Datos

```bash
# Ejecutar migraciones
php artisan migrate

# Crear tablas para jobs si no existen
php artisan queue:table
php artisan migrate

# Crear tablas para notificaciones
php artisan notifications:table
php artisan migrate
```

### 6. Instalar y Configurar Soketi (WebSocket Server)

```bash
# Instalar Soketi globalmente
npm install -g @soketi/soketi

# O instalar localmente en el proyecto
npm install @soketi/soketi
```

## 🚀 Levantar el Proyecto Completo

### Paso 1: Iniciar el Socket Server (Soketi)
```powershell
# En PowerShell, ejecutar el script
.\start-soketi-env.ps1
```

**Contenido del script `start-soketi-env.ps1`:**
```powershell
# Script para iniciar Soketi con configuración del proyecto
$env:DEBUG = "1"
$env:DEFAULT_APP_ID = "app-id"
$env:DEFAULT_APP_KEY = "app-key"
$env:DEFAULT_APP_SECRET = "app-secret"
$env:DEFAULT_APP_MAX_CONNECTIONS = "1000"
$env:DEFAULT_APP_ENABLE_CLIENT_MESSAGES = "true"
$env:SOKETI_PORT = "6001"
$env:SOKETI_HOST = "127.0.0.1"

# Iniciar Soketi
soketi start
```

### Paso 2: Iniciar el Procesador de Jobs/Colas
```bash
# En una nueva terminal
php artisan queue:work --queue=notifications,import,default

# O para desarrollo con reinicio automático
php artisan queue:listen
```

### Paso 3: Iniciar el Servidor PHP
```bash
# En otra terminal
php artisan serve
```

### Paso 4: Compilar Assets (si es necesario)
```bash
# En otra terminal
npm run dev

# O para observar cambios automáticamente
npm run watch
```

## 📊 Sistema de Jobs para Importación y Notificaciones

### Jobs Disponibles

1. **ImportProveedoresJob**
   - Procesa importación masiva de proveedores desde CSV
   - Ubicación: `app/Jobs/ImportProveedoresJob.php`
   
2. **ProcessNotificationJob**
   - Envía notificaciones en tiempo real a través de WebSockets
   - Ubicación: `app/Jobs/ProcessNotificationJob.php`

### Ejecutar Jobs Manualmente

```bash
# Procesar un job específico
php artisan queue:work --queue=import

# Procesar jobs de notificaciones
php artisan queue:work --queue=notifications

# Ver jobs pendientes
php artisan queue:failed
```

### Monitorear Jobs

```bash
# Ver estadísticas de la cola
php artisan queue:monitor notifications,import,default

# Reintentar jobs fallidos
php artisan queue:retry all
```

## 🔄 Flujo de Trabajo Completo

### Desarrollo Local - Orden de Inicio:

1. **Terminal 1 - Socket Server (Soketi):**
   ```powershell
   .\start-soketi-env.ps1
   ```
   Salida esperada:
   ```
   Starting Soketi server...
   Soketi is listening on port 6001
   ```

2. **Terminal 2 - Procesador de Jobs:**
   ```bash
   php artisan queue:work --queue=notifications,import,default --tries=3
   ```

3. **Terminal 3 - Servidor PHP:**
   ```bash
   php artisan serve --port=8000
   ```

4. **Terminal 4 - Compilación Frontend (opcional):**
   ```bash
   npm run dev
   ```

## 🐛 Resolución de Problemas Comunes

### Problemas con Jobs

1. **Jobs no se procesan:**
   ```bash
   # Verificar configuración de colas
   php artisan config:clear
   php artisan cache:clear
   
   # Reiniciar worker
   php artisan queue:restart
   ```

2. **Jobs fallidos:**
   ```bash
   # Ver jobs fallidos
   php artisan queue:failed
   
   # Reintentar un job específico
   php artisan queue:retry [job-id]
   ```

### Problemas con Soketi/WebSockets

1. **Conexión rechazada:**
   - Verificar que Soketi esté corriendo
   - Verificar puerto 6001 no esté ocupado
   - Revisar configuración en `.env`

2. **No llegan notificaciones:**
   - Verificar que el queue worker esté activo
   - Revisar logs: `tail -f storage/logs/laravel.log`

### Errores de Compilación TypeScript

```bash
# Limpiar y reinstalar
rm -rf node_modules package-lock.json
npm cache clean --force
npm install
npm run build
```

## 📝 Scripts Disponibles

### PHP/Laravel
- `php artisan serve` - Servidor de desarrollo
- `php artisan queue:work` - Procesar jobs
- `php artisan queue:listen` - Procesar jobs con auto-reload
- `php artisan migrate` - Ejecutar migraciones
- `php artisan tinker` - Consola interactiva

### Node.js/NPM
- `npm run dev` - Modo desarrollo
- `npm run build` - Compilar para producción
- `npm run watch` - Observar cambios
- `npm run lint` - Verificar código

### PowerShell
- `.\start-soketi-env.ps1` - Iniciar Soketi con configuración

## 🌐 Endpoints Principales

- **API:** `http://localhost:8000/api`
- **WebSocket:** `ws://localhost:6001`
- **Documentación API:** `http://localhost:8000/api/documentation`

## 📋 Checklist de Verificación

- [ ] PHP y Composer instalados
- [ ] Node.js y NPM instalados
- [ ] Base de datos configurada y migraciones ejecutadas
- [ ] Archivo `.env` configurado correctamente
- [ ] Soketi instalado (`npm install -g @soketi/soketi`)
- [ ] Socket server corriendo (puerto 6001)
- [ ] Queue worker activope
- [ ] Servidor PHP activo (puerto 8000)

## 💡 Tips para Desarrollo

1. **Monitorear logs en tiempo real:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **Limpiar caché cuando hay cambios de configuración:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan view:clear
   ```

3. **Test de WebSocket:**
   - Usar herramientas como Postman o wscat
   - Verificar en Chrome DevTools → Network → WS

4. **Supervisar Jobs:**
   - Usar Laravel Horizon (si está instalado)
   - O revisar tabla `jobs` en la base de datos

## 🚨 Importante

- **Siempre** iniciar Soketi antes que el queue worker
- **Siempre** ejecutar `npm run build` antes de deploy a producción
- **Nunca** commitear el archivo `.env` al repositorio
- **Verificar** que todos los servicios estén activos antes de probar

## 📞 Soporte

Para problemas o consultas sobre el proyecto, contactar al equipo de desarrollo.

---

**Última actualización:** Agosto 2025
**Versión:** 1.0.0
**Entorno:** Windows PowerShell
