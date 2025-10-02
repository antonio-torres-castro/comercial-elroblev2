<?php

namespace App\Controllers;

use App\Services\PermissionService;
use App\Middlewares\AuthMiddleware;
use App\Helpers\Security;
use Exception;

class HomeController
{
    private $permissionService;

    public function __construct()
    {
        // Verificar autenticaci�n
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
            
            // Obtener men�s accesibles para el usuario
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

    private function getHomeStats(array $user): array
    {
        // Estad�sticas b�sicas por defecto
        $stats = [
            'total_usuarios' => 0,
            'total_proyectos' => 0,
            'proyectos_activos' => 0,
            'tareas_pendientes' => 0
        ];
        
        try {
            // Solo mostrar estad�sticas si el usuario tiene permisos
            if (Security::hasPermission('Read') || Security::hasPermission('All')) {
                $stats = $this->calculateStats();
            }
            
        } catch (Exception $e) {
            error_log("Error calculando estad�sticas: " . $e->getMessage());
        }
        
        return $stats;
    }

    private function calculateStats(): array
    {
        // Implementaci�n b�sica de estad�sticas
        // Puedes expandir esto seg�n tus necesidades
        return [
            'total_usuarios' => 0,
            'total_proyectos' => 0,
            'proyectos_activos' => 0,
            'tareas_pendientes' => 0
        ];
    }
}