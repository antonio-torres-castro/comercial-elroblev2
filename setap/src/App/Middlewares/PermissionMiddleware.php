<?php

namespace App\Middlewares;

use App\Services\AuthService;
use App\Services\PermissionService;

class PermissionMiddleware
{
    private $requiredPermissions;
    private $authService;
    private $permissionService;

    public function __construct(array $permissions = [])
    {
        $this->requiredPermissions = $permissions;
        $this->authService = new AuthService();
        $this->permissionService = new PermissionService();
    }

    /**
     * Método estático para crear instancia con permisos requeridos
     */
    public static function requirePermission(...$permissions): self
    {
        return new self($permissions);
    }

    /**
     * Manejar la verificación de permisos
     */
    public function handle(): void
    {
        // Verificar autenticación primero
        if (!$this->authService->isAuthenticated()) {
            $this->redirectToLogin();
            return;
        }

        // Si no hay permisos requeridos, solo verificar autenticación
        if (empty($this->requiredPermissions)) {
            return;
        }

        $currentUser = $this->authService->getCurrentUser();
        if (!$currentUser) {
            $this->redirectToLogin();
            return;
        }

        // Verificar permisos
        $hasPermission = false;
        foreach ($this->requiredPermissions as $permission) {
            if ($this->permissionService->hasPermission($currentUser['id'], $permission)) {
                $hasPermission = true;
                break;
            }
        }

        if (!$hasPermission) {
            $this->accessDenied();
            return;
        }
    }

    /**
     * Redirigir al login
     */
    private function redirectToLogin(): void
    {
        if ($this->isAjaxRequest()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'No autenticado', 'redirect' => '/login']);
        } else {
            header('Location: /login');
        }
        exit;
    }

    /**
     * Mostrar error de acceso denegado
     */
    private function accessDenied(): void
    {
        if ($this->isAjaxRequest()) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Acceso denegado']);
        } else {
            http_response_code(403);
            echo $this->getAccessDeniedPage();
        }
        exit;
    }

    /**
     * Verificar si es una petición AJAX
     */
    private function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) 
               && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Página de acceso denegado
     */
    private function getAccessDeniedPage(): string
    {
        return '
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Acceso Denegado - SETAP</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body>
            <div class="container mt-5">
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="card border-danger">
                            <div class="card-header bg-danger text-white">
                                <h4 class="mb-0">Acceso Denegado</h4>
                            </div>
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <i class="bi bi-shield-exclamation" style="font-size: 3rem; color: #dc3545;"></i>
                                </div>
                                <p class="mb-3">No tienes permisos para acceder a esta sección.</p>
                                <a href="/dashboard" class="btn btn-primary">Volver al Dashboard</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>';
    }
}