<?php

declare(strict_types=1);

// Cargar configuración
App\Config\AppConfig::load();

// No necesitamos requires manuales - Composer los maneja automáticamente
// La sesión debe iniciarse antes de cualquier output
if (session_status() === PHP_SESSION_NONE) {
    // Configuración básica de sesión por defecto
    $sessionLifetime = 3600;
    $appUrl = 'http://localhost:8080';

    // Intentar cargar configuración desde variables de entorno si están disponibles
    if (class_exists('Dotenv\Dotenv')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->safeLoad();

        $sessionLifetime = $_ENV['SESSION_LIFETIME'] ?? $sessionLifetime;
        $appUrl = $_ENV['APP_URL'] ?? $appUrl;
    }

    session_set_cookie_params([
        'lifetime' => (int)$sessionLifetime,
        'path' => '/',
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
            error_log("Error crítico: " . print_r($error, true));
        }
    }
});
