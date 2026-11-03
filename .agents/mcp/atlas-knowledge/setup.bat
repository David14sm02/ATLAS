@echo off
chcp 65001 > nul
title Instalador ATLAS MCP TecNM
cls

echo ========================================================================
echo   🚀 ATLAS TECNM - INSTALADOR RAPIDO PARA WINDOWS
echo ========================================================================
echo.

where python >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] No se detecto Python instalado en tu sistema.
    echo Por favor instala Python desde https://www.python.org/ o ejecuta AtlasMCP-Setup.exe
    echo.
    pause
    exit /b 1
)

echo [1/2] Instalando dependencias de Google Drive y lectura de documentos...
python -m pip install -r requirements.txt
echo.
echo [2/2] Lanzando asistente interactivo de integracion MCP...
python install.py
pause
