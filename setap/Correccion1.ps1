# EJECUTAR TODO DESDE: C:\Users\aseso\source\repos\comercial-elroblev2\setap\

Write-Host "=== CORRIGIENDO AUTOLOAD DEL PROYECTO ===" -ForegroundColor Magenta

# 2. Regenerar autoload
cd venv
composer dump-autoload --optimize
Write-Host "Autoload regenerado" -ForegroundColor Green

# 3. Verificar
php -r "require 'vendor/autoload.php'; echo class_exists('App\\Config\\AppConfig') ? 'App\\Config\\AppConfig: OK\n' : 'App\\Config\\AppConfig: ERROR\n';"

# 4. Volver al directorio raíz
cd ..

Write-Host "CORRECCION COMPLETADA" -ForegroundColor Green
Write-Host "Ahora prueba el servidor: php -S localhost:8082 -t public/" -ForegroundColor Cyan