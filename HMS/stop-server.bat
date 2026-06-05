@echo off
title Stop LifeLine HMS Server
color 0C

echo.
echo  =====================================================
echo   Stopping LifeLine Hospital Management System Server
echo  =====================================================
echo.

echo  [*] Stopping PHP Development Server processes...
powershell -NoProfile -ExecutionPolicy Bypass -Command "Get-Process php, php-cgi -ErrorAction SilentlyContinue | Stop-Process -Force; Write-Host '  [+] All PHP dev processes stopped.' -ForegroundColor Green"

echo  [*] Stopping Standalone MySQL processes...
taskkill /f /im mysqld.exe >nul 2>&1
echo   [+] MySQL process stopped.

echo.
echo  =====================================================
echo   Clean up completed.
echo  =====================================================
echo.
pause
