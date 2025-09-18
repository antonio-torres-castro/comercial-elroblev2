<?php
namespace App\Config;

use Dotenv\Dotenv;

class AppConfig
{
    private static array $config = [];

    public static function load(): void
    {
        // Cargar variables de entorno (.env en la raíz del proyecto)
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../../');
        $dotenv->load();

        self::$config = [
            'app_name' => $_ENV['APP_NAME'] ?? 'SETAP',
            'app_env'  => $_ENV['APP_ENV'] ?? 'local',
            'app_url'  => $_ENV['APP_URL'] ?? 'http://localhost:8080/setap',
            'timezone' => $_ENV['TIMEZONE'] ?? 'America/Santiago',
            'locale'   => $_ENV['LOCALE'] ?? 'es_CL',
            'debug'    => filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'session_lifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 3600),
        ];

        // Configurar zona horaria global
        date_default_timezone_set(self::$config['timezone']);
    }

    public static function get(string $key, $default = null)
    {
        return self::$config[$key] ?? $default;
    }
}

// Cargar configuración al incluir este archivo
AppConfig::load();
