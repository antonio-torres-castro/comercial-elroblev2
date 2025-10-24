<?php
/**
 * Configuración de Base de Datos
 * Carga las constantes necesarias para la aplicación
 */

// Cargar variables de entorno desde el archivo _env
function loadEnv($path) {
    if (!file_exists($path)) {
        return false;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignorar comentarios
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parsear línea KEY=VALUE
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        
        if (!empty($key) && !empty($value)) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
    return true;
}

// Cargar archivo _env si existe
$envPath = __DIR__ . '/../_env';
if (file_exists($envPath)) {
    loadEnv($envPath);
}

// Definir constantes de base de datos
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_PORT', $_ENV['DB_PORT'] ?? '3306');
define('DB_NAME', $_ENV['DB_DATABASE'] ?? $_ENV['DB_NAME'] ?? 'comerci3_bdsetap');
define('DB_USER', $_ENV['DB_USERNAME'] ?? $_ENV['DB_USER'] ?? 'comerci3_admin');
define('DB_PASS', $_ENV['DB_PASSWORD'] ?? $_ENV['DB_PASS'] ?? 'Micomercial.1');

// Definir constantes de aplicación
define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');
define('APP_DEBUG', filter_var($_ENV['APP_DEBUG'] ?? 'false', FILTER_VALIDATE_BOOLEAN));
define('APP_NAME', $_ENV['APP_NAME'] ?? 'SETAP');
define('APP_URL', $_ENV['APP_URL'] ?? 'https://comercial-elroble.cl/setap');

// Configuración adicional
define('PASSWORD_MIN_LENGTH', $_ENV['PASSWORD_MIN_LENGTH'] ?? 8);
define('SESSION_LIFETIME', $_ENV['SESSION_LIFETIME'] ?? 3600);
define('TIMEZONE', $_ENV['TIMEZONE'] ?? 'America/Santiago');
define('LOCALE', $_ENV['LOCALE'] ?? 'es_CL');
