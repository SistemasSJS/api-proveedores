@echo off
title Reiniciar Laravel Queue Worker
echo =====================================
echo   Reiniciando Laravel Queue Worker
echo =====================================
echo.

echo Paso 1: Deteniendo workers existentes...
call "%~dp0stop-queue-worker.bat"

echo.
echo Esperando 3 segundos antes de reiniciar...
timeout /t 3 /nobreak >nul

echo.
echo Paso 2: Iniciando nuevo worker...
call "%~dp0start-queue-worker.bat"
