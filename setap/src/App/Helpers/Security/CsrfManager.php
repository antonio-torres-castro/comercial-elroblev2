<?php

namespace App\Helpers\Security;

use App\Services\CustomLogger;

/**
 * Gestor de tokens CSRF
 * Responsabilidad única: Gestionar protección CSRF
 */
class CsrfManager
{
    private static $tokenKey = 'csrf_token';
    private static $tokenExpiry = 3600; // 1 hora

    /**
     * Generar nuevo token CSRF
     */
    public static function generateToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Configuración de sesión para asegurar persistencia
            ini_set('session.gc_maxlifetime', 3600);
            ini_set('session.cookie_lifetime', 3600);
            ini_set('session.cookie_httponly', 1);
            
            session_start();
        }

        $token = bin2hex(random_bytes(32));

        $_SESSION[self::$tokenKey] = [
            'token' => $token,
            'expires' => time() + self::$tokenExpiry
        ];

        // Forzar guardado de sesión
        session_write_close();
        // Re-abrir sesión para continuar
        session_start();
        
        CustomLogger::debug("🔐 [CSRF] Generated new token: " . substr($token, 0, 10) . "...");
        CustomLogger::debug("🔐 [CSRF] Session ID: " . session_id());
        CustomLogger::debug("🔐 [CSRF] Session status: " . session_status());
        CustomLogger::debug("🔐 [CSRF] Token stored in session: " . isset($_SESSION[self::$tokenKey]) ? "YES" : "NO");

        return $token;
    }

    /**
     * Obtener token actual o generar uno nuevo
     */
    public static function getToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Si no existe token o ha expirado, generar uno nuevo
        if (!isset($_SESSION[self::$tokenKey]) || self::isTokenExpired()) {
            return self::generateToken();
        }

        return $_SESSION[self::$tokenKey]['token'];
    }

    /**
     * Validar token CSRF
     */
    public static function validateToken(string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        CustomLogger::debug("🔐 [CSRF] Validating token: " . substr($token, 0, 10) . "...");
        CustomLogger::debug("🔐 [CSRF] Current session ID: " . session_id());
        CustomLogger::debug("🔐 [CSRF] Session status: " . session_status());

        // Verificar que existe el token en sesión
        if (!isset($_SESSION[self::$tokenKey])) {
            CustomLogger::debug("🔐 [CSRF] No token found in session");
            CustomLogger::debug("🔐 [CSRF] Session keys: " . implode(', ', array_keys($_SESSION)));
            return false;
        }

        $sessionToken = $_SESSION[self::$tokenKey];
        CustomLogger::debug("🔐 [CSRF] Session token: " . substr($sessionToken['token'], 0, 10) . "...");
        CustomLogger::debug("🔐 [CSRF] Session token expiry: " . ($sessionToken['expires'] ?? 'NOT_SET'));

        // Verificar que no ha expirado
        if (self::isTokenExpired()) {
            CustomLogger::debug("🔐 [CSRF] Token expired");
            self::clearToken();
            return false;
        }

        // Comparar tokens usando hash_equals para evitar timing attacks
        $isValid = hash_equals($sessionToken['token'], $token);
        CustomLogger::debug("🔐 [CSRF] Token comparison result: " . ($isValid ? "VALID" : "INVALID"));

        return $isValid;
    }

    /**
     * Verificar si el token ha expirado
     */
    private static function isTokenExpired(): bool
    {
        if (!isset($_SESSION[self::$tokenKey]['expires'])) {
            CustomLogger::debug("🔐 [CSRF] Token expiry not set");
            return true;
        }

        $currentTime = time();
        $tokenExpiry = $_SESSION[self::$tokenKey]['expires'];
        $timeLeft = $tokenExpiry - $currentTime;
        
        CustomLogger::debug("🔐 [CSRF] Current time: $currentTime, Token expiry: $tokenExpiry, Time left: $timeLeft seconds");

        return $currentTime > $tokenExpiry;
    }

    /**
     * Limpiar token de la sesión
     */
    public static function clearToken(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        unset($_SESSION[self::$tokenKey]);
    }

    /**
     * Renderizar campo hidden con token CSRF
     */
    public static function renderField(): string
    {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Renderizar meta tag con token CSRF (para AJAX)
     */
    public static function renderMetaTag(): string
    {
        $token = self::getToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Obtener token para JavaScript
     */
    public static function getTokenForJs(): string
    {
        return json_encode(self::getToken());
    }

    /**
     * Validar token desde request
     */
    public static function validateFromRequest(): bool
    {
        // Buscar token en POST, GET o headers
        $token = $_POST['csrf_token'] ??
            $_GET['csrf_token'] ??
            $_SERVER['HTTP_X_CSRF_TOKEN'] ??
            '';

        return self::validateToken($token);
    }

    /**
     * Middleware para validar CSRF
     */
    public static function middleware(): void
    {
        // Solo validar en métodos que modifican datos
        $method = $_SERVER['REQUEST_METHOD'] ?? '';
        if (!in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            return;
        }

        if (!self::validateFromRequest()) {
            http_response_code(403);

            // Si es AJAX, devolver JSON
            $errorMessage = 'Token CSRF inválido';
            if (
                !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
            ) {
                self::sendCsrfError($errorMessage, 403);
            } else {
                http_response_code(403);
                echo $errorMessage;
                exit;
            }
        }
    }

    /**
     * Regenerar token (útil después de login)
     */
    public static function regenerateToken(): string
    {
        self::clearToken();
        return self::generateToken();
    }

    /**
     * Configurar tiempo de expiración del token
     */
    public static function setTokenExpiry(int $seconds): void
    {
        self::$tokenExpiry = $seconds;
    }

    /**
     * Obtener tiempo de expiración actual
     */
    public static function getTokenExpiry(): int
    {
        return self::$tokenExpiry;
    }

    /**
     * Verificar si hay un token válido en sesión
     */
    public static function hasValidToken(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION[self::$tokenKey]) && !self::isTokenExpired();
    }

    /**
     * Obtener información del token actual
     */
    public static function getTokenInfo(): ?array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION[self::$tokenKey])) {
            return null;
        }

        return [
            'token' => $_SESSION[self::$tokenKey]['token'],
            'expires' => $_SESSION[self::$tokenKey]['expires'],
            'expires_in' => $_SESSION[self::$tokenKey]['expires'] - time(),
            'is_expired' => self::isTokenExpired()
        ];
    }

    /**
     * Envía una respuesta JSON de error CSRF y termina la ejecución
     * @param string $message Mensaje de error
     * @param int $statusCode Código de estado HTTP
     */
    private static function sendCsrfError(string $message, int $statusCode = 403): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-cache, must-revalidate');
        
        echo json_encode([
            'success' => false,
            'error' => $message,
            'message' => $message,
            'csrf_error' => true
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
