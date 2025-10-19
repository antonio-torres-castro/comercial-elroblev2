# Script de activacion del entorno virtual
Write-Host "Activando entorno virtual..." -ForegroundColor Green

# Configurar variables de entorno
$env:COMPOSER_VENDOR_DIR = (Resolve-Path ".\vendor").Path
$env:PHP_INCLUDE_PATH = $env:COMPOSER_VENDOR_DIR

# Guardar PATH original
$env:ORIGINAL_PATH = $env:PATH

# Agregar binarios de vendor al PATH
if (Test-Path "$env:COMPOSER_VENDOR_DIR\bin") {
    $env:PATH = "$env:COMPOSER_VENDOR_DIR\bin;" + $env:PATH
}

Write-Host "Entorno virtual activado" -ForegroundColor Green
Write-Host "Vendor: $env:COMPOSER_VENDOR_DIR" -ForegroundColor Cyan
Write-Host "Para desactivar: ejecutar 'deactivate' o cerrar esta sesion" -ForegroundColor Yellow

# Registrar funcion global para desactivar
Set-Item -Path Function:global:deactivate -Value {
    Remove-Item Env:COMPOSER_VENDOR_DIR -ErrorAction SilentlyContinue
    Remove-Item Env:PHP_INCLUDE_PATH -ErrorAction SilentlyContinue
    if ($env:ORIGINAL_PATH) {
        $env:PATH = $env:ORIGINAL_PATH
        Remove-Item Env:ORIGINAL_PATH -ErrorAction SilentlyContinue
    }
    Write-Host "Entorno virtual desactivado" -ForegroundColor Red
}
