<?php

declare(strict_types=1);

use App\Helpers\Logger; // ajusta el namespace según donde guardes la clase

// Inicializa una sola vez (por ejemplo en tu bootstrap o config global)
//storage\logs\setap_auth_debug.log
Logger::init(__DIR__ . '/storage/logs/error.log');

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
App\Config\AppConfig::load();

// Configurar base path
define('BASE_PATH', '/setap/');

// No necesitamos requires manuales - Composer los maneja automáticamente
// La sesión debe iniciarse antes de cualquier output
if (session_status() === PHP_SESSION_NONE) {
    // Configuración básica de sesión por defecto
    $sessionLifetime = 3600;
    $appUrl = 'http://localhost:8080/setap';

    // Intentar cargar configuración desde variables de entorno si están disponibles
    if (class_exists('Dotenv\Dotenv')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->safeLoad();

        $sessionLifetime = $_ENV['SESSION_LIFETIME'] ?? $sessionLifetime;
        $appUrl = $_ENV['APP_URL'] ?? $appUrl;
    }

    session_set_cookie_params([
        'lifetime' => (int)$sessionLifetime,
        'path' => '/setap/',
        'domain' => parse_url($appUrl, PHP_URL_HOST),
        'secure' => parse_url($appUrl, PHP_URL_SCHEME) === 'https',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
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
