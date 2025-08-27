@echo off
title Detener Laravel Queue Workers
echo =====================================
echo   Deteniendo Laravel Queue Workers
echo =====================================
echo.

echo Buscando procesos PHP relacionados con Laravel Queue Worker...
tasklist /FI "IMAGENAME eq php.exe" /FO TABLE | findstr php.exe

echo.
echo Deteniendo todos los procesos PHP...
taskkill /F /IM php.exe 2>nul

if %ERRORLEVEL% EQU 0 (
    echo Queue Workers detenidos exitosamente.
) else (
    echo No se encontraron procesos PHP ejecutandose.
)

echo.
pause
