@echo off
title LifeLine HMS - Server Launcher
:: Run the PowerShell orchestrator script with execution policy bypassed
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0start-server.ps1"

