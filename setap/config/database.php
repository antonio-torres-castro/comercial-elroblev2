<?php

/**
 * Configuración de Base de Datos
 * Carga las constantes necesarias para la aplicación
 */

use App\Helpers\Logger;
// Cargar variables de entorno desde el archivo _env
function loadEnv($path)
{
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
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    loadEnv($envPath);
}

// Definir constantes de base de datos
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_PORT', $_ENV['DB_PORT'] ?? '3306');
define('DB_NAME', $_ENV['DB_DATABASE'] ?? $_ENV['DB_NAME'] ?? 'comerci3_bdsetap');
define('DB_USER', $_ENV['DB_USERNAME'] ?? $_ENV['DB_USER'] ?? 'comerci3_admin');
define('DB_PASS', $_ENV['DB_PASSWORD'] ?? $_ENV['DB_PASS'] ?? 'Micomercial.1');
define('DB_CONNECTION_TIMEOUT', $_ENV['DB_CONNECTION_TIMEOUT'] ?? 30); // segundos

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

// ===== CONFIGURACIÓN DE DEBUG Y LOGGING =====

// Configuración de logging
define('DEBUG_MODE', $_ENV['DEBUG_MODE'] ?? APP_DEBUG);
define('LOG_LEVEL', $_ENV['LOG_LEVEL'] ?? 'INFO');
define('LOG_TO_FILE', $_ENV['LOG_TO_FILE'] ?? true);
define('LOG_TO_DB', $_ENV['LOG_TO_DB'] ?? false);

// Configuración de base de datos para debug
define('DB_DEBUG_MODE', $_ENV['DB_DEBUG_MODE'] ?? DEBUG_MODE);
define('DB_SLOW_QUERY_THRESHOLD', $_ENV['DB_SLOW_QUERY_THRESHOLD'] ?? 100); // milisegundos

// Configuración de herramientas de debug
define('ENABLE_DEBUG_TOOLS', $_ENV['ENABLE_DEBUG_TOOLS'] ?? DEBUG_MODE);
define('DEBUG_ALLOWED_IPS', explode(',', $_ENV['DEBUG_ALLOWED_IPS'] ?? '127.0.0.1,localhost,::1'));

// ===== CLASE DE CONEXIÓN MEJORADA =====

class DatabaseConnection
{
    private static $instance = null;
    private $pdo = null;
    private $connectionTime = null;
    private $queryCount = 0;
    private $slowQueries = [];

    private function __construct()
    {
        $this->connect();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function connect()
    {
        $startTime = microtime(true);

        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];

            // Agregar timeout de conexión
            if (defined('DB_CONNECTION_TIMEOUT')) {
                $options[PDO::ATTR_TIMEOUT] = DB_CONNECTION_TIMEOUT;
            }

            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            $this->connectionTime = microtime(true) - $startTime;

            if (DEBUG_MODE) {
                $this->logConnectionInfo();
            }
        } catch (PDOException $e) {
            $this->logConnectionError($e);
            throw $e;
        }
    }

    public function getConnection()
    {
        return $this->pdo;
    }

    public function executeQuery($query, $params = [])
    {
        $startTime = microtime(true);
        $this->queryCount++;

        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);

            $executionTime = (microtime(true) - $startTime) * 1000; // en milisegundos

            if ($executionTime > DB_SLOW_QUERY_THRESHOLD) {
                $this->slowQueries[] = [
                    'query' => $query,
                    'time' => $executionTime,
                    'timestamp' => date('Y-m-d H:i:s')
                ];
            }

            if (DEBUG_MODE) {
                $this->logQuery($query, $executionTime, count($params));
            }

            return $stmt;
        } catch (PDOException $e) {
            $this->logQueryError($query, $e);
            throw $e;
        }
    }

    public function getConnectionStats()
    {
        return [
            'connection_time_ms' => round($this->connectionTime * 1000, 2),
            'query_count' => $this->queryCount,
            'slow_queries' => count($this->slowQueries),
            'is_connected' => $this->pdo !== null
        ];
    }

    private function logConnectionInfo()
    {
        if (function_exists('error_log')) {
            Logger::debug("DB Conn ok:" . round($this->connectionTime * 1000, 2) . "ms");
        }
    }

    private function logConnectionError($e)
    {
        if (function_exists('error_log')) {
            Logger::error("DB Conn nok:" . $e->getMessage());
        }
    }

    private function logQuery($query, $time, $paramCount)
    {
        if (function_exists('error_log')) {
            Logger::debug("Query ok:" . round($time, 2) . "ms (params: $paramCount): " . substr($query, 0, 100));
        }
    }

    private function logQueryError($query, $e)
    {
        if (function_exists('error_log')) {
            Logger::error("Query nok:" . $e->getMessage() . " | Query: " . substr($query, 0, 100));
        }
    }
}

// Función helper para obtener conexión
function getDbConnection()
{
    return DatabaseConnection::getInstance()->getConnection();
}

// Función helper para ejecutar query con logging
function executeDbQuery($query, $params = [])
{
    return DatabaseConnection::getInstance()->executeQuery($query, $params);
}

// Función para verificar estado de la base de datos
function checkDatabaseHealth()
{
    try {
        $db = DatabaseConnection::getInstance();
        $stmt = $db->executeQuery("SELECT 1 as test");
        $result = $stmt->fetch();

        return [
            'status' => 'OK',
            'test_result' => $result['test'],
            'stats' => $db->getConnectionStats()
        ];
    } catch (Exception $e) {
        return [
            'status' => 'ERROR',
            'error' => $e->getMessage()
        ];
    }
}

// Log de carga de configuración
if (DEBUG_MODE && function_exists('error_log')) {
    Logger::debug("DB config loaded: " . APP_ENV);
}
