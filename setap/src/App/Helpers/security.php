<?php

namespace App\Helpers;

use App\Config\AppConfig;
use App\Services\PermissionService;

class Security
{
    private static $permissionService = null;
    private static $rateLimitAttempts = [];

    // ============ MÉTODOS EXISTENTES (MANTENIDOS) ============

    public static function sanitizeInput(string $input): string
    {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }

    public static function generateCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateCsrfToken(string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function redirect(string $url): void
    {
        header("Location: $url");
        exit;
    }

    public static function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']) && isset($_SESSION['last_activity']) &&
            (time() - $_SESSION['last_activity'] < AppConfig::get('session_lifetime', 3600));
    }

    public static function requireAuth(): void
    {
        if (!self::isAuthenticated()) {
            $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
            self::redirect(AppConfig::get('app_url') . '/login');
        }
        // Actualizar el tiempo de última actividad
        $_SESSION['last_activity'] = time();
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function validatePassword(string $password): bool
    {
        $minLength = AppConfig::get('password_min_length', 8);
        return strlen($password) >= $minLength;
    }

    // ============ NUEVOS MÉTODOS DE PERMISOS ============

    /**
     * Verificar si el usuario actual tiene un permiso específico
     */
    public static function hasPermission(string $permission): bool
    {
        if (!self::isAuthenticated()) {
            return false;
        }

        return self::getPermissionService()->currentUserHasPermission($permission);
    }

    /**
     * Verificar si el usuario actual tiene acceso a un menú
     */
    public static function hasMenuAccess(string $menu): bool
    {
        if (!self::isAuthenticated()) {
            return false;
        }

        return self::getPermissionService()->currentUserHasMenuAccess($menu);
    }

    /**
     * Verificar si el usuario actual es administrador
     */
    public static function isAdmin(): bool
    {
        if (!self::isAuthenticated()) {
            return false;
        }

        return self::getPermissionService()->currentUserIsAdmin();
    }

    /**
     * Requerir permiso específico (redirige si no lo tiene)
     */
    public static function requirePermission(string $permission): void
    {
        self::requireAuth();

        if (!self::hasPermission($permission)) {
            self::denyAccess("Se requiere el permiso: {$permission}");
        }
    }

    /**
     * Requerir acceso a menú específico
     */
    public static function requireMenu(string $menu): void
    {
        self::requireAuth();

        if (!self::hasMenuAccess($menu)) {
            self::denyAccess("Se requiere acceso al menú: {$menu}");
        }
    }

    /**
     * Requerir privilegios de administrador
     */
    public static function requireAdmin(): void
    {
        self::requireAuth();

        if (!self::isAdmin()) {
            self::denyAccess("Se requieren privilegios de administrador");
        }
    }

    /**
     * Verificar múltiples permisos (AND)
     */
    public static function hasAllPermissions(array $permissions): bool
    {
        if (!self::isAuthenticated()) {
            return false;
        }

        foreach ($permissions as $permission) {
            if (!self::hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Verificar múltiples permisos (OR)
     */
    public static function hasAnyPermission(array $permissions): bool
    {
        if (!self::isAuthenticated()) {
            return false;
        }

        foreach ($permissions as $permission) {
            if (self::hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    // ============ NUEVAS VALIDACIONES AVANZADAS ============

    /**
     * Validar password con criterios avanzados
     */
    public static function validatePasswordStrength(string $password): array
    {
        $errors = [];
        $minLength = AppConfig::get('password_min_length', 8);

        if (strlen($password) < $minLength) {
            $errors[] = "La contraseña debe tener al menos {$minLength} caracteres";
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "La contraseña debe contener al menos una letra mayúscula";
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "La contraseña debe contener al menos una letra minúscula";
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "La contraseña debe contener al menos un número";
        }

        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = "La contraseña debe contener al menos un carácter especial";
        }

        return $errors;
    }

    /**
     * Validar RUT chileno
     */
    public static function validateRut(string $rut): bool
    {
        $rut = preg_replace('/[^0-9kK]/', '', $rut);

        if (strlen($rut) < 8 || strlen($rut) > 9) {
            return false;
        }

        $dv = strtoupper(substr($rut, -1));
        $numero = substr($rut, 0, -1);

        $suma = 0;
        $multiplicador = 2;

        for ($i = strlen($numero) - 1; $i >= 0; $i--) {
            $suma += $numero[$i] * $multiplicador;
            $multiplicador = $multiplicador == 7 ? 2 : $multiplicador + 1;
        }

        $resto = $suma % 11;
        $dvCalculado = $resto == 0 ? '0' : ($resto == 1 ? 'K' : (11 - $resto));

        return $dv == $dvCalculado;
    }

    /**
     * Validar email con verificación avanzada
     */
    public static function validateEmail(string $email): bool
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // Verificar que no tenga caracteres peligrosos
        if (preg_match('/[<>"\']/', $email)) {
            return false;
        }

        return true;
    }

    /**
     * Sanitizar array de datos
     */
    public static function sanitizeArray(array $data): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[self::sanitizeInput($key)] = self::sanitizeArray($value);
            } else {
                $sanitized[self::sanitizeInput($key)] = self::sanitizeInput($value);
            }
        }
        return $sanitized;
    }

    /**
     * Sanitizar para SQL (aunque se use PDO, es una capa extra)
     */
    public static function sanitizeForSql(string $input): string
    {
        return addslashes(self::sanitizeInput($input));
    }

    // ============ NUEVOS MÉTODOS DE SEGURIDAD ============

    /**
     * Generar token aleatorio seguro
     */
    public static function generateSecureToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Generar nonce para CSP
     */
    public static function generateNonce(): string
    {
        if (empty($_SESSION['csp_nonce'])) {
            $_SESSION['csp_nonce'] = base64_encode(random_bytes(16));
        }
        return $_SESSION['csp_nonce'];
    }

    /**
     * Rate limiting básico por IP
     */
    public static function checkRateLimit(string $action, int $maxAttempts = 5, int $timeWindow = 300): bool
    {
        $ip = self::getClientIp();
        $key = "{$action}_{$ip}";
        $now = time();

        if (!isset(self::$rateLimitAttempts[$key])) {
            self::$rateLimitAttempts[$key] = [];
        }

        // Limpiar intentos antiguos
        self::$rateLimitAttempts[$key] = array_filter(
            self::$rateLimitAttempts[$key],
            fn($timestamp) => ($now - $timestamp) < $timeWindow
        );

        // Verificar límite
        if (count(self::$rateLimitAttempts[$key]) >= $maxAttempts) {
            return false;
        }

        // Registrar intento
        self::$rateLimitAttempts[$key][] = $now;
        return true;
    }

    /**
     * Obtener IP del cliente (considerando proxies)
     */
    public static function getClientIp(): string
    {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = trim($_SERVER[$key]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * Verificar si la petición viene de HTTPS
     */
    public static function isSecureConnection(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            $_SERVER['SERVER_PORT'] == 443 ||
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    }

    /**
     * Forzar conexión HTTPS
     */
    public static function requireHttps(): void
    {
        if (!self::isSecureConnection() && AppConfig::get('force_https', false)) {
            $redirectUrl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            header("Location: $redirectUrl", true, 301);
            exit;
        }
    }

    /**
     * Generar headers de seguridad
     */
    public static function setSecurityHeaders(): void
    {
        $nonce = self::generateNonce();

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

        if (self::isSecureConnection()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }

        $csp = "default-src 'self'; " .
            "script-src 'self' 'nonce-{$nonce}' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
            "img-src 'self' data: https:; " .
            "font-src 'self' https://cdnjs.cloudflare.com; " .
            "connect-src 'self'; " .
            "frame-ancestors 'none';";

        header("Content-Security-Policy: {$csp}");
    }

    // ============ MÉTODOS DE LOGGING Y AUDITORÍA ============

    /**
     * Log de evento de seguridad
     */
    public static function logSecurityEvent(string $event, array $context = []): void
    {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'user_id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? null,
            'ip' => self::getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'context' => $context
        ];

        error_log('SECURITY_EVENT: ' . json_encode($logData));
    }

    /**
     * Log de intento de acceso no autorizado
     */
    public static function logUnauthorizedAccess(string $resource, string $reason = ''): void
    {
        self::logSecurityEvent('unauthorized_access', [
            'resource' => $resource,
            'reason' => $reason,
            'requested_url' => $_SERVER['REQUEST_URI'] ?? null
        ]);
    }

    // ============ MÉTODOS PRIVADOS/UTILITARIOS ============

    /**
     * Obtener instancia del servicio de permisos
     */
    private static function getPermissionService(): PermissionService
    {
        if (self::$permissionService === null) {
            self::$permissionService = new PermissionService();
        }
        return self::$permissionService;
    }

    /**
     * Denegar acceso con logging
     */
    private static function denyAccess(string $message): void
    {
        self::logUnauthorizedAccess($_SERVER['REQUEST_URI'] ?? 'unknown', $message);

        http_response_code(403);
        echo json_encode([
            'error' => true,
            'message' => $message,
            'code' => 403
        ]);
        exit;
    }

    /**
     * Verificar si es una petición AJAX
     */
    public static function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Obtener información del usuario actual
     */
    public static function getCurrentUserInfo(): array
    {
        if (!self::isAuthenticated()) {
            return [];
        }

        return [
            'id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? null,
            'email' => $_SESSION['email'] ?? null,
            'role' => $_SESSION['user_role'] ?? null,
            'nombre_completo' => $_SESSION['nombre_completo'] ?? null,
            'rut' => $_SESSION['rut'] ?? null
        ];
    }
}
