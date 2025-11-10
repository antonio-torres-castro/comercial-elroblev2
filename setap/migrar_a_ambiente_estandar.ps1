# ============================================================================
# MIGRACION DE AMBIENTE VIRTUAL A AMBIENTE ESTANDAR PHP
# Script: migrar_a_ambiente_estandar.ps1
# 
# Fecha: 2025-10-23
# ============================================================================

Write-Host "" 
Write-Host "=========================================================" -ForegroundColor Cyan
Write-Host "  MIGRACION: VENV -> AMBIENTE ESTANDAR PHP" -ForegroundColor Cyan
Write-Host "=========================================================" -ForegroundColor Cyan
Write-Host ""

# Verificar que estamos en el directorio correcto
if (-not (Test-Path "venv")) {
    Write-Host "ERROR: No se encuentra el directorio venv/" -ForegroundColor Red
    Write-Host "Ejecuta este script desde: comercial-elroblev2/setap/" -ForegroundColor Yellow
    exit 1
}

if (-not (Test-Path "venv\vendor")) {
    Write-Host "ERROR: No se encuentra venv/vendor/" -ForegroundColor Red
    exit 1
}

Write-Host "[1/7] Verificando estructura actual..." -ForegroundColor Yellow
Write-Host "  - venv/vendor/ existe: " -NoNewline
Write-Host "OK" -ForegroundColor Green

# PASO 1: Backup de seguridad
Write-Host ""
Write-Host "[2/7] Creando backup de seguridad..." -ForegroundColor Yellow
$backupDir = "backup_venv_" + (Get-Date -Format "yyyyMMdd_HHmmss")
if (-not (Test-Path $backupDir)) {
    New-Item -ItemType Directory -Path $backupDir -Force | Out-Null
}

# Copiar archivos criticos antes de modificarlos
$archivosCriticos = @(
    "error_analyzer.php",
    "run_tests.php",
    "test_report.php",
    "detailed_errors.php",
    "Correccion1.ps1",
    "configurar_workspace_completo.ps1",
    "tests\Tools\ConstantsValidator.php"
)

foreach ($archivo in $archivosCriticos) {
    if (Test-Path $archivo) {
        $destino = Join-Path $backupDir (Split-Path $archivo -Leaf)
        Copy-Item $archivo $destino -Force
    }
}

Write-Host "  Backup creado en: $backupDir" -ForegroundColor Green

# PASO 2: Mover vendor/ al nivel raiz
Write-Host ""
Write-Host "[3/7] Moviendo vendor/ a la raiz de setap/..." -ForegroundColor Yellow

if (Test-Path "vendor") {
    Write-Host "  Eliminando vendor/ existente..." -ForegroundColor Yellow
    Remove-Item "vendor" -Recurse -Force
}

Write-Host "  Moviendo venv/vendor/ -> vendor/..." -ForegroundColor Yellow
Move-Item "venv\vendor" "vendor" -Force
Write-Host "  vendor/ movido correctamente" -ForegroundColor Green

# PASO 3: Mover composer.json y composer.lock si no existen en raiz
Write-Host ""
Write-Host "[4/7] Verificando archivos de Composer..." -ForegroundColor Yellow

if (Test-Path "venv\composer.json") {
    if (-not (Test-Path "composer.json")) {
        Copy-Item "venv\composer.json" "composer.json" -Force
        Write-Host "  composer.json copiado a raiz" -ForegroundColor Green
    }
    else {
        Write-Host "  composer.json ya existe en raiz" -ForegroundColor Cyan
    }
}

if (Test-Path "venv\composer.lock") {
    if (-not (Test-Path "composer.lock")) {
        Copy-Item "venv\composer.lock" "composer.lock" -Force
        Write-Host "  composer.lock copiado a raiz" -ForegroundColor Green
    }
    else {
        Write-Host "  composer.lock ya existe en raiz" -ForegroundColor Cyan
    }
}

# PASO 4: Actualizar referencias en archivos PHP
Write-Host ""
Write-Host "[5/7] Actualizando referencias en archivos PHP..." -ForegroundColor Yellow

$archivosPHP = @(
    "error_analyzer.php",
    "run_tests.php",
    "test_report.php",
    "detailed_errors.php",
    "tests\Tools\ConstantsValidator.php"
)

foreach ($archivo in $archivosPHP) {
    if (Test-Path $archivo) {
        $contenido = Get-Content $archivo -Raw -Encoding UTF8
        
        # Reemplazar referencias a venv/vendor
        $contenidoNuevo = $contenido -replace '\.[\\/]venv[\\/]vendor', './vendor'
        $contenidoNuevo = $contenidoNuevo -replace '[\\/]venv[\\/]vendor', '/vendor'
        $contenidoNuevo = $contenidoNuevo -replace 'venv[\\/]vendor', 'vendor'
        
        # Guardar cambios
        $contenidoNuevo | Out-File -FilePath $archivo -Encoding UTF8 -NoNewline
        Write-Host "  - $archivo actualizado" -ForegroundColor Green
    }
}

# PASO 5: Actualizar scripts PowerShell
Write-Host ""
Write-Host "[6/7] Actualizando scripts PowerShell..." -ForegroundColor Yellow

# Actualizar Correccion1.ps1
if (Test-Path "Correccion1.ps1") {
    $contenido = Get-Content "Correccion1.ps1" -Raw -Encoding UTF8
    $contenidoNuevo = $contenido -replace 'cd venv', '# Ambiente estandar - no requiere cd venv'
    $contenidoNuevo | Out-File -FilePath "Correccion1.ps1" -Encoding UTF8 -NoNewline
    Write-Host "  - Correccion1.ps1 actualizado" -ForegroundColor Green
}

# Actualizar configurar_workspace_completo.ps1
if (Test-Path "configurar_workspace_completo.ps1") {
    $contenido = Get-Content "configurar_workspace_completo.ps1" -Raw -Encoding UTF8
    $contenidoNuevo = $contenido -replace 'if \(-not \(Test-Path "venv"\)\) \{[^}]+\}', '# Ambiente estandar PHP'
    $contenidoNuevo = $contenidoNuevo -replace '"\.\/venv\/vendor"', '"./vendor"'
    $contenidoNuevo | Out-File -FilePath "configurar_workspace_completo.ps1" -Encoding UTF8 -NoNewline
    Write-Host "  - configurar_workspace_completo.ps1 actualizado" -ForegroundColor Green
}

# PASO 6: Eliminar directorio venv/
Write-Host ""
Write-Host "[7/7] Eliminando directorio venv/..." -ForegroundColor Yellow

if (Test-Path "venv") {
    Remove-Item "venv" -Recurse -Force
    Write-Host "  Directorio venv/ eliminado" -ForegroundColor Green
}

# RESUMEN FINAL
Write-Host ""
Write-Host "=========================================================" -ForegroundColor Green
Write-Host "  MIGRACION COMPLETADA EXITOSAMENTE" -ForegroundColor Green
Write-Host "=========================================================" -ForegroundColor Green
Write-Host ""
Write-Host "CAMBIOS REALIZADOS:" -ForegroundColor Cyan
Write-Host "  1. vendor/ movido a la raiz de setap/" -ForegroundColor White
Write-Host "  2. Referencias actualizadas en $($archivosPHP.Count) archivos PHP" -ForegroundColor White
Write-Host "  3. Scripts PowerShell actualizados" -ForegroundColor White
Write-Host "  4. Directorio venv/ eliminado" -ForegroundColor White
Write-Host "  5. Backup creado en: $backupDir" -ForegroundColor White
Write-Host ""
Write-Host "ESTRUCTURA ACTUAL:" -ForegroundColor Cyan
Write-Host "  setap/" -ForegroundColor White
Write-Host "  |-- vendor/          (Dependencias Composer)" -ForegroundColor White
Write-Host "  |-- composer.json" -ForegroundColor White
Write-Host "  |-- composer.lock" -ForegroundColor White
Write-Host "  |-- src/" -ForegroundColor White
Write-Host "  |-- public/" -ForegroundColor White
Write-Host "  |-- tests/" -ForegroundColor White
Write-Host ""
Write-Host "PROXIMOS PASOS:" -ForegroundColor Yellow
Write-Host "  1. Verificar que composer funciona:" -ForegroundColor White
Write-Host "     php composer.phar dump-autoload" -ForegroundColor Cyan
Write-Host ""
Write-Host "  2. Ejecutar tests para verificar:" -ForegroundColor White
Write-Host "     php run_tests.php" -ForegroundColor Cyan
Write-Host ""
Write-Host "  3. Iniciar servidor de desarrollo (solo pruebas):" -ForegroundColor White
Write-Host "     php -S localhost:8080 -t public/" -ForegroundColor Cyan
Write-Host ""
Write-Host "  4. Acceso normal via Apache:" -ForegroundColor White
Write-Host "     http://localhost:8080/setap" -ForegroundColor Cyan
Write-Host ""
Write-Host "=========================================================" -ForegroundColor Green
Write-Host ""
