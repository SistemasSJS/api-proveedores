@echo off
title Laravel Queue Worker - API Proveedores
echo =====================================
echo   Laravel Queue Worker - API Proveedores
echo =====================================
echo.
echo Iniciando Queue Worker...
echo Proyecto: %~dp0..
echo Cola: database
echo.

REM Cambiar al directorio del proyecto (un nivel arriba del directorio scripts)
cd /d "%~dp0.."

REM Verificar que el directorio existe
if not exist "artisan" (
    echo ERROR: No se encontro el archivo artisan en el directorio del proyecto.
    echo Verificar la ruta: %CD%
    pause
    exit /b 1
)

REM Iniciar el queue worker con configuración específica para producción
echo Ejecutando: php artisan queue:work database --sleep=3 --tries=3 --max-time=3600 --timeout=1800 --memory=512
echo.
php artisan queue:work database --sleep=3 --tries=3 --max-time=3600 --timeout=1800 --memory=512

echo.
echo Queue Worker detenido.
pause
