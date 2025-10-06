<?php

namespace App\Helpers\Security;

/**
 * Limitador de velocidad de requests
 * Responsabilidad única: Controlar rate limiting
 */
class RateLimiter
{
    private static $attempts = [];
    private static $storage = 'session'; // session, file, database

    /**
     * Verificar rate limiting
     */
    public static function checkLimit(string $action, int $maxAttempts = 5, int $timeWindow = 300, string $identifier = null): bool
    {
        $identifier = $identifier ?: self::getIdentifier();
        $key = $action . '_' . $identifier;
        $now = time();

        // Cargar intentos existentes
        $attempts = self::loadAttempts($key);

        // Limpiar intentos antiguos
        $attempts = array_filter($attempts, function($timestamp) use ($now, $timeWindow) {
            return ($now - $timestamp) <= $timeWindow;
        });

        // Verificar si excede el límite
        if (count($attempts) >= $maxAttempts) {
            return false;
        }

        // Registrar este intento
        $attempts[] = $now;
        self::saveAttempts($key, $attempts);

        return true;
    }

    /**
     * Registrar intento fallido
     */
    public static function recordAttempt(string $action, string $identifier = null): void
    {
        $identifier = $identifier ?: self::getIdentifier();
        $key = $action . '_' . $identifier;
        $now = time();

        $attempts = self::loadAttempts($key);
        $attempts[] = $now;
        
        self::saveAttempts($key, $attempts);
    }

    /**
     * Obtener número de intentos restantes
     */
    public static function getRemainingAttempts(string $action, int $maxAttempts = 5, int $timeWindow = 300, string $identifier = null): int
    {
        $identifier = $identifier ?: self::getIdentifier();
        $key = $action . '_' . $identifier;
        $now = time();

        $attempts = self::loadAttempts($key);
        
        // Contar intentos dentro del timeWindow
        $validAttempts = array_filter($attempts, function($timestamp) use ($now, $timeWindow) {
            return ($now - $timestamp) <= $timeWindow;
        });

        return max(0, $maxAttempts - count($validAttempts));
    }

    /**
     * Obtener tiempo hasta que se permita el siguiente intento
     */
    public static function getTimeUntilReset(string $action, int $timeWindow = 300, string $identifier = null): int
    {
        $identifier = $identifier ?: self::getIdentifier();
        $key = $action . '_' . $identifier;
        $now = time();

        $attempts = self::loadAttempts($key);
        
        if (empty($attempts)) {
            return 0;
        }

        // Encontrar el intento más antiguo que aún está en el timeWindow
        $oldestAttempt = min($attempts);
        $timeSinceOldest = $now - $oldestAttempt;
        
        return max(0, $timeWindow - $timeSinceOldest);
    }

    /**
     * Limpiar intentos de una acción específica
     */
    public static function clearAttempts(string $action, string $identifier = null): void
    {
        $identifier = $identifier ?: self::getIdentifier();
        $key = $action . '_' . $identifier;
        
        self::saveAttempts($key, []);
    }

    /**
     * Middleware de rate limiting
     */
    public static function middleware(string $action, int $maxAttempts = 5, int $timeWindow = 300): void
    {
        if (!self::checkLimit($action, $maxAttempts, $timeWindow)) {
            $timeUntilReset = self::getTimeUntilReset($action, $timeWindow);
            
            http_response_code(429); // Too Many Requests
            header('Retry-After: ' . $timeUntilReset);
            
            if (self::isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'error' => 'Demasiados intentos',
                    'retry_after' => $timeUntilReset,
                    'message' => "Intenta nuevamente en {$timeUntilReset} segundos"
                ]);
            } else {
                echo "Demasiados intentos. Intenta nuevamente en {$timeUntilReset} segundos.";
            }
            
            exit;
        }
    }

    /**
     * Rate limiting específico para login
     */
    public static function checkLoginAttempts(string $identifier, int $maxAttempts = 5): bool
    {
        return self::checkLimit('login', $maxAttempts, 900, $identifier); // 15 minutos
    }

    /**
     * Rate limiting para APIs
     */
    public static function checkApiRate(string $endpoint, int $maxRequests = 100, int $timeWindow = 3600): bool
    {
        return self::checkLimit("api_{$endpoint}", $maxRequests, $timeWindow);
    }

    /**
     * Rate limiting para formularios
     */
    public static function checkFormSubmission(string $formName, int $maxSubmissions = 10): bool
    {
        return self::checkLimit("form_{$formName}", $maxSubmissions, 300); // 5 minutos
    }

    /**
     * Obtener identificador único del cliente
     */
    private static function getIdentifier(): string
    {
        // Combinar IP y User Agent para identificar al cliente
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        return md5($ip . $userAgent);
    }

    /**
     * Cargar intentos desde el almacenamiento
     */
    private static function loadAttempts(string $key): array
    {
        switch (self::$storage) {
            case 'session':
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                return $_SESSION['rate_limit'][$key] ?? [];
                
            case 'file':
                $file = sys_get_temp_dir() . '/rate_limit_' . md5($key) . '.json';
                if (file_exists($file)) {
                    $data = json_decode(file_get_contents($file), true);
                    return $data['attempts'] ?? [];
                }
                return [];
                
            default:
                return self::$attempts[$key] ?? [];
        }
    }

    /**
     * Guardar intentos en el almacenamiento
     */
    private static function saveAttempts(string $key, array $attempts): void
    {
        switch (self::$storage) {
            case 'session':
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['rate_limit'][$key] = $attempts;
                break;
                
            case 'file':
                $file = sys_get_temp_dir() . '/rate_limit_' . md5($key) . '.json';
                $data = ['attempts' => $attempts, 'updated' => time()];
                file_put_contents($file, json_encode($data));
                break;
                
            default:
                self::$attempts[$key] = $attempts;
                break;
        }
    }

    /**
     * Configurar tipo de almacenamiento
     */
    public static function setStorage(string $storage): void
    {
        if (in_array($storage, ['session', 'file', 'memory'])) {
            self::$storage = $storage;
        }
    }

    /**
     * Verificar si es request AJAX
     */
    private static function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Limpiar archivos de rate limiting antiguos
     */
    public static function cleanup(): void
    {
        if (self::$storage !== 'file') {
            return;
        }

        $tempDir = sys_get_temp_dir();
        $files = glob($tempDir . '/rate_limit_*.json');
        $now = time();

        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            
            // Eliminar archivos no actualizados en más de 24 horas
            if (isset($data['updated']) && ($now - $data['updated']) > 86400) {
                unlink($file);
            }
        }
    }

    /**
     * Obtener estadísticas de rate limiting
     */
    public static function getStats(string $action, string $identifier = null): array
    {
        $identifier = $identifier ?: self::getIdentifier();
        $key = $action . '_' . $identifier;
        $attempts = self::loadAttempts($key);
        $now = time();

        return [
            'total_attempts' => count($attempts),
            'recent_attempts' => count(array_filter($attempts, function($timestamp) use ($now) {
                return ($now - $timestamp) <= 300; // últimos 5 minutos
            })),
            'first_attempt' => !empty($attempts) ? min($attempts) : null,
            'last_attempt' => !empty($attempts) ? max($attempts) : null,
            'identifier' => $identifier
        ];
    }
}
