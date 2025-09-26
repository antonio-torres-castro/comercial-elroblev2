<?php

namespace App\Controllers;

use App\Services\PermissionService;
use App\Middlewares\AuthMiddleware;
use App\Helpers\Security;
use Exception;

class DashboardController
{
    private $permissionService;

    public function __construct()
    {
        // Verificar autenticación
        (new AuthMiddleware())->handle();
        
        $this->permissionService = new PermissionService();
    }

    public function index()
    {
        try {
            // Obtener usuario actual
            $currentUser = $this->getCurrentUser();
            
            if (!$currentUser) {
                Security::redirect('/login');
                return;
            }
            
            // Obtener menús accesibles para el usuario
            $menus = $this->permissionService->getUserMenus($currentUser['id']);
            
            // Datos para el dashboard
            $dashboardData = [
                'user' => $currentUser,
                'menus' => $menus,
                'stats' => $this->getDashboardStats($currentUser)
            ];
            
            require_once __DIR__ . '/../Views/dashboard.php';
            
        } catch (Exception $e) {
            error_log("Error en DashboardController::index: " . $e->getMessage());
            http_response_code(500);
            echo "Error interno del servidor";
        }
    }

    private function getCurrentUser(): ?array
    {
        if (!Security::isAuthenticated()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email' => $_SESSION['email'],
            'nombre_completo' => $_SESSION['nombre_completo'],
            'rol' => $_SESSION['rol'],
            'usuario_tipo_id' => $_SESSION['usuario_tipo_id']
        ];
    }

    private function getDashboardStats(array $user): array
    {
        // Estadísticas básicas por defecto
        $stats = [
            'total_usuarios' => 0,
            'total_proyectos' => 0,
            'proyectos_activos' => 0,
            'tareas_pendientes' => 0
        ];
        
        try {
            // Solo mostrar estadísticas si el usuario tiene permisos
            if (Security::hasPermission('view_statistics')) {
                $stats = $this->calculateStats();
            }
            
        } catch (Exception $e) {
            error_log("Error calculando estadísticas: " . $e->getMessage());
        }
        
        return $stats;
    }

    private function calculateStats(): array
    {
        // Implementación básica de estadísticas
        // Puedes expandir esto según tus necesidades
        return [
            'total_usuarios' => 0,
            'total_proyectos' => 0,
            'proyectos_activos' => 0,
            'tareas_pendientes' => 0
        ];
    }
}