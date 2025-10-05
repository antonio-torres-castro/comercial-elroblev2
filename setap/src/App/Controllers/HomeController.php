<?php

namespace App\Controllers;

use App\Services\PermissionService;
use App\Middlewares\AuthMiddleware;
use App\Helpers\Security;
use Exception;

class HomeController extends BaseController
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
            
            // Datos para el home
            $homeData = [
                'user' => $currentUser,
                'menus' => $menus,
                'stats' => $this->getHomeStats($currentUser)
            ];
            
            require_once __DIR__ . '/../Views/home.php';
            
        } catch (Exception $e) {
            error_log("Error en HomeController::index: " . $e->getMessage());
            http_response_code(500);
            echo "Error interno del servidor";
        }
    }



    private function getHomeStats(array $user): array
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
            if (Security::hasPermission('Read') || Security::hasPermission('All')) {
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
