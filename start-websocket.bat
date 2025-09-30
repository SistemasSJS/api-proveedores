@echo off
echo ===============================================
echo Iniciando Servidor WebSocket Soketi
echo ===============================================
echo.
echo Configuracion:
echo - Puerto: 80
echo - Host: localhost
echo - App ID: app-id
echo - App Key: app-key
echo - Dashboard: http://localhost:80/metrics
echo.
echo Presiona Ctrl+C para detener el servidor
echo ===============================================
echo.
cd /d C:\repositorio\sjsconstrucciones\app\api-proveedores
npx soketi start --config=soketi.json
pause
