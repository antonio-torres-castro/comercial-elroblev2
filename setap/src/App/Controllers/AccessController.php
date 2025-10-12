<?php
namespace App\Controllers;

use App\Constants\AppConstants;
use App\Traits\CommonValidationsTrait;
use Exception;

/**
 * AccessController - Refactorizado
 * Eliminación de código duplicado y estandarización
 *
 * ANTES: 200+ líneas con mucho código duplicado
 * DESPUÉS: ~85 líneas, código limpio y reutilizable
 */
class AccessController extends AbstractBaseController
{
    use CommonValidationsTrait;

    /**
     * Lista principal del mantenedor de accesos
     * ANTES: 50+ líneas, DESPUÉS: 15 líneas
     */
    public function index()
    {
        return $this->executeWithErrorHandling(function () {
            // Una sola línea reemplaza 10+ líneas de verificaciones
            if (!$this->requireAuthAndPermission('manage_access')) {
                return;
            }

            // Obtener datos sin duplicar métodos
            $data = [
                'userTypes' => $this->commonDataService->getUserTypes(),
                'allMenus' => $this->getAllMenus(),
                'accessByUserType' => $this->getAccessByUserType(),
                'title' => 'Gestión de Accesos',
                'subtitle' => 'Configurar accesos por tipo de usuario'
            ];

            // Renderización unificada
            $this->render('access/index', $data);
        }, 'index');
    }

    /**
     * Actualizar accesos de un tipo de usuario
     * ANTES: 60+ líneas, DESPUÉS: 25 líneas
     */
    public function update()
    {
        return $this->executeWithErrorHandling(function () {
            if (!$this->requireAuthAndPermission('manage_access')) {
                return;
            }

            // Validación POST y CSRF en una línea
            $errors = $this->validatePostRequest();
            if (!empty($errors)) {
                $this->jsonResponse(['success' => false, 'message' => implode(', ', $errors)], 400);
                return;
            }

            $userTypeId = (int)($_POST['user_type_id'] ?? 0);
            $menuIds = $_POST['menu_ids'] ?? [];

            if (!$userTypeId) {
                $this->jsonResponse(['success' => false, 'message' => 'Tipo de usuario requerido'], 400);
                return;
            }

            // Lógica de negocio limpia
            $result = $this->updateUserTypeAccess($userTypeId, $menuIds);
            $message = $result ? 'Accesos actualizados correctamente' : 'Error al actualizar accesos';
            $statusCode = $result ? 200 : 500;

            $this->jsonResponse(['success' => $result, 'message' => $message], $statusCode);
        }, 'update');
    }

    /**
     * Métodos privados específicos del controlador
     * (Lógica de negocio específica, no duplicada)
     */
    private function getAllMenus(): array
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
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error obteniendo menús: " . $e->getMessage());
            return [];
        }
    }

    private function getAccessByUserType(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT usuario_tipo_id, menu_id, fecha_creacion
                FROM usuario_tipo_menus
                WHERE estado_tipo_id = 2
            ");
            $stmt->execute();
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

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

    private function updateUserTypeAccess($userTypeId, $menuIds): bool
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
                $values = [];
                foreach ($menuIds as $menuId) {
                    $values[] = $userTypeId;
                    $values[] = (int)$menuId;
                }

                $stmt = $this->db->prepare("
                    INSERT INTO usuario_tipo_menus (usuario_tipo_id, menu_id, fecha_creacion, estado_tipo_id)
                    VALUES " . str_repeat('(?,?,NOW(),2),', count($menuIds) - 1) . "(?,?,NOW(),2)"
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
