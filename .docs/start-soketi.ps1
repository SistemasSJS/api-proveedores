# Script para iniciar el servidor Soketi WebSocket
Write-Host "🚀 Iniciando servidor Soketi WebSocket..." -ForegroundColor Green

# Verificar si soketi está instalado
$soketiInstalled = npm list -g @soketi/soketi 2>$null | Select-String "@soketi/soketi"

if (-not $soketiInstalled) {
    Write-Host "⚠️ Soketi no está instalado. Instalando..." -ForegroundColor Yellow
    npm install -g @soketi/soketi
}

# Detener cualquier instancia previa de Soketi
Write-Host "🔄 Deteniendo instancias previas de Soketi..." -ForegroundColor Yellow
Get-Process | Where-Object {$_.ProcessName -eq "node" -and $_.MainWindowTitle -like "*soketi*"} | Stop-Process -Force -ErrorAction SilentlyContinue

# Iniciar Soketi
Write-Host "📡 Iniciando Soketi en el puerto 80..." -ForegroundColor Cyan
Write-Host "Credenciales:" -ForegroundColor Cyan
Write-Host "  - App ID: 2041542" -ForegroundColor White
Write-Host "  - App Key: 054109b74e56a9b3893f" -ForegroundColor White
Write-Host "  - Cluster: mt1" -ForegroundColor White
Write-Host ""

# Iniciar Soketi en la misma ventana
soketi start --config=soketi-dev.json
