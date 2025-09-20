<?php

namespace App\Config;

use Dotenv\Dotenv;
use RuntimeException;

class AppConfig
{
    private static array $config = [];
    private static bool $loaded = false;

    public static function load(): void
    {
        if (self::$loaded) {
            return;
        }

        // Obtener la ruta raíz del proyecto (donde está el .env)
        $projectRoot = dirname(__DIR__, 3);

        // Verificar si el archivo .env existe
        if (!file_exists($projectRoot . '/.env')) {
            throw new RuntimeException('El archivo .env no existe en la ruta: ' . $projectRoot);
        }

        $dotenv = Dotenv::createImmutable($projectRoot);
        $dotenv->load();

        // Cargar cada variable individualmente
        self::loadAppEnv();
        self::loadDebug();
        self::loadAppName();
        self::loadAppUrl();
        self::loadDatabaseConfig();
        self::loadSecurityConfig();
        self::loadLocalizationConfig();

        // Configurar zona horaria global
        date_default_timezone_set(self::$config['timezone']);

        // Configurar manejo de errores según el entorno
        self::configureErrorHandling();

        self::$loaded = true;
    }

    private static function loadAppEnv(): void
    {
        self::$config['app_env'] = $_ENV['APP_ENV'] ?? '';
    }

    private static function loadDebug(): void
    {
        $debug = $_ENV['APP_DEBUG'] ?? true;
        self::$config['debug'] = filter_var($debug, FILTER_VALIDATE_BOOLEAN);
    }

    private static function loadAppName(): void
    {
        self::$config['app_name'] = $_ENV['APP_NAME'] ?? '';
    }

    private static function loadAppUrl(): void
    {
        $appUrl = $_ENV['APP_URL'] ?? '';
        self::$config['app_url'] = rtrim($appUrl, '/');
    }

    private static function loadDatabaseConfig(): void
    {
        // Validar variables de base de datos requeridas
        self::validateRequired(['DB_HOST', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD']);

        self::$config['db_host'] = $_ENV['DB_HOST'];
        self::$config['db_port'] = (int)($_ENV['DB_PORT'] ?? -1);
        self::$config['db_database'] = $_ENV['DB_DATABASE'];
        self::$config['db_username'] = $_ENV['DB_USERNAME'];
        self::$config['db_password'] = $_ENV['DB_PASSWORD'];
    }

    private static function loadSecurityConfig(): void
    {
        self::$config['password_min_length'] = (int)($_ENV['PASSWORD_MIN_LENGTH'] ?? 8);
        self::$config['session_lifetime'] = (int)($_ENV['SESSION_LIFETIME'] ?? 3600);
    }

    private static function loadLocalizationConfig(): void
    {
        self::$config['timezone'] = $_ENV['TIMEZONE'] ?? '';
        self::$config['locale'] = $_ENV['LOCALE'] ?? '';
    }

    private static function validateRequired(array $requiredVars): void
    {
        foreach ($requiredVars as $var) {
            if (!isset($_ENV[$var]) || empty($_ENV[$var])) {
                throw new RuntimeException("La variable de entorno requerida '{$var}' no está definida o está vacía.");
            }
        }
    }

    private static function configureErrorHandling(): void
    {
        if (self::$config['debug']) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
        } else {
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
            ini_set('display_errors', '0');
            ini_set('log_errors', '1');
        }
    }

    public static function get(string $key, $default = null)
    {
        if (!self::$loaded) {
            self::load();
        }

        return self::$config[$key] ?? $default;
    }

    public static function isDebug(): bool
    {
        if (!self::$loaded) {
            self::load();
        }

        return self::$config['debug'];
    }

    public static function getEnv(): string
    {
        if (!self::$loaded) {
            self::load();
        }

        return self::$config['app_env'];
    }
}
