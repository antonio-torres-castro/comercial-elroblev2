<?php

declare(strict_types=1);

namespace App\Helpers;

class Logger
{
    /** @var string */
    private static string $logFile;

    /**
     * Inicializa el logger indicando la ruta y nombre del archivo de log.
     */
    public static function init(string $logPath): void
    {
        self::$logFile = $logPath;
        // Crea el directorio si no existe
        $dir = dirname($logPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
    }

    /**
     * Registra un mensaje en el log.
     * 
     * @param string $level Nivel del log (INFO, WARNING, ERROR, DEBUG, etc.)
     * @param string $message Mensaje a registrar
     */
    public static function log(string $level, string $message): void
    {
        if (empty(self::$logFile)) {
            // Si no se inicializó, usa el error_log por defecto del sistema
            error_log("Logger no inicializado: $message");
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $formatted = sprintf("[%s] [%s] %s%s", $timestamp, strtoupper($level), $message, PHP_EOL);

        // Usa error_log con flag 3 (append a archivo)
        error_log($formatted, 3, self::$logFile);
    }

    /** Métodos auxiliares */
    public static function info(string $message): void
    {
        self::log('INFO', $message);
    }
    public static function warning(string $message): void
    {
        self::log('WARNING', $message);
    }
    public static function error(string $message): void
    {
        self::log('ERROR', $message);
    }
    public static function debug(string $message): void
    {
        self::log('DEBUG', $message);
    }
}
