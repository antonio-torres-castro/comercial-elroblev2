<?php

namespace App\Middlewares;

use App\Helpers\Security;
use App\Services\PermissionService;

class PermissionMiddleware
{
    private $permissionService;
    private $requiredPermissions;
    private $requiredMenus;
    private $requireAllPermissions;
    private $requireAllMenus;
    private $adminOnly;

    public function __construct()
    {
        $this->permissionService = new PermissionService();
        $this->requiredPermissions = [];
        $this->requiredMenus = [];
        $this->requireAllPermissions = true;
        $this->requireAllMenus = true;
        $this->adminOnly = false;
    }

    /**
     * Crear middleware que requiere permisos específicos
     */
    public static function requirePermission(string ...$permissions): self
    {
        $middleware = new self();
        $middleware->requiredPermissions = $permissions;
        return $middleware;
    }

    /**
     * Crear middleware que requiere acceso a menús específicos
     */
    public static function requireMenu(string ...$menus): self
    {
        $middleware = new self();
        $middleware->requiredMenus = $menus;
        return $middleware;
    }

    /**
     * Crear middleware que requiere permisos y menús
     */
    public static function requirePermissionAndMenu(array $permissions, array $menus): self
    {
        $middleware = new self();
        $middleware->requiredPermissions = $permissions;
        $middleware->requiredMenus = $menus;
        return $middleware;
    }

    /**
     * Crear middleware solo para administradores
     */
    public static function adminOnly(): self
    {
        $middleware = new self();
        $middleware->adminOnly = true;
        return $middleware;
    }

    /**
     * Configurar si se requieren TODOS los permisos (AND) o solo uno (OR)
     */
    public function requireAll(bool $requireAll = true): self
    {
        $this->requireAllPermissions = $requireAll;
        return $this;
    }

    /**
     * Configurar si se requieren TODOS los menús (AND) o solo uno (OR)
     */
    public function requireAllMenus(bool $requireAll = true): self
    {
        $this->requireAllMenus = $requireAll;
        return $this;
    }

    /**
     * Manejar la verificación de permisos
     */
    public function handle(): void
    {
        // Verificar autenticación básica
        if (!Security::isAuthenticated()) {
            $this->redirectToLogin();
            return;
        }

        $userId = $_SESSION['user_id'];

        // Si es solo para admin
        if ($this->adminOnly) {
            if (!$this->permissionService->isAdmin($userId)) {
                $this->denyAccess('Esta acción requiere privilegios de administrador.');
                return;
            }
            return; // Admin aprobado
        }

        // Verificar permisos específicos
        if (!empty($this->requiredPermissions)) {
            $hasPermission = $this->requireAllPermissions
                ? $this->permissionService->hasAllPermissions($userId, $this->requiredPermissions)
                : $this->permissionService->hasAnyPermission($userId, $this->requiredPermissions);

            if (!$hasPermission) {
                $permissionsList = implode(', ', $this->requiredPermissions);
                $operator = $this->requireAllPermissions ? 'todos' : 'al menos uno de';
                $this->denyAccess("Se requiere {$operator} los siguientes permisos: {$permissionsList}");
                return;
            }
        }

        // Verificar acceso a menús
        if (!empty($this->requiredMenus)) {
            $hasMenuAccess = true;

            if ($this->requireAllMenus) {
                foreach ($this->requiredMenus as $menu) {
                    if (!$this->permissionService->hasMenuAccess($userId, $menu)) {
                        $hasMenuAccess = false;
                        break;
                    }
                }
            } else {
                $hasMenuAccess = false;
                foreach ($this->requiredMenus as $menu) {
                    if ($this->permissionService->hasMenuAccess($userId, $menu)) {
                        $hasMenuAccess = true;
                        break;
                    }
                }
            }

            if (!$hasMenuAccess) {
                $menusList = implode(', ', $this->requiredMenus);
                $operator = $this->requireAllMenus ? 'todos' : 'al menos uno de';
                $this->denyAccess("Se requiere acceso a {$operator} los siguientes menús: {$menusList}");
                return;
            }
        }

        // Si llegamos aquí, el acceso está aprobado
        // Actualizar última actividad
        $_SESSION['last_activity'] = time();
    }

    /**
     * Verificar permiso específico (método estático para uso rápido)
     */
    public static function check(string $permission): void
    {
        $middleware = self::requirePermission($permission);
        $middleware->handle();
    }

    /**
     * Verificar acceso a menú específico (método estático para uso rápido)
     */
    public static function checkMenu(string $menu): void
    {
        $middleware = self::requireMenu($menu);
        $middleware->handle();
    }

    /**
     * Verificar si es admin (método estático para uso rápido)
     */
    public static function checkAdmin(): void
    {
        $middleware = self::adminOnly();
        $middleware->handle();
    }

    /**
     * Obtener instancia del servicio de permisos para uso externo
     */
    public static function getPermissionService(): PermissionService
    {
        return new PermissionService();
    }

    // ============ MÉTODOS PRIVADOS ============

    /**
     * Redirigir al login
     */
    private function redirectToLogin(): void
    {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        Security::redirect('/login');
    }

    /**
     * Denegar acceso con mensaje personalizado
     */
    private function denyAccess(string $message = 'Acceso denegado.'): void
    {
        // Log del intento de acceso no autorizado
        error_log("Acceso denegado para usuario {$_SESSION['username']} (ID: {$_SESSION['user_id']}): {$message}");

        http_response_code(403);

        // Mostrar página de error o JSON según el tipo de petición
        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'error' => true,
                'message' => $message,
                'code' => 403
            ]);
        } else {
            $this->showAccessDeniedPage($message);
        }

        exit;
    }

    /**
     * Verificar si es una petición AJAX
     */
    private function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Mostrar página de acceso denegado
     */
    private function showAccessDeniedPage(string $message): void
    {
        $userName = htmlspecialchars($_SESSION['nombre_completo'] ?? $_SESSION['username'] ?? 'Usuario');
        $userRole = htmlspecialchars($_SESSION['user_role'] ?? 'Sin rol');

        echo <<<HTML
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Acceso Denegado - SETAP</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
            <style>
                body { 
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                }
                .error-container {
                    background: rgba(255, 255, 255, 0.95);
                    border-radius: 15px;
                    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
                    padding: 3rem;
                    text-align: center;
                    max-width: 500px;
                    margin: 0 auto;
                }
                .error-icon {
                    font-size: 4rem;
                    color: #dc3545;
                    margin-bottom: 1rem;
                }
                .error-code {
                    font-size: 6rem;
                    font-weight: bold;
                    color: #6c757d;
                    line-height: 1;
                    margin-bottom: 0.5rem;
                }
                .back-btn {
                    margin-top: 2rem;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="error-container">
                    <i class="fas fa-shield-alt error-icon"></i>
                    <div class="error-code">403</div>
                    <h2 class="mb-3">Acceso Denegado</h2>
                    <p class="text-muted mb-3">{$message}</p>
                    
                    <div class="alert alert-info">
                        <strong>Usuario:</strong> {$userName}<br>
                        <strong>Rol:</strong> {$userRole}
                    </div>
                    
                    <p class="small text-muted">
                        Si crees que deberías tener acceso a esta función, contacta al administrador del sistema.
                    </p>
                    
                    <div class="back-btn">
                        <a href="/dashboard" class="btn btn-primary me-2">
                            <i class="fas fa-home"></i> Ir al Dashboard
                        </a>
                        <button onclick="history.back()" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Volver Atrás
                        </button>
                    </div>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }
}
