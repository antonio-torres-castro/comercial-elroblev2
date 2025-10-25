<?php

namespace App\Services;

class CustomLogger
{
    private static $logFile;
    private static $initialized = false;

    /**
     * Inicializar el logger personalizado
     */
    private static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        // Crear directorio de logs si no existe
        $logDir = __DIR__ . '/../../../storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        self::$logFile = $logDir . '/setap_auth_debug.log';
        self::$initialized = true;
    }

    /**
     * Escribir log personalizado
     */
    public static function log(string $level, string $message, array $context = []): void
    {
        self::init();
        
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = empty($context) ? '' : ' | Context: ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        $logEntry = "[$timestamp] [$level] $message$contextStr" . PHP_EOL;
        
        // Agregar al archivo de log
        file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // También mantener error_log() para compatibilidad
        error_log($message);
    }

    /**
     * Log de debug
     */
    public static function debug(string $message, array $context = []): void
    {
        self::log('DEBUG', $message, $context);
    }

    /**
     * Log de información
     */
    public static function info(string $message, array $context = []): void
    {
        self::log('INFO', $message, $context);
    }

    /**
     * Log de advertencia
     */
    public static function warning(string $message, array $context = []): void
    {
        self::log('WARNING', $message, $context);
    }

    /**
     * Log de error
     */
    public static function error(string $message, array $context = []): void
    {
        self::log('ERROR', $message, $context);
    }

    /**
     * Obtener la ruta del archivo de log
     */
    public static function getLogFilePath(): string
    {
        self::init();
        return self::$logFile;
    }

    /**
     * Obtener los últimos N logs
     */
    public static function getLastLogs(int $lines = 50): array
    {
        self::init();
        
        if (!file_exists(self::$logFile)) {
            return [];
        }

        $logs = [];
        $file = new \SplFileObject(self::$logFile, 'r');
        
        // Ir al final del archivo
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();
        
        // Leer desde el principio hasta obtener las últimas N líneas
        $startLine = max(0, $totalLines - $lines);
        $file->seek($startLine);
        
        while (!$file->eof()) {
            $line = trim($file->current());
            if (!empty($line)) {
                $logs[] = $line;
            }
            $file->next();
        }
        
        return array_reverse($logs);
    }

    /**
     * Limpiar el archivo de log
     */
    public static function clearLogs(): void
    {
        self::init();
        
        if (file_exists(self::$logFile)) {
            file_put_contents(self::$logFile, '');
        }
    }
}