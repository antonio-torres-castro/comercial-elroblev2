# CONFIGURACIÓN VS CODE PARA AMBIENTE VIRTUAL
Write-Host "=== CONFIGURANDO VS CODE ===" -ForegroundColor Green

# Verificar que estamos en el lugar correcto
if (-not (Test-Path "venv")) {
    Write-Host "ERROR: No se encuentra venv. Ejecuta desde setap/" -ForegroundColor Red
    exit 1
}

# Crear directorio .vscode
if (-not (Test-Path ".vscode")) {
    New-Item -ItemType Directory -Path ".vscode" -Force
    Write-Host "Directorio .vscode creado" -ForegroundColor Green
}

# Crear extensions.json
$extensionsContent = @'
{
    "recommendations": [
        "xdebug.php-debug",
        "bmewburn.vscode-intelephense-client"
    ]
}
'@
$extensionsContent | Out-File -FilePath ".vscode\extensions.json" -Encoding UTF8 -Force

# Crear settings.json
$settingsContent = @'
{
    "php.validate.enable": true,
    "files.associations": {
        "*.php": "php"
    },
    "intelephense.environment.includePaths": [
        "./venv/vendor"
    ]
}
'@
$settingsContent | Out-File -FilePath ".vscode\settings.json" -Encoding UTF8 -Force

# Crear launch.json
$launchContent = @'
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Listen for Xdebug",
            "type": "php",
            "request": "launch",
            "port": 9003,
            "log": true
        }
    ]
}
'@
$launchContent | Out-File -FilePath ".vscode\launch.json" -Encoding UTF8 -Force

Write-Host "=== CONFIGURACION COMPLETADA ===" -ForegroundColor Green
Write-Host ""
Write-Host "Archivos creados:" -ForegroundColor Cyan
Write-Host "- .vscode\extensions.json" -ForegroundColor White
Write-Host "- .vscode\settings.json" -ForegroundColor White  
Write-Host "- .vscode\launch.json" -ForegroundColor White
Write-Host ""
Write-Host "SIGUIENTE: code ." -ForegroundColor Green