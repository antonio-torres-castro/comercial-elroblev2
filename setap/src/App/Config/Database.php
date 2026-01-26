<?php

namespace App\Config;

use App\Helpers\Logger;
use PDO;
use PDOException;
use RuntimeException;

class Database
{
    private static ?PDO $instance = null;

    private function __construct() {}
    private function __clone() {}

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            // Verificar si estamos en desarrollo o testing para usar SQLite
            $env = AppConfig::get('app_env', '');
            if ($env === 'development' || $env === 'testing') {
                Logger::debug("Entre en modo: " . $env);
                // Usar SQLite para desarrollo o testing
                if ($env === 'testing' && ($_ENV['DB_DATABASE'] ?? '') === ':memory:') {
                    // Base de datos en memoria para testing
                    $dsn = "sqlite::memory:";
                } else {
                    // Archivo SQLite para desarrollo
                    $dbPath = __DIR__ . '/../../../storage/database.sqlite';

                    // Crear el directorio storage si no existe
                    $storageDir = dirname($dbPath);
                    if (!is_dir($storageDir)) {
                        mkdir($storageDir, 0755, true);
                    }

                    // Crear archivo de base de datos si no existe
                    if (!file_exists($dbPath)) {
                        touch($dbPath);
                    }

                    $dsn = "sqlite:$dbPath";
                }
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ];

                try {
                    self::$instance = new PDO($dsn, null, null, $options);
                } catch (PDOException $e) {
                    throw new RuntimeException('Error de conexión a SQLite: ' . $e->getMessage());
                }
            } else {
                Logger::debug("Entre en modo: " . $env);
                // Usar MySQL para producción
                $host = AppConfig::get('db_host', '');
                $db   = AppConfig::get('db_database', '');
                $user = AppConfig::get('db_username', '');
                $pass = AppConfig::get('db_password', '');
                $port = AppConfig::get('db_port', 3306);
                $charset = 'utf8mb4';

                $dsn = "mysql:host=$host;dbname=$db;port=$port;charset=$charset";

                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::ATTR_PERSISTENT         => false,
                ];

                try {
                    self::$instance = new PDO($dsn, $user, $pass, $options);
                } catch (PDOException $e) {
                    throw new RuntimeException('conexión BD: ' . $e->getMessage());
                }
            }
        }

        return self::$instance;
    }



    public static function disconnect(): void
    {
        self::$instance = null;
    }
}
