<?php

/**
 * Configuración Centralizada para Herramientas de Debug
 * Incluye todas las configuraciones necesarias para el sistema de depuración
 * Autor: MiniMax Agent
 */

// IPs autorizadas (configuración centralizada)
define('DEBUG_ALLOWED_IPS', [
    '127.0.0.1',
    'localhost',
    '::1',
    '181.72.88.67'
]);

// Ruta del directorio de logs
define('DEBUG_LOG_DIR', __DIR__ . '/../logs');

// Configuración de logging
define('DEBUG_LOG_ENABLED', true);
define('DEBUG_LOG_LEVEL', 'DEBUG'); // DEBUG, INFO, WARNING, ERROR
define('DEBUG_MAX_LOG_SIZE', 10 * 1024 * 1024); // 10MB
define('DEBUG_LOG_ROTATION', true);

// Configuración de base de datos
define('DEBUG_DB_CHECK_ENABLED', true);
define('DEBUG_DB_TIMEOUT', 5); // segundos

// Configuración de rendimiento
define('DEBUG_MEMORY_MONITORING', true);
define('DEBUG_CPU_MONITORING', false); // Requiere extensión sys_getloadavg
define('DEBUG_PERFORMANCE_TRACKING', true);

// Configuración de seguridad
define('DEBUG_SECURE_MODE', true);
define('DEBUG_SESSION_TIMEOUT', 3600); // 1 hora
define('DEBUG_ACCESS_LOG', true);

// Configuración de herramientas
define('DEBUG_TOOL_ENABLED', true);
define('DEBUG_AUTO_REFRESH_INTERVAL', 30000); // 30 segundos
define('DEBUG_MAX_LINES_DISPLAY', 1000);

// Configuración de notificaciones
define('DEBUG_EMAIL_NOTIFICATIONS', false);
define('DEBUG_WEBHOOK_URL', '');

// Configuración de herramientas específicas
define('DEBUG_SQL_SAFE_MODE', true);
define('DEBUG_SQL_MAX_EXECUTION_TIME', 10); // segundos
define('DEBUG_FILE_ACCESS_LIMIT', 50 * 1024 * 1024); // 50MB

// Configuración de extensiones PHP críticas
define('DEBUG_CRITICAL_EXTENSIONS', [
    'pdo',
    'pdo_mysql',
    'curl',
    'openssl',
    'mbstring',
    'json',
    'filter',
    'session',
    'gd',
    'zip',
    'dom'
]);

// Configuración de archivos críticos del sistema
define('DEBUG_CRITICAL_FILES', [
    '../config/database.php' => 'Configuración de Base de Datos',
    '../src/App/bootstrap.php' => 'Bootstrap de Aplicación',
    '../public/index.php' => 'Punto de Entrada Principal',
    '../vendor/autoload.php' => 'Composer Autoload',
    '../.htaccess' => 'Configuración Apache',
    '../composer.json' => 'Configuración Composer'
]);

// Configuración de directorios críticos
define('DEBUG_CRITICAL_DIRS', [
    '../logs' => 'Directorio de Logs',
    '../storage' => 'Directorio de Storage',
    '../cache' => 'Directorio de Cache',
    '../tmp' => 'Directorio Temporal'
]);

// Función para verificar si una IP está autorizada
function isDebugAccessAllowed()
{
    $clientIP = $_SERVER['REMOTE_ADDR'] ?? '';

    // Verificar en lista de IPs permitidas
    if (in_array($clientIP, DEBUG_ALLOWED_IPS)) {
        return true;
    }

    // Verificar si es localhost o loopback
    if (in_array($clientIP, ['127.0.0.1', '::1', 'localhost'])) {
        return true;
    }

    return false;
}

// Función para verificar configuración de debug
function isDebugEnabled()
{
    return DEBUG_TOOL_ENABLED && isDebugAccessAllowed();
}

// Función para obtener configuración de entorno
function getEnvironmentConfig()
{
    $config = [
        'development' => [
            'debug_enabled' => true,
            'log_level' => 'DEBUG',
            'display_errors' => true
        ],
        'production' => [
            'debug_enabled' => false,
            'log_level' => 'ERROR',
            'display_errors' => false
        ]
    ];

    $env = $_ENV['APP_ENV'] ?? 'production';
    return $config[$env] ?? $config['production'];
}

// Función para verificar estado de herramientas críticas
function checkDebugHealth()
{
    $health = [
        'status' => 'OK',
        'checks' => [],
        'warnings' => [],
        'errors' => []
    ];

    // Verificar directorio de logs
    if (!is_dir(DEBUG_LOG_DIR)) {
        if (!mkdir(DEBUG_LOG_DIR, 0755, true)) {
            $health['errors'][] = 'No se puede crear directorio de logs';
            $health['status'] = 'ERROR';
        }
    }

    if (!is_writable(DEBUG_LOG_DIR)) {
        $health['warnings'][] = 'Directorio de logs no tiene permisos de escritura';
    }

    // Verificar archivos de configuración
    foreach (DEBUG_CRITICAL_FILES as $file => $description) {
        if (!file_exists($file)) {
            $health['warnings'][] = "Archivo crítico no encontrado: $description";
        }
    }

    // Verificar extensiones PHP
    foreach (DEBUG_CRITICAL_EXTENSIONS as $ext) {
        if (!extension_loaded($ext)) {
            $health['warnings'][] = "Extensión PHP no cargada: $ext";
        }
    }

    // Verificar conexión a base de datos
    if (DEBUG_DB_CHECK_ENABLED) {
        try {
            if (file_exists('../config/database.php')) {
                include_once '../config/database.php';
                if (defined('DB_HOST')) {
                    $pdo = new PDO(
                        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                        DB_USER,
                        DB_PASS,
                        [PDO::ATTR_TIMEOUT => DEBUG_DB_TIMEOUT]
                    );
                    $pdo->query("SELECT 1");
                }
            }
        } catch (Exception $e) {
            $health['errors'][] = 'conexión a BD: ' . $e->getMessage();
            $health['status'] = 'ERROR';
        }
    }

    return $health;
}

// Función para limpiar logs antiguos
function cleanupOldLogs($daysToKeep = 30)
{
    if (!DEBUG_LOG_ROTATION) {
        return false;
    }

    $cleaned = 0;
    $cutoffDate = time() - ($daysToKeep * 24 * 60 * 60);

    $logFiles = glob(DEBUG_LOG_DIR . '/*.log*');
    foreach ($logFiles as $file) {
        if (filemtime($file) < $cutoffDate) {
            if (unlink($file)) {
                $cleaned++;
            }
        }
    }

    return $cleaned;
}

// Función para rotación de logs
function rotateLogFile($logFile)
{
    if (!DEBUG_LOG_ROTATION || !file_exists($logFile)) {
        return false;
    }

    $fileSize = filesize($logFile);
    if ($fileSize < DEBUG_MAX_LOG_SIZE) {
        return false;
    }

    $timestamp = date('Y-m-d_H-i-s');
    $rotatedFile = $logFile . '.' . $timestamp;

    return rename($logFile, $rotatedFile);
}

// Clase para manejo avanzado de logging
class AdvancedLogger
{
    private static $instance = null;
    private $logFile;
    private $logLevel;

    private function __construct()
    {
        $this->logFile = DEBUG_LOG_DIR . '/debug_system.log';
        $this->logLevel = DEBUG_LOG_LEVEL;
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function log($level, $message, $context = [])
    {
        if (!DEBUG_LOG_ENABLED) {
            return false;
        }

        if (!$this->shouldLog($level)) {
            return false;
        }

        // Verificar rotación de log
        rotateLogFile($this->logFile);

        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logEntry = "[$timestamp] [$level] [IP:$ip] $message$contextStr" . PHP_EOL;

        return file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    private function shouldLog($level)
    {
        $levels = ['DEBUG' => 0, 'INFO' => 1, 'WARNING' => 2, 'ERROR' => 3];
        $currentLevel = $levels[$this->logLevel] ?? 0;
        $messageLevel = $levels[$level] ?? 0;

        return $messageLevel >= $currentLevel;
    }

    public function debug($message, $context = [])
    {
        return $this->log('DEBUG', $message, $context);
    }

    public function info($message, $context = [])
    {
        return $this->log('INFO', $message, $context);
    }

    public function warning($message, $context = [])
    {
        return $this->log('WARNING', $message, $context);
    }

    public function error($message, $context = [])
    {
        return $this->log('ERROR', $message, $context);
    }
}

// Función para inicializar configuración de debug
function initDebugConfig()
{
    // Configurar zona horaria
    date_default_timezone_set('America/Santiago');

    // Configurar manejo de errores
    if (isDebugEnabled()) {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        ini_set('log_errors', 1);
    } else {
        error_reporting(0);
        ini_set('display_errors', 0);
        ini_set('log_errors', 1);
    }

    // Inicializar logger
    if (DEBUG_LOG_ENABLED) {
        AdvancedLogger::getInstance()->info('Sistema de debug inicializado', [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    }
}

// Inicializar configuración al cargar el archivo
initDebugConfig();
