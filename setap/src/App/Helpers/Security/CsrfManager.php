<?php

namespace App\Helpers\Security;

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
            session_start();
        }

        $token = bin2hex(random_bytes(32));
        
        $_SESSION[self::$tokenKey] = [
            'token' => $token,
            'expires' => time() + self::$tokenExpiry
        ];

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

        // Verificar que existe el token en sesión
        if (!isset($_SESSION[self::$tokenKey])) {
            return false;
        }

        // Verificar que no ha expirado
        if (self::isTokenExpired()) {
            self::clearToken();
            return false;
        }

        // Comparar tokens usando hash_equals para evitar timing attacks
        return hash_equals($_SESSION[self::$tokenKey]['token'], $token);
    }

    /**
     * Verificar si el token ha expirado
     */
    private static function isTokenExpired(): bool
    {
        if (!isset($_SESSION[self::$tokenKey]['expires'])) {
            return true;
        }

        return time() > $_SESSION[self::$tokenKey]['expires'];
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
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Token CSRF inválido']);
            } else {
                echo 'Token CSRF inválido';
            }
            
            exit;
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
}
