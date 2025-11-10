<?php
//#public_html\setap\src\App\bootstrap.php
declare(strict_types=1);

use App\Config\AppConfig;
use App\Constants\AppConstants;
use App\Helpers\Logger; // ajusta el namespace según donde guardes la clase


date_default_timezone_set('America/Santiago');
// Inicializa una sola vez (por ejemplo en tu bootstrap o config global)
//storage\logs\setap_auth_debug.log
Logger::init(__DIR__ . '/../../storage/logs/error.log');

// 2️⃣ Registrar el manejador global de errores
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    $message = "[$errno] $errstr en $errfile:$errline";
    Logger::error($message);
    return true; // Evita el logeo duplicado de PHP
});

// 3️⃣ Registrar el manejador global de excepciones no capturadas
set_exception_handler(function (Throwable $exception) {
    Logger::error("Excepción no capturada: " . $exception->getMessage() .
        " en " . $exception->getFile() . ":" . $exception->getLine());
});

// 4️⃣ (Opcional) Registrar cierre para capturar errores fatales
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null) {
        $message = "[SHUTDOWN] {$error['message']} en {$error['file']}:{$error['line']}";
        Logger::error($message);
    }
});
// Cargar configuración
AppConfig::load();

$scheme = AppConfig::get('app_scheme');
$host = AppConfig::get('app_host');
// $path = AppConfig::get('app_path'); // No se puede usar la estandar por que esta index en public, fuera de src\App
$appUrl = AppConfig::get('app_url');

$path = AppConstants::APP_FOLDER;

// Configurar base path
define('BASE_PATH', $path);

// No necesitamos requires manuales - Composer los maneja automáticamente
// La sesión debe iniciarse antes de cualquier output
if (session_status() === PHP_SESSION_NONE) {
    // Configuración básica de sesión por defecto
    $sessionLifetime = 3600;
    // $appUrl = 'http://localhost:8080/setap';

    // Intentar cargar configuración desde variables de entorno si están disponibles
    if (class_exists('Dotenv\Dotenv')) {
        $paths = __DIR__ . '/../../';
        $dotenv = Dotenv\Dotenv::createImmutable($paths);
        $dotenv->safeLoad();

        $sessionLifetime = $_ENV['SESSION_LIFETIME'] ?? $sessionLifetime;
    }

    Logger::debug("path:" . $path . " host:" . $host . " secure:" . $scheme);

    $sessionPath = __DIR__ . '/../../storage/sessions';
    // Verificar que la carpeta exista
    if (!is_dir($sessionPath)) {
        mkdir($sessionPath, 0775, true);
    }
    // Asignar la ruta de almacenamiento de sesiones
    session_save_path($sessionPath);

    session_set_cookie_params([
        'lifetime' => (int)$sessionLifetime,
        'path' => $path,
        'domain' => $host,
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
    Logger::debug('Ruta de sesiones: ' . session_save_path());
    Logger::debug('Session ID actual: ' . session_id());

    foreach (glob($sessionPath . '/sess_*') as $file) {
        if (filemtime($file) < time() - $sessionLifetime) {
            @unlink($file);
        }
    }
}

// Registrar shut down function para manejo de errores
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);

        // Determinar si estamos en modo debug
        $debug = false;
        if (class_exists('Dotenv\Dotenv')) {
            $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
            $dotenv->safeLoad();
            $debug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);
        }

        if ($debug) {
            echo "<h1>Error crítico</h1>";
            echo "<pre>" . print_r($error, true) . "</pre>";
        } else {
            echo "<h1>Error interno del servidor</h1>";
            Logger::error(print_r($error, true));
        }
    }
});

// NOTA: Hemos eliminado completamente el autoloader personalizado
// Composer's autoloader se encarga de todo ahora

// ===== HELPERS PARA VISTAS - FASE 3 MEJORAS =====
// Incluir helpers para acceso seguro a datos en vistas
require_once __DIR__ . '/Helpers/ViewHelpers.php';
