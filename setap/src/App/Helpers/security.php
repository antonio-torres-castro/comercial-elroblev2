<?php

namespace App\Helpers;

use App\Config\AppConfig;
use App\Services\PermissionService;
use App\Helpers\Security\CsrfManager;
use App\Helpers\Security\AuthHelper;
use App\Helpers\Security\RateLimiter;
use App\Helpers\Security\SecurityValidator;

/**
 * Security Facade
 *
 * Esta clase actúa como una Fachada para los diferentes componentes de seguridad,
 * siguiendo el principio de responsabilidad única (SRP).
 * Delega las operaciones específicas a clases especializadas.
 */
class Security
{
    private static $permissionService = null;

    // ============ MÉTODOS BÁSICOS ============

    /**
     * Sanitizar entrada de datos
     * Delegado a SecurityValidator
     */
    public static function sanitizeInput(string $input): string
    {
        return SecurityValidator::sanitizeInput($input);
    }

    /**
     * Generar token CSRF
     * Delegado a CsrfManager
     */
    public static function generateCsrfToken(): string
    {
        return CsrfManager::generateToken();
    }

    /**
     * Validar token CSRF
     * Delegado a CsrfManager
     */
    public static function validateCsrfToken(string $token): bool
    {
        return CsrfManager::validateToken($token);
    }

    /**
     * Renderizar campo CSRF oculto
     * Delegado a CsrfManager
     */
    public static function renderCsrfField(): void
    {
        CsrfManager::renderField();
    }

    /**
     * Redireccionar a una URL
     * Delegado a AuthHelper
     */
    public static function redirect(string $url): void
    {
        AuthHelper::redirect($url);
    }

    /**
     * Verificar si el usuario está autenticado
     * Delegado a AuthHelper
     */
    public static function isAuthenticated(): bool
    {
        return AuthHelper::isAuthenticated();
    }

    // ============ MÉTODOS DE PERMISOS ============
    // Estos métodos mantienen su lógica actual ya que trabajan con PermissionService
    // que es un servicio específico del dominio de permisos

    /**
     * Verificar si el usuario logueado tiene un permiso
     * Delegado a AuthHelper para autenticación y PermissionService para permisos
     */
    public static function hasPermission(string $permission): bool
    {
        if (!AuthHelper::isAuthenticated()) {
            return false;
        }

        if (self::$permissionService === null) {
            self::$permissionService = new PermissionService();
        }

        return self::$permissionService->hasPermission(AuthHelper::getCurrentUserId(), $permission);
    }

    /**
     * Verificar si el usuario logueado tiene acceso a un menú
     * Delegado a AuthHelper para autenticación y PermissionService para permisos
     */
    public static function hasMenuAccess(string $menuName): bool
    {
        if (!AuthHelper::isAuthenticated()) {
            return false;
        }

        if (self::$permissionService === null) {
            self::$permissionService = new PermissionService();
        }

        return self::$permissionService->hasMenuAccess(AuthHelper::getCurrentUserId(), $menuName);
    }

    /**
     * Obtener menús del usuario logueado
     * Delegado a AuthHelper para autenticación y PermissionService para permisos
     */
    public static function getUserMenus(): array
    {
        if (!AuthHelper::isAuthenticated()) {
            return [];
        }

        if (self::$permissionService === null) {
            self::$permissionService = new PermissionService();
        }

        return self::$permissionService->getUserMenus(AuthHelper::getCurrentUserId());
    }

    // ============ VALIDACIONES AVANZADAS ============
    // Todas delegadas a SecurityValidator

    /**
     * Validar RUT chileno
     * Delegado a SecurityValidator
     */
    public static function validateRut(string $rut): bool
    {
        return SecurityValidator::validateRut($rut);
    }

    /**
     * Validar fortaleza de contraseña
     * Delegado a SecurityValidator
     */
    public static function validatePasswordStrength(string $password): array
    {
        return SecurityValidator::validatePasswordStrength($password);
    }

    // ============ RATE LIMITING ============

    /**
     * Verificar rate limiting
     * Delegado a RateLimiter
     */
    public static function checkRateLimit(string $action, int $maxAttempts = 5, int $timeWindow = 300): bool
    {
        return RateLimiter::checkLimit($action, $maxAttempts, $timeWindow);
    }

    // ============ HEADERS DE SEGURIDAD ============

    /**
     * Configurar headers de seguridad
     * Delegado a SecurityValidator
     */
    public static function setSecurityHeaders(): void
    {
        SecurityValidator::setSecurityHeaders();
    }

    /**
     * Forzar HTTPS
     * Delegado a SecurityValidator
     */
    public static function requireHttps(): void
    {
        SecurityValidator::requireHttps();
    }

    /**
     * Validar email
     * Delegado a SecurityValidator
     */
    public static function validateEmail(string $email): bool
    {
        return SecurityValidator::validateEmail($email);
    }

    /**
     * Generar password seguro
     * Delegado a SecurityValidator
     */
    public static function generateSecurePassword(int $length = 12): string
    {
        return SecurityValidator::generateSecurePassword($length);
    }

    /**
     * Registrar evento de seguridad
     * Delegado a SecurityValidator
     */
    public static function logSecurityEvent(string $event, array $data = []): void
    {
        SecurityValidator::logSecurityEvent($event, $data);
    }
}
