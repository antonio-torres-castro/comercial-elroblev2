<?php

namespace App\Controllers;

use App\Services\PermissionService;
use App\Core\ViewRenderer;
use App\Middlewares\AuthMiddleware;
use App\Helpers\Security;
use App\Constants\AppConstants;
use App\Config\Database;
use PDO;
use Exception;

class AccessController extends BaseController
{
    private $permissionService;
    private $viewRenderer;
    private $db;

    public function __construct()
    {
        // Verificar autenticación
        (new AuthMiddleware())->handle();

        $this->permissionService = new PermissionService();
        $this->viewRenderer = new ViewRenderer();
        $this->db = Database::getInstance();
    }

    /**
     * Lista principal del mantenedor de accesos
     */
    public function index()
    {
        try {
            $currentUser = $this->getCurrentUser();

            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            // Verificar acceso al menú
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_access')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_ACCESS_DENIED);
                return;
            }

            // Obtener tipos de usuario
            $userTypes = $this->getUserTypes();

            // Obtener todos los menús disponibles
            $allMenus = $this->getAllMenus();

            // Obtener accesos actuales por tipo de usuario
            $accessByUserType = $this->getAccessByUserType();

            // Renderizar la vista
            echo $this->viewRenderer->render('access/index', [
                'userTypes' => $userTypes,
                'allMenus' => $allMenus,
                'accessByUserType' => $accessByUserType,
                'currentUser' => $currentUser
            ]);
        } catch (Exception $e) {
            error_log("Error en AccessController::index: " . $e->getMessage());
            http_response_code(500);
            echo AppConstants::ERROR_INTERNAL_SERVER;
        }
    }

    /**
     * Actualizar accesos de un tipo de usuario
     */
    public function update()
    {
        try {
            $currentUser = $this->getCurrentUser();

            if (!$currentUser) {
                $this->redirectToLogin();
                return;
            }

            // Verificar acceso al menú
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_access')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
                return;
            }

            $userTypeId = (int)($_POST['user_type_id'] ?? 0);
            $menuIds = $_POST['menu_ids'] ?? [];

            if (!$userTypeId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Tipo de usuario requerido']);
                return;
            }

            // Actualizar accesos
            $result = $this->updateUserTypeAccess($userTypeId, $menuIds);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Accesos actualizados correctamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar accesos']);
            }
        } catch (Exception $e) {
            error_log("Error en AccessController::update: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
    }

    /**
     * Obtener tipos de usuario
     */
    private function getUserTypes()
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, nombre, descripcion
                FROM usuario_tipos
                ORDER BY nombre
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error obteniendo tipos de usuario: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener todos los menús disponibles
     */
    private function getAllMenus()
    {
        try {
            $stmt = $this->db->prepare("
                SELECT m.id, m.nombre, m.descripcion, m.display, m.icono, mg.nombre as grupo_nombre
                FROM menu m
                LEFT JOIN menu_grupo mg ON m.menu_grupo_id = mg.id
                WHERE m.estado_tipo_id = 2
                ORDER BY mg.nombre, m.orden, m.nombre
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error obteniendo menús: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener accesos actuales por tipo de usuario
     */
    private function getAccessByUserType()
    {
        try {
            $stmt = $this->db->prepare("
                SELECT usuario_tipo_id, menu_id, fecha_creacion
                FROM usuario_tipo_menus
                WHERE estado_tipo_id = 2
            ");
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Organizar por tipo de usuario
            $accessByUserType = [];
            foreach ($result as $access) {
                $accessByUserType[$access['usuario_tipo_id']][] = $access['menu_id'];
            }

            return $accessByUserType;
        } catch (Exception $e) {
            error_log("Error obteniendo accesos por tipo de usuario: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Actualizar accesos de un tipo de usuario
     */
    private function updateUserTypeAccess($userTypeId, $menuIds)
    {
        try {
            $this->db->beginTransaction();

            // Eliminar accesos actuales (cambiar estado)
            $stmt = $this->db->prepare("
                UPDATE usuario_tipo_menus
                SET estado_tipo_id = 4, fecha_modificacion = NOW()
                WHERE usuario_tipo_id = ?
            ");
            $stmt->execute([$userTypeId]);

            // Agregar nuevos accesos
            if (!empty($menuIds)) {
                $placeholders = str_repeat('?,', count($menuIds) - 1) . '?';
                $values = [];
                foreach ($menuIds as $menuId) {
                    $values[] = $userTypeId;
                    $values[] = (int)$menuId;
                }

                $stmt = $this->db->prepare(
                    "
                    INSERT INTO usuario_tipo_menus (usuario_tipo_id, menu_id, fecha_creacion, estado_tipo_id)
                    VALUES " . str_repeat('(?,?,NOW(),1),', count($menuIds) - 1) . "(?,?,NOW(),1)"
                );
                $stmt->execute($values);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error actualizando accesos: " . $e->getMessage());
            return false;
        }
    }
}
