<?php

namespace App\Controllers;

use App\Constants\AppConstants;
use App\Traits\CommonValidationsTrait;
use Exception;

/**
 * PermissionsController - Refactorizado
 * Eliminación de código duplicado y estandarización
 *
 * ANTES: 200+ líneas con mucho código duplicado
 * DESPUÉS: ~85 líneas, código limpio y reutilizable
 */
class PermissionsController extends AbstractBaseController
{
    use CommonValidationsTrait;

    /**
     * Lista principal del mantenedor de permisos
     * ANTES: 50+ líneas, DESPUÉS: 15 líneas
     */
    public function index()
    {
        return $this->executeWithErrorHandling(function () {
            // Una sola línea reemplaza 10+ líneas de verificaciones
            if (!$this->requireAuthAndPermission('manage_permissions')) {
                return;
            }

            // Obtener datos sin duplicar métodos
            $data = [
                'userTypes' => $this->commonDataService->getUserTypes(),
                'allPermissions' => $this->getAllPermissions(),
                'permissionsByUserType' => $this->getPermissionsByUserType(),
                'title' => 'Gestión de Permisos',
                'subtitle' => 'Configurar permisos por tipo de usuario'
            ];

            // Renderización unificada
            $this->render('permissions/index', $data);
        }, 'index');
    }

    /**
     * Actualizar permisos de un tipo de usuario
     * ANTES: 60+ líneas, DESPUÉS: 25 líneas
     * Formulario tradicional con redirect
     */
    public function update()
    {
        return $this->executeWithErrorHandling(function () {
            if (!$this->requireAuthAndPermission('manage_permissions')) {
                return;
            }

            // Validación POST y CSRF en una línea
            $errors = $this->validatePostRequest();
            if (!empty($errors)) {
                $this->redirectWithError(AppConstants::ROUTE_PERMISOS, implode(', ', $errors));
                return;
            }

            $userTypeId = (int)($_POST['user_type_id'] ?? 0);
            $permissionIds = $_POST['permission_ids'] ?? [];

            if (!$userTypeId) {
                $this->redirectWithError(AppConstants::ROUTE_PERMISOS, 'Tipo de usuario requerido');
                return;
            }

            // Lógica de negocio limpia
            $result = $this->updateUserTypePermissions($userTypeId, $permissionIds);
            $message = $result ? 'Permisos actualizados correctamente' : 'Error al actualizar permisos';

            // Respuesta tradicional con redirect
            if ($result) {
                $this->redirectWithSuccess(AppConstants::ROUTE_PERMISOS, $message);
            } else {
                $this->redirectWithError(AppConstants::ROUTE_PERMISOS, $message);
            }
        }, 'update');
    }

    /**
     * Métodos privados específicos del controlador
     * (Lógica de negocio específica, no duplicada)
     */
    private function getAllPermissions(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, nombre, descripcion
                FROM permiso_tipos
                ORDER BY nombre
            ");
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error obteniendo permisos: " . $e->getMessage());
            return [];
        }
    }

    private function getPermissionsByUserType(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT usuario_tipo_id, permiso_id, fecha_creacion
                FROM usuario_tipo_permisos
                WHERE estado_tipo_id = 2
            ");
            $stmt->execute();
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

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

    private function updateUserTypePermissions($userTypeId, $permissionIds): bool
    {
        try {
            $this->db->beginTransaction();

            // 1. Obtener las tuplas actuales activas (usuario_tipo_id, permiso_id)
            $stmt = $this->db->prepare("
                SELECT permiso_id
                FROM usuario_tipo_permisos
                WHERE usuario_tipo_id = ? AND estado_tipo_id = 2
            ");
            $stmt->execute([$userTypeId]);
            $currentPermissionIds = array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'permiso_id');

            // 2. Obtener las tuplas eliminadas (usuario_tipo_id, permiso_id)
            $stmt = $this->db->prepare("
                SELECT permiso_id
                FROM usuario_tipo_permisos
                WHERE usuario_tipo_id = ? AND estado_tipo_id = 4
            ");
            $stmt->execute([$userTypeId]);
            $deletedPermissionIds = array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'permiso_id');

            // Asegurar que $permissionIds es array, convertir a enteros y eliminar duplicados
            $permissionIds = !empty($permissionIds) ? array_unique(array_map('intval', (array)$permissionIds)) : [];

            // 3. Encontrar los permiso_id que ya no están seleccionados (deben desactivarse)
            $permissionIdsToDeactivate = array_diff($currentPermissionIds, $permissionIds);

            // 4. Desactivar los permisos que ya no están seleccionados
            if (!empty($permissionIdsToDeactivate)) {
                $placeholders = str_repeat('?,', count($permissionIdsToDeactivate) - 1) . '?';
                $stmt = $this->db->prepare("
                    UPDATE usuario_tipo_permisos
                    SET estado_tipo_id = 4, fecha_modificacion = NOW()
                    WHERE usuario_tipo_id = ? AND permiso_id IN ($placeholders)
                ");
                $stmt->execute(array_merge([$userTypeId], $permissionIdsToDeactivate));
            }

            // 5. Encontrar los permiso_id que nuevamente están seleccionados (deben activarse)
            $permissionIdsToActivate = array_intersect($deletedPermissionIds, $permissionIds);

            // 6. Activar los permisos que se han vuelto a seleccionar
            if (!empty($permissionIdsToActivate)) {
                $placeholders = str_repeat('?,', count($permissionIdsToActivate) - 1) . '?';
                $stmt = $this->db->prepare("
                    UPDATE usuario_tipo_permisos
                    SET estado_tipo_id = 2, fecha_modificacion = NOW()
                    WHERE usuario_tipo_id = ? AND permiso_id IN ($placeholders)
                ");
                $stmt->execute(array_merge([$userTypeId], $permissionIdsToActivate));
            }

            // 7. Encontrar los permiso_id nuevos que deben insertarse
            $allExistingPermissionIds = array_merge($currentPermissionIds, $permissionIdsToActivate);
            $permissionIdsToInsert = array_diff($permissionIds, $allExistingPermissionIds);

            // Insertar los nuevos permisos
            if (!empty($permissionIdsToInsert)) {
                $values = [];
                foreach ($permissionIdsToInsert as $permissionId) {
                    $values[] = $userTypeId;
                    $values[] = (int)$permissionId;
                }

                $stmt = $this->db->prepare(
                    "INSERT INTO usuario_tipo_permisos (usuario_tipo_id, permiso_id, fecha_creacion, estado_tipo_id)
                    VALUES " . str_repeat('(?,?,NOW(),2),', count($permissionIdsToInsert) - 1) . "(?,?,NOW(),2)"
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
