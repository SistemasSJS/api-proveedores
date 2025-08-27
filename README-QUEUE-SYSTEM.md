# 🚀 Sistema de Colas Laravel - API Proveedores

Este proyecto incluye un sistema completo de colas (Jobs) configurado para Windows Server que maneja automáticamente las importaciones CSV de productos.

## 📁 Estructura de Archivos

```
api-proveedores/
├── queue-worker-supervisor.ps1      # Script supervisor principal (PowerShell)
├── scripts/
│   ├── start-queue-worker.bat       # Iniciar worker manualmente
│   ├── stop-queue-worker.bat        # Detener todos los workers
│   └── restart-queue-worker.bat     # Reiniciar worker
├── docs/
│   └── configuracion-task-scheduler.md  # Guía Task Scheduler
├── app/
│   ├── Jobs/
│   │   └── CSVImportJob.php         # Job principal de importación
│   ├── Console/Commands/
│   │   └── QueueMonitor.php         # Comando de monitoreo
│   └── Listeners/
│       └── QueueFailedListener.php  # Alertas de jobs fallidos
├── config/
│   ├── queue.php                    # Configuración de colas
│   └── logging.php                  # Configuración de logs
└── storage/logs/
    ├── queue/                       # Logs específicos de colas
    ├── imports/                     # Logs de importaciones
    └── queue-supervisor.log         # Log del supervisor
```

## 🎯 Características Principales

### ✅ **Rutas Relativas**
- ✅ Detección automática del directorio del proyecto
- ✅ Scripts portátiles entre servidores
- ✅ No requiere hardcodeo de rutas absolutas

### ✅ **Supervisor Inteligente**
- ✅ Reinicio automático en caso de fallos
- ✅ Validación del entorno antes de ejecutar
- ✅ Logs detallados con timestamps
- ✅ Estadísticas de cola en tiempo real

### ✅ **Sistema de Monitoreo**
- ✅ Comando `php artisan queue:monitor`
- ✅ Logs organizados por categoría
- ✅ Alertas por email en caso de fallos

## 🚀 Cómo Usar

### **Opción 1: Ejecutar el Supervisor (Recomendado)**

Desde PowerShell en el directorio del proyecto:

```powershell
# El script detectará automáticamente la ruta del proyecto
.\queue-worker-supervisor.ps1
```

O desde cualquier ubicación especificando la ruta:

```powershell
# Especificar ruta manualmente si es necesario
.\queue-worker-supervisor.ps1 -ProjectPath "C:\mi\proyecto\laravel"
```

### **Opción 2: Scripts Batch Manuales**

```batch
# Iniciar worker
scripts\start-queue-worker.bat

# Detener worker  
scripts\stop-queue-worker.bat

# Reiniciar worker
scripts\restart-queue-worker.bat
```

### **Opción 3: Worker Simple de Laravel**

```bash
# Worker básico (se detiene cuando no hay jobs)
php artisan queue:work database --stop-when-empty

# Worker persistente
php artisan queue:work database --sleep=3 --tries=3
```

## 📊 Monitoreo

### **Comando de Monitoreo**

```bash
# Vista básica
php artisan queue:monitor

# Vista detallada
php artisan queue:monitor --detailed

# Auto-refresh cada 5 segundos
php artisan queue:monitor --refresh=5

# Salida JSON
php artisan queue:monitor --json
```

### **Verificar Estado Manualmente**

```powershell
# Ver procesos PHP activos
Get-Process php

# Ver logs en tiempo real
Get-Content -Path "storage\logs\queue-supervisor.log" -Wait -Tail 20
```

## ⚙️ Configuración

### **Variables de Entorno (.env)**

```env
# Configuración de colas
QUEUE_CONNECTION=database
QUEUE_DRIVER=database

# Alertas de fallos (opcional)
QUEUE_ALERT_ON_FAILURE=true
QUEUE_ALERT_EMAIL=admin@construcc.com.mx
QUEUE_INTERNAL_NOTIFICATION=false
```

### **Parámetros del Supervisor**

El supervisor acepta los siguientes parámetros:

```powershell
.\queue-worker-supervisor.ps1 `
  -ProjectPath "C:\ruta\proyecto" `  # Auto-detecta si se omite
  -MaxRetries 5 `                    # Máximo reintentos antes de parar
  -RestartDelay 10 `                 # Segundos entre reintentos
  -MaxTime 3600 `                    # Tiempo máximo por worker (segundos)
  -Memory 512 `                      # Límite de memoria (MB)
  -Sleep 3 `                         # Pausa entre jobs (segundos)
  -Tries 3                           # Intentos por job fallido
```

## 🔧 Inicio Automático con Windows

### **Para configurar inicio automático:**

1. Sigue la guía detallada: [`docs/configuracion-task-scheduler.md`](docs/configuracion-task-scheduler.md)

2. **Configuración rápida en Task Scheduler:**
   - **Programa**: `powershell.exe`
   - **Argumentos**: `-ExecutionPolicy Bypass -WindowStyle Hidden -File ".\queue-worker-supervisor.ps1"`
   - **Iniciar en**: `[RUTA_COMPLETA_DE_TU_PROYECTO]`

## 📧 Sistema de Alertas

### **Email Automático en Fallos**
- Se envían emails cuando fallan jobs
- Incluye detalles del error y acciones recomendadas
- Configurable en `.env` con `QUEUE_ALERT_EMAIL`

### **Logs Detallados**
- `storage/logs/queue/` - Logs técnicos del sistema de colas
- `storage/logs/imports/` - Logs de negocio de importaciones  
- `storage/logs/queue-supervisor.log` - Log del supervisor

## 🛠️ Troubleshooting

### **El supervisor no inicia**
```bash
# Verificar permisos de PowerShell
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope LocalMachine
```

### **No se detecta el proyecto**
```powershell
# Especificar ruta manualmente
.\queue-worker-supervisor.ps1 -ProjectPath "C:\ruta\completa\proyecto"
```

### **PHP no está en PATH**
```powershell
# Agregar PHP al PATH del sistema o especificar ruta completa
# En el supervisor, buscar la línea: Start-Process -FilePath "php"
# Cambiar por: Start-Process -FilePath "C:\path\to\php\php.exe"
```

### **Problemas de base de datos**
```bash
# Verificar conexión
php artisan tinker
DB::connection()->getPdo();

# Limpiar caché de configuración
php artisan config:clear
php artisan config:cache
```

## 🎯 Jobs Procesados

El sistema está configurado para procesar automáticamente:

- ✅ **CSVImportJob** - Importaciones masivas de productos CSV
- ✅ **Notificaciones** - Envío de emails y notificaciones push
- ✅ **Jobs personalizados** - Cualquier job que agregues al proyecto

## 🔄 Flujo de Trabajo

1. **Usuario sube CSV** → Se crea un `ImportAudit`
2. **Sistema despacha** `CSVImportJob` → Se añade a la cola `database`  
3. **Worker procesa** → Job se ejecuta en segundo plano
4. **Supervisor monitorea** → Reinicia worker si falla
5. **Alertas automáticas** → Emails en caso de errores
6. **Logs detallados** → Todo queda registrado

---

## 📞 Soporte

Si tienes problemas:

1. **Revisar logs**: `storage/logs/queue-supervisor.log`
2. **Monitorear estado**: `php artisan queue:monitor`
3. **Verificar procesos**: `Get-Process php`
4. **Reiniciar supervisor**: `scripts\restart-queue-worker.bat`

El sistema está diseñado para ser **robusto, portable y fácil de mantener** ✨
