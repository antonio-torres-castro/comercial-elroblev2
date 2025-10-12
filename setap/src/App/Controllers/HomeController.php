<?php
namespace App\Controllers;
use App\Services\PermissionService;
use App\Middlewares\AuthMiddleware;
use App\Helpers\Security;
use App\Constants\AppConstants;
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
                $this->redirectToLogin();
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
            echo AppConstants::ERROR_INTERNAL_SERVER;
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
        try {
            $db = \App\Config\Database::getInstance();
            
            // 1. Total usuarios (excluir eliminados: estado_tipo_id != 4)
            $stmt = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE estado_tipo_id != 4");
            $stmt->execute();
            $totalUsuarios = $stmt->fetchColumn();
            
            // 2. Total proyectos (excluir eliminados: estado_tipo_id != 4)
            $stmt = $db->prepare("SELECT COUNT(*) FROM proyectos WHERE estado_tipo_id != 4");
            $stmt->execute();
            $totalProyectos = $stmt->fetchColumn();
            
            // 3. Proyectos activos (estado activo=2 o iniciado=5)
            $stmt = $db->prepare("SELECT COUNT(*) FROM proyectos WHERE estado_tipo_id IN (2, 5)");
            $stmt->execute();
            $proyectosActivos = $stmt->fetchColumn();
            
            // 4. Tareas pendientes (estado creado=1, activo=2, o iniciado=5)
            $stmt = $db->prepare("SELECT COUNT(*) FROM proyecto_tareas WHERE estado_tipo_id IN (1, 2, 5)");
            $stmt->execute();
            $tareasPendientes = $stmt->fetchColumn();
            
            return [
                'total_usuarios' => (int)$totalUsuarios,
                'total_proyectos' => (int)$totalProyectos,
                'proyectos_activos' => (int)$proyectosActivos,
                'tareas_pendientes' => (int)$tareasPendientes
            ];
            
        } catch (\Exception $e) {
            error_log("Error calculando estadísticas del dashboard: " . $e->getMessage());
            
            // Retornar valores por defecto en caso de error
            return [
                'total_usuarios' => 0,
                'total_proyectos' => 0,
                'proyectos_activos' => 0,
                'tareas_pendientes' => 0
            ];
        }
    }
}
