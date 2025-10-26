<?php

namespace App\Helpers\Security;

use App\Helpers\Logger;
use App\Constants\AppConstants;
use App\Services\PermissionService;

/**
 * Helper de autenticación
 * Responsabilidad única: Gestionar estado de autenticación
 */
class AuthHelper
{
    private static $permissionService = null;

    /**
     * Verificar si el usuario está autenticado
     */
    public static function isAuthenticated(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Obtener ID del usuario autenticado
     */
    public static function getUserId(): ?int
    {
        if (!self::isAuthenticated()) {
            return null;
        }

        return (int)$_SESSION['user_id'];
    }

    /**
     * Alias para getUserId() - usado en Security facade
     */
    public static function getCurrentUserId(): ?int
    {
        return self::getUserId();
    }

    /**
     * Obtener datos del usuario autenticado
     */
    public static function getUser(): ?array
    {
        if (!self::isAuthenticated()) {
            return null;
        }

        return $_SESSION['user_data'] ?? null;
    }

    /**
     * Establecer usuario autenticado
     */
    public static function setUser(array $userData): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['user_data'] = $userData;
        $_SESSION['login_time'] = time();

        // Regenerar ID de sesión por seguridad
        session_regenerate_id(true);
    }

    /**
     * Cerrar sesión
     */
    public static function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Limpiar variables de sesión relacionadas con autenticación
        unset($_SESSION['user_id']);
        unset($_SESSION['user_data']);
        unset($_SESSION['login_time']);
        unset($_SESSION['permissions']);
        unset($_SESSION['menus']);

        // Regenerar ID de sesión
        session_regenerate_id(true);
    }

    /**
     * Verificar si el usuario tiene un permiso específico
     */
    public static function hasPermission(string $permission): bool
    {
        if (!self::isAuthenticated()) {
            return false;
        }

        if (self::$permissionService === null) {
            self::$permissionService = new PermissionService();
        }

        return self::$permissionService->hasPermission(self::getUserId(), $permission);
    }

    /**
     * Verificar si el usuario tiene acceso a un menú
     */
    public static function hasMenuAccess(string $menuName): bool
    {
        if (!self::isAuthenticated()) {
            return false;
        }

        if (self::$permissionService === null) {
            self::$permissionService = new PermissionService();
        }

        return self::$permissionService->hasMenuAccess(self::getUserId(), $menuName);
    }

    /**
     * Obtener menús del usuario autenticado
     */
    public static function getUserMenus(): array
    {
        if (!self::isAuthenticated()) {
            return [];
        }

        if (self::$permissionService === null) {
            self::$permissionService = new PermissionService();
        }

        return self::$permissionService->getUserMenus(self::getUserId());
    }

    /**
     * Verificar si el usuario es administrador
     */
    public static function isAdmin(): bool
    {
        $user = self::getUser();
        return $user && ($user['tipo_usuario'] === 'admin' || $user['usuario_tipo_id'] == 1);
    }

    /**
     * Verificar timeout de sesión
     */
    public static function checkSessionTimeout(int $timeoutMinutes = 120): bool
    {
        if (!self::isAuthenticated()) {
            return true; // Ya expirada
        }

        $loginTime = $_SESSION['login_time'] ?? 0;
        $currentTime = time();
        $timeoutSeconds = $timeoutMinutes * 60;

        return ($currentTime - $loginTime) > $timeoutSeconds;
    }

    /**
     * Renovar tiempo de sesión
     */
    public static function renewSession(): void
    {
        if (self::isAuthenticated()) {
            $_SESSION['login_time'] = time();
        }
    }

    /**
     * Middleware de autenticación
     */
    public static function requireAuth(): void
    {
        if (!self::isAuthenticated()) {
            // Si es AJAX, devolver error JSON
            if (self::isAjaxRequest()) {
                self::sendJsonError('No autorizado', 401);
            }

            // Redirigir a login
            self::redirectToLogin();
        }

        // Verificar timeout
        if (self::checkSessionTimeout()) {
            self::logout();

            if (self::isAjaxRequest()) {
                self::sendJsonError('Sesión expirada', 401);
            }

            self::redirectToLogin('Sesión expirada');
        }

        // Renovar sesión si está activa
        self::renewSession();
    }

    /**
     * Middleware de permisos
     */
    public static function requirePermission(string $permission): void
    {
        self::requireAuth();

        if (!self::hasPermission($permission)) {
            if (self::isAjaxRequest()) {
                self::sendJsonError('Acceso denegado', 403);
            } else {
                http_response_code(403);
                echo 'Acceso denegado';
                exit;
            }
        }
    }

    /**
     * Middleware de acceso a menú
     */
    public static function requireMenuAccess(string $menuName): void
    {
        self::requireAuth();

        if (!self::hasMenuAccess($menuName)) {
            if (self::isAjaxRequest()) {
                self::sendJsonError('Acceso denegado al menú', 403);
            } else {
                http_response_code(403);
                echo 'Acceso denegado';
                exit;
            }
        }
    }

    /**
     * Redirección general
     */
    public static function redirect(string $url): void
    {
        // Agregar el base path si no está presente
        if (strpos($url, '/setap/') !== 0) {
            $url = '/setap' . $url;
        }
        header("Location: $url");
        exit;
    }

    /**
     * Redirigir a login
     */
    public static function redirectToLogin(string $message = ''): void
    {
        $loginUrl = AppConstants::ROUTE_LOGIN;

        if (!empty($message)) {
            $loginUrl .= '?message=' . urlencode($message);
        }

        self::redirect($loginUrl);
    }

    /**
     * Verificar si es request AJAX
     */
    public static function isAjaxRequest(): bool
    {
        // Verificar header X-Requested-With (XMLHttpRequest y fetch con header manual)
        if (
            !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        ) {
            return true;
        }

        // Verificar Content-Type para requests fetch que esperan JSON
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';

        // Si el cliente espera JSON, probablemente es AJAX
        if (strpos($accept, 'application/json') !== false) {
            return true;
        }

        // Si es una request POST/PUT/DELETE sin redirect, probablemente es AJAX
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (
            in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH']) &&
            !isset($_POST['_redirect']) &&
            strpos($accept, 'text/html') === false
        ) {
            return true;
        }

        return false;
    }

    /**
     * Obtener información de la sesión actual
     */
    public static function getSessionInfo(): array
    {
        if (!self::isAuthenticated()) {
            return [];
        }

        return [
            'user_id' => self::getUserId(),
            'login_time' => $_SESSION['login_time'] ?? null,
            'session_duration' => time() - ($_SESSION['login_time'] ?? 0),
            'session_id' => session_id(),
            'is_admin' => self::isAdmin()
        ];
    }

    /**
     * Verificar si el usuario puede acceder a un recurso específico
     */
    public static function canAccess(string $resource, string $action = 'read'): bool
    {
        if (!self::isAuthenticated()) {
            return false;
        }

        // Los administradores pueden acceder a todo
        if (self::isAdmin()) {
            return true;
        }

        // Verificar permiso específico
        $permission = "{$resource}_{$action}";
        return self::hasPermission($permission);
    }

    /**
     * Registrar actividad del usuario
     */
    public static function logActivity(string $activity, array $details = []): void
    {
        if (!self::isAuthenticated()) {
            return;
        }

        $logData = [
            'user_id' => self::getUserId(),
            'activity' => $activity,
            'details' => $details,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s')
        ];

        Logger::error("USER_ACTIVITY: " . json_encode($logData));
    }

    /**
     * Envía una respuesta JSON de error y termina la ejecución
     * Helper para respuestas AJAX elegantes
     * @param string $message Mensaje de error
     * @param int $statusCode Código de estado HTTP
     */
    private static function sendJsonError(string $message, int $statusCode = 401): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-cache, must-revalidate');

        echo json_encode([
            'success' => false,
            'error' => $message,
            'message' => $message
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
