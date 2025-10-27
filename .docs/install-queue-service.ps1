# Script para instalar Queue Worker como servicio de Windows
# Ejecutar como Administrador

$serviceName = "LaravelQueueWorker"
$phpPath = "C:\php\php.exe"  # Ajusta la ruta de PHP
$artisanPath = "C:\repositorio\app\api-proveedores\artisan"
$workingDir = "C:\repositorio\app\api-proveedores"

Write-Host "🚀 Instalando servicio: $serviceName" -ForegroundColor Cyan

# Verificar si NSSM está instalado
if (!(Get-Command nssm -ErrorAction SilentlyContinue)) {
    Write-Host "❌ NSSM no está instalado" -ForegroundColor Red
    Write-Host "Instala NSSM desde: https://nssm.cc/download" -ForegroundColor Yellow
    exit 1
}

# Detener y eliminar servicio si existe
Write-Host "🔍 Verificando si el servicio ya existe..." -ForegroundColor Yellow
$existingService = Get-Service -Name $serviceName -ErrorAction SilentlyContinue
if ($existingService) {
    Write-Host "⚠️  Servicio existente encontrado, eliminando..." -ForegroundColor Yellow
    nssm stop $serviceName
    nssm remove $serviceName confirm
    Start-Sleep -Seconds 2
}

# Instalar servicio
Write-Host "📦 Instalando servicio..." -ForegroundColor Cyan
nssm install $serviceName $phpPath $artisanPath queue:work --queue=imports,notifications,default --verbose --tries=3 --timeout=1800

# Configurar directorio de trabajo
Write-Host "📁 Configurando directorio de trabajo..." -ForegroundColor Cyan
nssm set $serviceName AppDirectory $workingDir

# Configurar reinicio automático
Write-Host "🔄 Configurando reinicio automático..." -ForegroundColor Cyan
nssm set $serviceName AppExit Default Restart
nssm set $serviceName AppRestartDelay 5000  # 5 segundos

# Configurar logs
Write-Host "📝 Configurando logs..." -ForegroundColor Cyan
nssm set $serviceName AppStdout "$workingDir\storage\logs\queue-worker-stdout.log"
nssm set $serviceName AppStderr "$workingDir\storage\logs\queue-worker-stderr.log"

# Configurar descripción
Write-Host "📋 Configurando descripción..." -ForegroundColor Cyan
nssm set $serviceName Description "Laravel Queue Worker - Procesa colas de notificaciones e imports"

# Iniciar servicio
Write-Host "▶️  Iniciando servicio..." -ForegroundColor Cyan
nssm start $serviceName

# Verificar estado
Start-Sleep -Seconds 3
$service = Get-Service -Name $serviceName
if ($service.Status -eq "Running") {
    Write-Host "✅ Servicio instalado e iniciado correctamente" -ForegroundColor Green
    Write-Host ""
    Write-Host "📊 Estado del servicio:" -ForegroundColor Cyan
    Get-Service -Name $serviceName | Format-Table -AutoSize
    Write-Host ""
    Write-Host "📝 Logs en:" -ForegroundColor Yellow
    Write-Host "   - $workingDir\storage\logs\queue-worker-stdout.log"
    Write-Host "   - $workingDir\storage\logs\queue-worker-stderr.log"
} else {
    Write-Host "❌ Error al iniciar el servicio" -ForegroundColor Red
    nssm status $serviceName
}

Write-Host ""
Write-Host "🔧 Comandos útiles:" -ForegroundColor Cyan
Write-Host "   nssm start $serviceName      # Iniciar"
Write-Host "   nssm stop $serviceName       # Detener"
Write-Host "   nssm restart $serviceName    # Reiniciar"
Write-Host "   nssm status $serviceName     # Ver estado"
Write-Host "   nssm edit $serviceName       # Editar configuración (GUI)"
Write-Host "   nssm remove $serviceName     # Eliminar servicio"
