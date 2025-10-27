# 🪟 Servicios de Windows para Laravel

## 📋 Requisitos

- Windows Server 2016+ o Windows 10+
- PHP instalado y en PATH
- NSSM (Non-Sucking Service Manager)

## 🔧 Instalación de NSSM

### Opción 1: Descarga Manual
```powershell
# 1. Descargar desde: https://nssm.cc/download
# 2. Extraer a C:\nssm
# 3. Agregar a PATH: C:\nssm\win64
```

### Opción 2: Con Chocolatey
```powershell
choco install nssm
```

## 🚀 Instalación de Servicios

### **1. Editar Rutas en los Scripts**

Antes de ejecutar, edita estos archivos y ajusta las rutas:

**`install-queue-service.ps1`**
```powershell
$phpPath = "C:\php\php.exe"  # ← Cambia esto
$artisanPath = "C:\repositorio\app\api-proveedores\artisan"  # ← Y esto
$workingDir = "C:\repositorio\app\api-proveedores"  # ← Y esto
```

**`install-reverb-service.ps1`**
```powershell
$phpPath = "C:\php\php.exe"  # ← Cambia esto
$artisanPath = "C:\repositorio\app\api-proveedores\artisan"  # ← Y esto
$workingDir = "C:\repositorio\app\api-proveedores"  # ← Y esto
```

### **2. Ejecutar Scripts como Administrador**

```powershell
# Abrir PowerShell como Administrador

# Instalar Queue Worker
.\install-queue-service.ps1

# Instalar Reverb
.\install-reverb-service.ps1
```

## 🎛️ Administración de Servicios

### **GUI de Windows (Services.msc)**

```powershell
# Abrir administrador de servicios
services.msc
```

Busca:
- `LaravelQueueWorker`
- `LaravelReverb`

### **PowerShell**

```powershell
# Ver estado
Get-Service LaravelQueueWorker
Get-Service LaravelReverb

# Iniciar
Start-Service LaravelQueueWorker
Start-Service LaravelReverb

# Detener
Stop-Service LaravelQueueWorker
Stop-Service LaravelReverb

# Reiniciar
Restart-Service LaravelQueueWorker
Restart-Service LaravelReverb
```

### **NSSM**

```powershell
# Estado
nssm status LaravelQueueWorker
nssm status LaravelReverb

# Iniciar
nssm start LaravelQueueWorker
nssm start LaravelReverb

# Detener
nssm stop LaravelQueueWorker
nssm stop LaravelReverb

# Reiniciar
nssm restart LaravelQueueWorker
nssm restart LaravelReverb

# Editar configuración (abre GUI)
nssm edit LaravelQueueWorker
nssm edit LaravelReverb

# Ver logs
nssm rotate LaravelQueueWorker  # Rotar logs
nssm rotate LaravelReverb
```

## 📝 Logs

### **Ubicación**

```
C:\repositorio\app\api-proveedores\storage\logs\
├── queue-worker-stdout.log  # Salida estándar del worker
├── queue-worker-stderr.log  # Errores del worker
├── reverb-stdout.log        # Salida estándar de Reverb
├── reverb-stderr.log        # Errores de Reverb
└── laravel.log              # Logs de Laravel
```

### **Ver logs en tiempo real**

```powershell
# Queue Worker
Get-Content storage\logs\queue-worker-stdout.log -Wait -Tail 50
Get-Content storage\logs\queue-worker-stderr.log -Wait -Tail 50

# Reverb
Get-Content storage\logs\reverb-stdout.log -Wait -Tail 50
Get-Content storage\logs\reverb-stderr.log -Wait -Tail 50

# Laravel
Get-Content storage\logs\laravel.log -Wait -Tail 50
```

## 🔄 Reinicio Automático

Ambos servicios están configurados para:
- ✅ Reiniciarse automáticamente si fallan
- ✅ Iniciarse automáticamente con Windows
- ✅ Esperar 5 segundos antes de reiniciar

## 🛠️ Troubleshooting

### **Servicio no inicia**

1. **Verificar logs**:
```powershell
Get-Content storage\logs\queue-worker-stderr.log -Tail 50
```

2. **Verificar permisos**:
```powershell
# El usuario del servicio necesita permisos de lectura/escritura en:
# - C:\repositorio\app\api-proveedores
# - C:\repositorio\app\api-proveedores\storage
```

3. **Verificar PHP**:
```powershell
# Debe funcionar desde CMD
C:\php\php.exe -v
```

4. **Verificar artisan**:
```powershell
cd C:\repositorio\app\api-proveedores
php artisan --version
```

### **Queue Worker no procesa jobs**

1. **Verificar que el servicio esté corriendo**:
```powershell
Get-Service LaravelQueueWorker
```

2. **Ver jobs pendientes**:
```powershell
php artisan queue:work --once
```

3. **Verificar conexión de BD**:
```powershell
php artisan tinker --execute="DB::table('jobs')->count()"
```

### **Reverb no acepta conexiones**

1. **Verificar puerto**:
```powershell
netstat -ano | findstr :8080
```

2. **Verificar firewall**:
```powershell
# Agregar regla de firewall
New-NetFirewallRule -DisplayName "Laravel Reverb" -Direction Inbound -Protocol TCP -LocalPort 8080 -Action Allow
```

3. **Probar conexión**:
```powershell
Test-NetConnection localhost -Port 8080
```

## 🗑️ Desinstalar Servicios

```powershell
# Detener servicios
nssm stop LaravelQueueWorker
nssm stop LaravelReverb

# Eliminar servicios
nssm remove LaravelQueueWorker confirm
nssm remove LaravelReverb confirm
```

## 📊 Monitoreo

### **Task Manager**

1. Abrir Task Manager (`Ctrl+Shift+Esc`)
2. Ir a pestaña "Services"
3. Buscar `LaravelQueueWorker` y `LaravelReverb`

### **Event Viewer**

```powershell
eventvwr.msc
# Windows Logs → Application
# Buscar eventos de LaravelQueueWorker y LaravelReverb
```

## ✅ Checklist de Instalación

- [ ] NSSM instalado
- [ ] Rutas editadas en scripts
- [ ] `install-queue-service.ps1` ejecutado como Admin
- [ ] `install-reverb-service.ps1` ejecutado como Admin
- [ ] Servicios corriendo en `services.msc`
- [ ] Logs generándose correctamente
- [ ] Queue procesando jobs
- [ ] Reverb aceptando conexiones WebSocket

## 🎯 Configuración Recomendada para Producción

```powershell
# 1. Configurar usuario dedicado para los servicios
# 2. Configurar límites de memoria
# 3. Configurar rotación de logs
# 4. Configurar alertas de monitoreo
# 5. Configurar backup automático de BD
```

---

**Última actualización:** 2025-10-24
