# VERIFICACION DE VS CODE Y EXTENSIONES PHP
Write-Host "=== DIAGNOSTICO VS CODE PARA PHP DEBUG ===" -ForegroundColor Green

# 1. Verificar VS Code
Write-Host "1. VERIFICANDO VS CODE..." -ForegroundColor Yellow
try {
    $vscodeVersion = code --version 2>$null
    if ($vscodeVersion) {
        Write-Host "VS Code esta instalado" -ForegroundColor Green
        Write-Host "Versión: $($vscodeVersion[0])" -ForegroundColor Cyan
    } else {
        Write-Host "VS Code no encontrado en PATH" -ForegroundColor Red
    }
} catch {
    Write-Host " Error al verificar VS Code" -ForegroundColor Red
}

# 2. Verificar extensiones PHP
Write-Host "2. VERIFICANDO EXTENSIONES PHP..." -ForegroundColor Yellow
try {
    $extensions = code --list-extensions 2>$null
    $phpExtensions = $extensions | Where-Object { $_ -like "*php*" -or $_ -like "*debug*" }
    
    if ($phpExtensions) {
        Write-Host " Extensiones PHP encontradas:" -ForegroundColor Green
        foreach ($ext in $phpExtensions) {
            Write-Host "    $ext" -ForegroundColor Cyan
        }
    } else {
        Write-Host " No se encontraron extensiones PHP" -ForegroundColor Red
    }
    
    # Verificar extension especifica de debug
    $debugExt = $extensions | Where-Object { $_ -eq "xdebug.php-debug" }
    if ($debugExt) {
        Write-Host " Extension PHP Debug (xdebug.php-debug) instalada" -ForegroundColor Green
    } else {
        Write-Host " Extension PHP Debug NO encontrada" -ForegroundColor Red
        Write-Host " Instalar con: code --install-extension xdebug.php-debug" -ForegroundColor Yellow
    }
} catch {
    Write-Host " Error al verificar extensiones" -ForegroundColor Red
}

# 3. Verificar configuracion del workspace
Write-Host "`n3. VERIFICANDO CONFIGURACION WORKSPACE..." -ForegroundColor Yellow
if (Test-Path ".vscode\settings.json") {
    Write-Host " settings.json existe" -ForegroundColor Green
} else {
    Write-Host " settings.json no existe (opcional)" -ForegroundColor Yellow
}

if (Test-Path ".vscode\launch.json") {
    Write-Host " launch.json existe" -ForegroundColor Green
} else {
    Write-Host " launch.json no existe" -ForegroundColor Red
}

# 4. Comando para instalar extension
Write-Host "=== SOLUCION RAPIDA ===" -ForegroundColor Green
Write-Host "Si PHP Debug no est instalada, ejecuta:" -ForegroundColor White
Write-Host "code --install-extension xdebug.php-debug" -ForegroundColor Cyan

Write-Host "Luego reinicia VS Code y prueba de nuevo" -ForegroundColor White