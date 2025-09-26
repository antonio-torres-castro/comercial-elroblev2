<?php

namespace App\Helpers;

use App\Config\AppConfig;
use App\Services\PermissionService;

class Security
{
    private static $permissionService = null;
    private static $rateLimitAttempts = [];

    // ============ MÉTODOS BÁSICOS ============

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
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    // ============ NUEVOS MÉTODOS DE PERMISOS ============

    /**
     * Verificar si el usuario logueado tiene un permiso
     */
    public static function hasPermission(string $permission): bool
    {
        if (!self::isAuthenticated()) {
            return false;
        }

        if (self::$permissionService === null) {
            self::$permissionService = new PermissionService();
        }

        return self::$permissionService->hasPermission($_SESSION['user_id'], $permission);
    }

    /**
     * Verificar si el usuario logueado tiene acceso a un menú
     */
    public static function hasMenuAccess(string $menuName): bool
    {
        if (!self::isAuthenticated()) {
            return false;
        }

        if (self::$permissionService === null) {
            self::$permissionService = new PermissionService();
        }

        return self::$permissionService->hasMenuAccess($_SESSION['user_id'], $menuName);
    }

    /**
     * Obtener menús del usuario logueado
     */
    public static function getUserMenus(): array
    {
        if (!self::isAuthenticated()) {
            return [];
        }

        if (self::$permissionService === null) {
            self::$permissionService = new PermissionService();
        }

        return self::$permissionService->getUserMenus($_SESSION['user_id']);
    }

    // ============ VALIDACIONES AVANZADAS ============

    /**
     * Validar RUT chileno
     */
    public static function validateRut(string $rut): bool
    {
        $rut = preg_replace('/[^0-9kK]/', '', $rut);

        if (strlen($rut) < 2) {
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
        $dvCalculado = $resto == 0 ? '0' : ($resto == 1 ? 'K' : (string)(11 - $resto));

        return $dv === $dvCalculado;
    }

    /**
     * Validar fortaleza de contraseña
     */
    public static function validatePasswordStrength(string $password): array
    {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = 'La contraseña debe tener al menos 8 caracteres';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos una mayúscula';
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos una minúscula';
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos un número';
        }

        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos un carácter especial';
        }

        return $errors;
    }

    // ============ RATE LIMITING ============

    /**
     * Verificar rate limiting
     */
    public static function checkRateLimit(string $action, int $maxAttempts = 5, int $timeWindow = 300): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = $action . '_' . $ip;
        $now = time();

        if (!isset(self::$rateLimitAttempts[$key])) {
            self::$rateLimitAttempts[$key] = [];
        }

        // Limpiar intentos antiguos
        self::$rateLimitAttempts[$key] = array_filter(
            self::$rateLimitAttempts[$key],
            function ($timestamp) use ($now, $timeWindow) {
                return ($now - $timestamp) <= $timeWindow;
            }
        );

        // Verificar si excede el límite
        if (count(self::$rateLimitAttempts[$key]) >= $maxAttempts) {
            return false;
        }

        // Registrar este intento
        self::$rateLimitAttempts[$key][] = $now;
        return true;
    }

    // ============ HEADERS DE SEGURIDAD ============

    /**
     * Configurar headers de seguridad
     */
    public static function setSecurityHeaders(): void
    {
        // Content Security Policy
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data: https:; font-src 'self' https://cdn.jsdelivr.net;");

        // HSTS
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

        // X-Frame-Options
        header('X-Frame-Options: DENY');

        // X-Content-Type-Options
        header('X-Content-Type-Options: nosniff');

        // X-XSS-Protection
        header('X-XSS-Protection: 1; mode=block');

        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }

    /**
     * Forzar HTTPS
     */
    public static function requireHttps(): void
    {
        if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
            $redirectURL = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            header("Location: $redirectURL");
            exit;
        }
    }

    /**
     * Validar email
     */
    public static function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Generar password seguro
     */
    public static function generateSecurePassword(int $length = 12): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        return substr(str_shuffle(str_repeat($chars, ceil($length / strlen($chars)))), 0, $length);
    }


    /**
     * Registrar evento de seguridad
     */
    public static function logSecurityEvent(string $event, array $data = []): void
    {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'user_id' => $_SESSION['user_id'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'data' => $data
        ];

        error_log("SECURITY_EVENT: " . json_encode($logData));
    }
}
