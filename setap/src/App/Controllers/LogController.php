<?php

namespace App\Controllers;

use App\Services\CustomLogger;

/**
 * LogController - Para visualizar logs del sistema
 */
class LogController extends AbstractBaseController
{
    protected function initializeController(): void
    {
        // Solo inicialización básica
    }

    /**
     * Ver logs de autenticación (solo para debug)
     */
    public function viewAuthLogs()
    {
        // Solo permitir acceso en desarrollo/debug
        $allowedHosts = [
            'localhost',
            '127.0.0.1', 
            '::1',
            '192.168.',
            '10.',
            '172.'
        ];

        $clientIP = $_SERVER['REMOTE_ADDR'] ?? '';
        $isLocal = false;

        foreach ($allowedHosts as $allowed) {
            if (strpos($clientIP, $allowed) === 0) {
                $isLocal = true;
                break;
            }
        }

        if (!$isLocal) {
            http_response_code(403);
            die('Acceso denegado. Solo acceso local permitido.');
        }

        $logs = CustomLogger::getLastLogs(100);
        $logFilePath = CustomLogger::getLogFilePath();
        
        echo "<!DOCTYPE html>\n";
        echo "<html><head><title>SETAP - Debug Logs</title></head><body>\n";
        echo "<h1>🔐 SETAP Authentication Debug Logs</h1>\n";
        echo "<p><strong>Log File:</strong> " . htmlspecialchars($logFilePath) . "</p>\n";
        echo "<p><strong>Last updated:</strong> " . date('Y-m-d H:i:s') . "</p>\n";
        echo "<p><strong>Total entries:</strong> " . count($logs) . "</p>\n";
        echo "<p><strong>Clear logs:</strong> <a href='?clear=1'>[Clear]</a></p>\n";
        
        if (isset($_GET['clear']) && $_GET['clear'] === '1') {
            CustomLogger::clearLogs();
            echo "<p><strong style='color: red;'>Logs cleared!</strong></p>\n";
            echo "<meta http-equiv='refresh' content='2;url=?'>\n";
        }
        
        echo "<pre style='background: #f5f5f5; padding: 20px; border: 1px solid #ccc; max-height: 600px; overflow-y: scroll;'>\n";
        
        if (empty($logs)) {
            echo "No logs found.\n";
        } else {
            foreach ($logs as $log) {
                $color = '#333';
                if (strpos($log, '[ERROR]') !== false) {
                    $color = '#d32f2f';
                } elseif (strpos($log, '[WARNING]') !== false) {
                    $color = '#f57c00';
                } elseif (strpos($log, '[INFO]') !== false) {
                    $color = '#1976d2';
                } elseif (strpos($log, '[DEBUG]') !== false) {
                    $color = '#388e3c';
                }
                
                echo "<span style='color: $color;'>" . htmlspecialchars($log) . "</span>\n";
            }
        }
        
        echo "</pre>\n";
        echo "</body></html>\n";
    }
}