<?php

namespace App\Middlewares;

use App\Helpers\Security;

class PermissionMiddleware
{
    private $requiredPermission;

    public function __construct(string $requiredPermission)
    {
        $this->requiredPermission = $requiredPermission;
    }

    public function handle(): void
    {
        if (!Security::isAuthenticated()) {
            Security::redirect('/login');
        }

        $userRole = $_SESSION['user_role'];

        // Verificar permisos basados en el rol
        if (!$this->hasPermission($userRole)) {
            http_response_code(403);
            echo "Acceso denegado. No tienes permisos para esta acción.";
            exit;
        }
    }

    private function hasPermission($userRole): bool
    {
        // Lógica simple de permisos (mejorar con base de datos después)
        $permissions = [
            'admin' => ['admin', 'manage_users'],
            'planner' => ['planner', 'manage_tasks'],
            'supervisor' => ['supervisor', 'approve_tasks'],
            'executor' => ['executor', 'execute_tasks'],
            'client' => ['client', 'view_reports']
        ];

        return in_array($this->requiredPermission, $permissions[$userRole] ?? []);
    }
}
