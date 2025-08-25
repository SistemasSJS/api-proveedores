# Script para iniciar Soketi usando variables de entorno
# Esto evita problemas de configuración con el archivo JSON

Write-Host "===============================================" -ForegroundColor Cyan
Write-Host "   🚀 Iniciando servidor Soketi WebSocket" -ForegroundColor Green
Write-Host "===============================================" -ForegroundColor Cyan
Write-Host ""

# Detener instancias previas de Soketi
Write-Host "🔄 Deteniendo instancias previas..." -ForegroundColor Yellow
Get-Process | Where-Object {$_.ProcessName -eq "node"} | Stop-Process -Force -ErrorAction SilentlyContinue
Start-Sleep -Seconds 1

# Configurar variables de entorno
Write-Host "⚙️  Configurando variables de entorno..." -ForegroundColor Yellow
$env:SOKETI_DEBUG = '1'
$env:SOKETI_DEFAULT_APP_ID = '2041542'
$env:SOKETI_DEFAULT_APP_KEY = '054109b74e56a9b3893f'
$env:SOKETI_DEFAULT_APP_SECRET = '56903bfe04ef9c08b755'
$env:SOKETI_DEFAULT_APP_ENABLE_CLIENT_MESSAGES = 'true'
$env:SOKETI_PORT = '80'
$env:SOKETI_HOST = '127.0.0.1'

Write-Host ""
Write-Host "📋 Credenciales configuradas:" -ForegroundColor Cyan
Write-Host "   App ID:     2041542" -ForegroundColor White
Write-Host "   App Key:    054109b74e56a9b3893f" -ForegroundColor White
Write-Host "   App Secret: 56903bfe04ef9c08b755" -ForegroundColor White
Write-Host "   Puerto:     80" -ForegroundColor White
Write-Host "   Host:       127.0.0.1" -ForegroundColor White
Write-Host ""
Write-Host "===============================================" -ForegroundColor Cyan
Write-Host "📡 Iniciando Soketi..." -ForegroundColor Green
Write-Host "   Presiona Ctrl+C para detener el servidor" -ForegroundColor Yellow
Write-Host "===============================================" -ForegroundColor Cyan
Write-Host ""

# Iniciar Soketi
soketi start
