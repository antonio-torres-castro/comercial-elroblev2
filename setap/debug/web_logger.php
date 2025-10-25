<?php
/**
 * Sistema de Logging Avanzado para Debug Web-Only
 * Complementa las herramientas de debug con logging estructurado
 * Autor: MiniMax Agent
 */

require_once 'config.php';

class WebDebugLogger
{
    private static $instance = null;
    private $logFile;
    private $isInitialized = false;
    
    private function __construct()
    {
        $this->initializeLogger();
    }
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function initializeLogger()
    {
        if (!DEBUG_LOG_ENABLED) {
            return;
        }
        
        $this->logFile = DEBUG_LOG_DIR . '/web_debug.log';
        
        // Crear directorio si no existe
        if (!is_dir(DEBUG_LOG_DIR)) {
            mkdir(DEBUG_LOG_DIR, 0755, true);
        }
        
        $this->isInitialized = true;
        
        // Log de inicialización
        $this->log('INFO', 'Sistema de logging web debug inicializado', [
            'ip' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ]);
    }
    
    public function log($level, $message, $context = [])
    {
        if (!$this->isInitialized || !DEBUG_LOG_ENABLED) {
            return false;
        }
        
        // Verificar rotación de log
        $this->rotateLogIfNeeded();
        
        $timestamp = date('Y-m-d H:i:s');
        $ip = $this->getClientIP();
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $logEntry = "[$timestamp] [$level] [IP:$ip] $message$contextStr" . PHP_EOL;
        
        return file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
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
    
    public function critical($message, $context = [])
    {
        return $this->log('CRITICAL', $message, $context);
    }
    
    /**
     * Log específico para debugging web
     */
    public function webDebug($message, $context = [])
    {
        $extendedContext = array_merge($context, [
            'session_id' => session_id(),
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'http_host' => $_SERVER['HTTP_HOST'] ?? 'unknown',
            'referer' => $_SERVER['HTTP_REFERER'] ?? 'none'
        ]);
        
        return $this->log('DEBUG', "[WEB] $message", $extendedContext);
    }
    
    /**
     * Log específico para errores de aplicación
     */
    public function appError($message, $context = [])
    {
        $extendedContext = array_merge($context, [
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true)
        ]);
        
        return $this->log('ERROR', "[APP] $message", $extendedContext);
    }
    
    /**
     * Log específico para operaciones de base de datos
     */
    public function dbDebug($message, $context = [])
    {
        $extendedContext = array_merge($context, [
            'execution_time' => microtime(true),
            'memory_before' => memory_get_usage(true)
        ]);
        
        return $this->log('DEBUG', "[DB] $message", $extendedContext);
    }
    
    /**
     * Log específico para monitoreo de rendimiento
     */
    public function performanceLog($operation, $duration, $context = [])
    {
        $level = $duration > 1000 ? 'WARNING' : 'INFO';
        $extendedContext = array_merge($context, [
            'duration_ms' => round($duration * 1000, 2),
            'memory_after' => memory_get_usage(true),
            'memory_delta' => memory_get_usage(true) - ($context['memory_before'] ?? 0)
        ]);
        
        return $this->log($level, "[PERF] $operation took {$extendedContext['duration_ms']}ms", $extendedContext);
    }
    
    /**
     * Log específico para seguridad
     */
    public function securityLog($event, $context = [])
    {
        $extendedContext = array_merge($context, [
            'ip' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'timestamp' => time()
        ]);
        
        return $this->log('WARNING', "[SECURITY] $event", $extendedContext);
    }
    
    /**
     * Obtener logs recientes
     */
    public function getRecentLogs($lines = 50, $level = null)
    {
        if (!file_exists($this->logFile)) {
            return [];
        }
        
        $allLines = file($this->logFile);
        $recentLines = array_slice($allLines, -$lines);
        
        if ($level !== null) {
            $recentLines = array_filter($recentLines, function($line) use ($level) {
                return strpos($line, "[$level]") !== false;
            });
        }
        
        return array_values($recentLines);
    }
    
    /**
     * Buscar logs por término
     */
    public function searchLogs($searchTerm, $lines = 100)
    {
        if (!file_exists($this->logFile)) {
            return [];
        }
        
        $allLines = file($this->logFile);
        $recentLines = array_slice($allLines, -$lines);
        
        $matchingLines = array_filter($recentLines, function($line) use ($searchTerm) {
            return stripos($line, $searchTerm) !== false;
        });
        
        return array_values($matchingLines);
    }
    
    /**
     * Obtener estadísticas de logs
     */
    public function getLogStats()
    {
        if (!file_exists($this->logFile)) {
            return [
                'total_lines' => 0,
                'file_size' => 0,
                'levels' => []
            ];
        }
        
        $content = file_get_contents($this->logFile);
        $lines = explode("\n", $content);
        
        $levels = ['DEBUG' => 0, 'INFO' => 0, 'WARNING' => 0, 'ERROR' => 0, 'CRITICAL' => 0];
        
        foreach ($lines as $line) {
            foreach ($levels as $level => $count) {
                if (strpos($line, "[$level]") !== false) {
                    $levels[$level]++;
                    break;
                }
            }
        }
        
        return [
            'total_lines' => count(array_filter($lines)),
            'file_size' => filesize($this->logFile),
            'last_modified' => filemtime($this->logFile),
            'levels' => $levels
        ];
    }
    
    /**
     * Limpiar logs antiguos
     */
    public function cleanupOldLogs($daysToKeep = 30)
    {
        $cleanedFiles = 0;
        $cutoffTime = time() - ($daysToKeep * 24 * 60 * 60);
        
        $logFiles = glob(DEBUG_LOG_DIR . '/*.log*');
        foreach ($logFiles as $file) {
            if (filemtime($file) < $cutoffTime) {
                if (unlink($file)) {
                    $cleanedFiles++;
                    $this->log('INFO', "Archivo de log eliminado: " . basename($file));
                }
            }
        }
        
        return $cleanedFiles;
    }
    
    /**
     * Exportar logs a JSON
     */
    public function exportToJson($lines = 100)
    {
        $logs = $this->getRecentLogs($lines);
        $structuredLogs = [];
        
        foreach ($logs as $logLine) {
            if (preg_match('/^\[(.*?)\] \[(.*?)\] \[IP:(.*?)\] (.*)$/', $logLine, $matches)) {
                $structuredLogs[] = [
                    'timestamp' => $matches[1],
                    'level' => $matches[2],
                    'ip' => $matches[3],
                    'message' => trim($matches[4])
                ];
            }
        }
        
        return json_encode($structuredLogs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Rotar log si excede el tamaño máximo
     */
    private function rotateLogIfNeeded()
    {
        if (!DEBUG_LOG_ROTATION || !file_exists($this->logFile)) {
            return;
        }
        
        if (filesize($this->logFile) < DEBUG_MAX_LOG_SIZE) {
            return;
        }
        
        $timestamp = date('Y-m-d_H-i-s');
        $rotatedFile = $this->logFile . '.' . $timestamp;
        
        if (rename($this->logFile, $rotatedFile)) {
            $this->log('INFO', "Log rotado a: " . basename($rotatedFile));
        }
    }
    
    /**
     * Obtener IP del cliente
     */
    private function getClientIP()
    {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Handler de errores personalizado para logging
     */
    public static function errorHandler($errno, $errstr, $errfile, $errline)
    {
        $logger = self::getInstance();
        
        $errorTypes = [
            E_ERROR => 'FATAL ERROR',
            E_WARNING => 'WARNING',
            E_PARSE => 'PARSE ERROR',
            E_NOTICE => 'NOTICE',
            E_CORE_ERROR => 'CORE ERROR',
            E_CORE_WARNING => 'CORE WARNING',
            E_COMPILE_ERROR => 'COMPILE ERROR',
            E_USER_ERROR => 'USER ERROR',
            E_USER_WARNING => 'USER WARNING',
            E_USER_NOTICE => 'USER NOTICE',
            // E_STRICT fue removido en PHP 5.4
            E_RECOVERABLE_ERROR => 'RECOVERABLE ERROR',
            E_DEPRECATED => 'DEPRECATED',
            E_USER_DEPRECATED => 'USER DEPRECATED'
        ];
        
        $errorType = $errorTypes[$errno] ?? 'UNKNOWN ERROR';
        
        $logger->error("$errorType: $errstr in $errfile on line $errline", [
            'error_number' => $errno,
            'error_file' => $errfile,
            'error_line' => $errline,
            'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
        ]);
        
        return false; // Continuar con el manejo normal de errores
    }
    
    /**
     * Handler de excepciones para logging
     */
    public static function exceptionHandler($exception)
    {
        $logger = self::getInstance();
        
        $logger->critical("Uncaught Exception: " . $exception->getMessage(), [
            'exception_class' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'previous' => $exception->getPrevious() ? get_class($exception->getPrevious()) : null
        ]);
    }
}

// Configurar handlers de error
if (DEBUG_LOG_ENABLED) {
    set_error_handler([WebDebugLogger::class, 'errorHandler']);
    set_exception_handler([WebDebugLogger::class, 'exceptionHandler']);
}

// Función global para logging fácil
function webDebugLog($level, $message, $context = [])
{
    return WebDebugLogger::getInstance()->log($level, $message, $context);
}

// Funciones helper para logging rápido
function webDebugInfo($message, $context = [])
{
    return WebDebugLogger::getInstance()->info($message, $context);
}

function webDebugError($message, $context = [])
{
    return WebDebugLogger::getInstance()->error($message, $context);
}

function webDebugWarning($message, $context = [])
{
    return WebDebugLogger::getInstance()->warning($message, $context);
}

// Función para inicializar logging al incluir este archivo
if (DEBUG_LOG_ENABLED) {
    WebDebugLogger::getInstance()->info('Archivo de logging web debug cargado');
}
?>