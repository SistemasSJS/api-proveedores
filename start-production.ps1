# Script para iniciar servicios de producción
Write-Host "===============================================" -ForegroundColor Cyan
Write-Host "   🏭 Iniciando servicios de PRODUCCIÓN" -ForegroundColor Green
Write-Host "===============================================" -ForegroundColor Cyan

# Optimizar para producción
Write-Host "⚙️  Optimizando aplicación..." -ForegroundColor Yellow
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer install --optimize-autoloader --no-dev

Write-Host ""
Write-Host "🚀 Iniciando servicios..." -ForegroundColor Green

# Iniciar en ventanas separadas
Start-Process powershell -ArgumentList "-NoExit", "-Command", "& '.\start-soketi-env.ps1'"
Start-Sleep -Seconds 3

Start-Process powershell -ArgumentList "-NoExit", "-Command", "php artisan queue:work --queue=notifications,import,default --tries=3 --timeout=90"
Start-Sleep -Seconds 2

Start-Process powershell -ArgumentList "-NoExit", "-Command", "php artisan serve --host=******* --port=8094"

Write-Host ""
Write-Host "✅ Servicios iniciados:" -ForegroundColor Green
Write-Host "   📡 WebSocket: Puerto 80" -ForegroundColor White
Write-Host "   🔄 Queue Worker: Activo" -ForegroundColor White
Write-Host "   🌐 API Server: Puerto 8094" -ForegroundColor White
Write-Host ""
Write-Host "Presiona cualquier tecla para continuar..." -ForegroundColor Yellow
Read-Host