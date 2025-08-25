@echo off
echo ================================================
echo    Iniciando servidor Soketi WebSocket
echo ================================================
echo.
echo Credenciales configuradas:
echo   - App ID: 2041542
echo   - App Key: 054109b74e56a9b3893f
echo   - Cluster: mt1
echo   - Puerto: 80
echo.
echo Presiona Ctrl+C para detener el servidor
echo ================================================
echo.

soketi start --config=soketi-dev.json
