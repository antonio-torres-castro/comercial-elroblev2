<?php

namespace App\Helpers;

use App\Config\AppConfig;

class Security
{
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
}
