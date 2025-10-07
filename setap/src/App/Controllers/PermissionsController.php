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

class PermissionsController extends BaseController
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
     * Lista principal del mantenedor de permisos
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
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_permissions')) {
                http_response_code(403);
                echo $this->renderError(AppConstants::ERROR_ACCESS_DENIED);
                return;
            }

            // Obtener tipos de usuario
            $userTypes = $this->getUserTypes();

            // Obtener todos los permisos disponibles
            $allPermissions = $this->getAllPermissions();

            // Obtener permisos actuales por tipo de usuario
            $permissionsByUserType = $this->getPermissionsByUserType();

            // Renderizar la vista
            echo $this->viewRenderer->render('permissions/index', [
                'userTypes' => $userTypes,
                'allPermissions' => $allPermissions,
                'permissionsByUserType' => $permissionsByUserType,
                'currentUser' => $currentUser
            ]);
        } catch (Exception $e) {
            error_log("Error en PermissionsController::index: " . $e->getMessage());
            http_response_code(500);
            echo AppConstants::ERROR_INTERNAL_SERVER;
        }
    }

    /**
     * Actualizar permisos de un tipo de usuario
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
            if (!$this->permissionService->hasMenuAccess($currentUser['id'], 'manage_permissions')) {
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
            $permissionIds = $_POST['permission_ids'] ?? [];

            if (!$userTypeId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Tipo de usuario requerido']);
                return;
            }

            // Actualizar permisos
            $result = $this->updateUserTypePermissions($userTypeId, $permissionIds);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Permisos actualizados correctamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar permisos']);
            }
        } catch (Exception $e) {
            error_log("Error en PermissionsController::update: " . $e->getMessage());
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
     * Obtener todos los permisos disponibles
     */
    private function getAllPermissions()
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, nombre, descripcion
                FROM permiso_tipos
                ORDER BY nombre
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error obteniendo permisos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener permisos actuales por tipo de usuario
     */
    private function getPermissionsByUserType()
    {
        try {
            $stmt = $this->db->prepare("
                SELECT usuario_tipo_id, permiso_id, fecha_creacion
                FROM usuario_tipo_permisos
                WHERE estado_tipo_id = 2
            ");
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Organizar por tipo de usuario
            $permissionsByUserType = [];
            foreach ($result as $permission) {
                $permissionsByUserType[$permission['usuario_tipo_id']][] = $permission['permiso_id'];
            }

            return $permissionsByUserType;
        } catch (Exception $e) {
            error_log("Error obteniendo permisos por tipo de usuario: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Actualizar permisos de un tipo de usuario
     */
    private function updateUserTypePermissions($userTypeId, $permissionIds)
    {
        try {
            $this->db->beginTransaction();

            // Eliminar permisos actuales (cambiar estado)
            $stmt = $this->db->prepare("
                UPDATE usuario_tipo_permisos
                SET estado_tipo_id = 4, fecha_modificacion = NOW()
                WHERE usuario_tipo_id = ?
            ");
            $stmt->execute([$userTypeId]);

            // Agregar nuevos permisos
            if (!empty($permissionIds)) {
                $values = [];
                foreach ($permissionIds as $permissionId) {
                    $values[] = $userTypeId;
                    $values[] = (int)$permissionId;
                }

                $stmt = $this->db->prepare(
                    "
                    INSERT INTO usuario_tipo_permisos (usuario_tipo_id, permiso_id, fecha_creacion, estado_tipo_id)
                    VALUES " . str_repeat('(?,?,NOW(),1),', count($permissionIds) - 1) . "(?,?,NOW(),1)"
                );
                $stmt->execute($values);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error actualizando permisos: " . $e->getMessage());
            return false;
        }
    }
}
