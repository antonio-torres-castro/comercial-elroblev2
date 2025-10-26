<?php

use App\Helpers\Logger;

// Archivo temporal para verificar configuración de logs
echo "Configuración de Error Log:\n";
echo "error_log: " . ini_get('error_log') . "\n";
echo "log_errors: " . ini_get('log_errors') . "\n";
echo "display_errors: " . ini_get('display_errors') . "\n";

// Escribir un mensaje de prueba
Logger::info("Test: Verificando ubicación de logs - " . date('Y-m-d H:i:s'));
echo "Mensaje de prueba enviado al log.\n";
