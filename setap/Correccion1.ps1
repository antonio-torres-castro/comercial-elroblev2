# EJECUTAR TODO DESDE: C:\Users\aseso\source\repos\comercial-elroblev2\setap\

Write-Host "=== CORRIGIENDO AUTOLOAD DEL PROYECTO ===" -ForegroundColor Magenta
Write-Host "Ambiente: Estandar PHP (sin venv)" -ForegroundColor Cyan
Write-Host ""

# Regenerar autoload en la raiz de setap/
Write-Host "Regenerando autoload..." -ForegroundColor Yellow
php composer.phar dump-autoload --optimize
Write-Host "Autoload regenerado" -ForegroundColor Green
Write-Host ""

# Verificar que las clases se cargan correctamente
Write-Host "Verificando clases..." -ForegroundColor Yellow
php -r "require 'vendor/autoload.php'; echo class_exists('App\\Config\\AppConfig') ? 'App\\Config\\AppConfig: OK\n' : 'App\\Config\\AppConfig: ERROR\n';"
Write-Host ""

Write-Host "CORRECCION COMPLETADA" -ForegroundColor Green
Write-Host ""
Write-Host "ACCESO A LA APLICACION:" -ForegroundColor Cyan
Write-Host "1. Asegurate que Apache este ejecutandose en puerto 8080" -ForegroundColor White
Write-Host "2. Accede a: http://localhost:8080/setap" -ForegroundColor Yellow
Write-Host "   (NO uses php -S, la aplicacion se sirve via Apache)" -ForegroundColor White
Write-Host ""
Write-Host "Para pruebas locales directas (solo desarrollo):" -ForegroundColor Cyan
Write-Host "php -S localhost:8080 -t public/" -ForegroundColor Yellow