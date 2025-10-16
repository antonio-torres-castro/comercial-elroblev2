# Script de activación del entorno virtual
Write-Host "🚀 Activando entorno virtual..." -ForegroundColor Green

# Configurar variables de entorno
$env:COMPOSER_VENDOR_DIR = (Resolve-Path ".\vendor").Path
$env:PHP_INCLUDE_PATH = $env:COMPOSER_VENDOR_DIR

# Agregar binarios de vendor al PATH
if (Test-Path "$env:COMPOSER_VENDOR_DIR\bin") {
    $env:PATH = "$env:COMPOSER_VENDOR_DIR\bin;" + $env:PATH
}

Write-Host "✅ Entorno virtual activado" -ForegroundColor Green
Write-Host "📁 Vendor: $env:COMPOSER_VENDOR_DIR" -ForegroundColor Cyan
Write-Host "💡 Para desactivar: ejecuta 'deactivate' o cierra esta sesión" -ForegroundColor Yellow

# Función para desactivar
function deactivate {
    Remove-Item Env:COMPOSER_VENDOR_DIR -ErrorAction SilentlyContinue
    Remove-Item Env:PHP_INCLUDE_PATH -ErrorAction SilentlyContinue
    Write-Host "❌ Entorno virtual desactivado" -ForegroundColor Red
}
