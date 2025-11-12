<?php

namespace App\Controllers;

use App\Services\PermissionService;
use App\Middlewares\AuthMiddleware;
use App\Helpers\Security;
use App\Helpers\Logger;
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
            Logger::error("HomeController::index: " . $e->getMessage());
            http_response_code(500);
            echo AppConstants::ERROR_INTERNAL_SERVER;
        }
    }

    private function getHomeStats(?array $user = []): array
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
            if (Security::hasPermission('Read')) {
                $stats = $this->calculateStats($user);
            }
        } catch (Exception $e) {
            Logger::error("calculando estadísticas: " . $e->getMessage());
        }

        return $stats;
    }

    private function calculateStats(?array $cUser = []): array
    {
        try {
            $db = \App\Config\Database::getInstance();
            $params = [];

            $cliente_id = $cUser['cliente_id'] ?? 0;

            $sqlAnd = $cliente_id > 0 ? " and cliente_id = ?" : "";
            if ($cliente_id > 0) {
                $params[] =  $cliente_id;
            }

            // 1. Total usuarios activos
            $stmt = $db->prepare("SELECT COUNT(1) FROM usuarios WHERE estado_tipo_id = 2" . $sqlAnd);
            $stmt->execute($params);
            $totalUsuarios = $stmt->fetchColumn();

            // 2. Total proyectos
            $stmt = $db->prepare("SELECT COUNT(1) FROM proyectos WHERE estado_tipo_id != 4" . $sqlAnd);
            $stmt->execute($params);
            $totalProyectos = $stmt->fetchColumn();

            // 3. Proyectos activos (estado activo)
            $stmt = $db->prepare("SELECT COUNT(1) FROM proyectos WHERE fecha_inicio < curdate() and fecha_fin > curdate() and estado_tipo_id IN (2, 5)" . $sqlAnd);
            $stmt->execute($params);
            $proyectosActivos = $stmt->fetchColumn();

            // 4. Tareas pendientes (estado activo=2, o iniciado=5, rechazado=7).
            $sqlAnd = $cliente_id > 0 ? " and pt.cliente_id = ?" : "";
            $stmt = $db->prepare("SELECT count(1) 
                                  FROM proyecto_tareas pt 
                                  Inner Join proyectos p on p.id = pt.proyecto_id 
                                  WHERE p.fecha_inicio < curdate() and p.fecha_fin > curdate()
                                  and pt.estado_tipo_id IN (2, 5, 6, 7)" . $sqlAnd);
            $stmt->execute($params);
            $tareasPendientes = $stmt->fetchColumn();

            return [
                'total_usuarios' => (int)$totalUsuarios,
                'total_proyectos' => (int)$totalProyectos,
                'proyectos_activos' => (int)$proyectosActivos,
                'tareas_pendientes' => (int)$tareasPendientes
            ];
        } catch (\Exception $e) {
            Logger::error("calculando estadísticas del dashboard: " . $e->getMessage());

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
