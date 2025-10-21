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
                $this->redirectWithError(AppConstants::ROUTE_ACCESS, implode(', ', $errors));
                return;
            }

            $userTypeId = (int)($_POST['user_type_id'] ?? 0);
            $menuIds = $_POST['menu_ids'] ?? [];

            if (!$userTypeId) {
                $this->redirectWithError(AppConstants::ROUTE_ACCESS, 'Tipo de usuario requerido');
                return;
            }

            // Lógica de negocio limpia
            $result = $this->updateUserTypeAccess($userTypeId, $menuIds);
            $message = $result ? 'Accesos actualizados correctamente' : 'Error al actualizar accesos';

            if ($result) {
                $this->redirectWithSuccess(AppConstants::ROUTE_ACCESS, $message);
            } else {
                $this->redirectWithError(AppConstants::ROUTE_ACCESS, $message);
            }
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

            // 1. Obtener las tuplas actuales (usuario_tipo_id, menu_id)
            $stmt = $this->db->prepare("
                SELECT menu_id
                FROM usuario_tipo_menus
                WHERE usuario_tipo_id = ? AND estado_tipo_id = 2
            ");
            $stmt->execute([$userTypeId]);
            $currentMenuIds = array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'menu_id');

            // 1. Obtener las tuplas eliminadas (usuario_tipo_id, menu_id)
            $stmt = $this->db->prepare("
                SELECT menu_id
                FROM usuario_tipo_menus
                WHERE usuario_tipo_id = ? AND estado_tipo_id = 4
            ");
            $stmt->execute([$userTypeId]);
            $deletedMenuIds = array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'menu_id');

            // Asegurar que $menuIds es array y convertir a enteros
            $menuIds = !empty($menuIds) ? array_map('intval', (array)$menuIds) : [];

            // 2. Encontrar los menu_id que ya no están seleccionados (deben desactivarse)
            $menuIdsToDeactivate = array_diff($currentMenuIds, $menuIds);

            // 3. Desactivar los accesos que ya no están seleccionados
            if (!empty($menuIdsToDeactivate)) {
                $placeholders = str_repeat('?,', count($menuIdsToDeactivate) - 1) . '?';
                $stmt = $this->db->prepare("
                    UPDATE usuario_tipo_menus
                    SET estado_tipo_id = 4, fecha_modificacion = NOW()
                    WHERE usuario_tipo_id = ? AND menu_id IN ($placeholders)
                ");
                $stmt->execute(array_merge([$userTypeId], $menuIdsToDeactivate));
            }

            // 4. Encontrar los menu_id que nuevamente están seleccionados (deben activarse)
            $menuIdsToActivate = array_intersect($deletedMenuIds, $menuIds);

            // 5. Activar los accesos que se han vuelto a seleccionar
            if (!empty($menuIdsToActivate)) {
                $placeholders = str_repeat('?,', count($menuIdsToActivate) - 1) . '?';
                $stmt = $this->db->prepare("
                    UPDATE usuario_tipo_menus
                    SET estado_tipo_id = 2, fecha_modificacion = NOW()
                    WHERE usuario_tipo_id = ? AND menu_id IN ($placeholders)
                ");
                $stmt->execute(array_merge([$userTypeId], $menuIdsToActivate));
            }

            // 6. Encontrar los menu_id nuevos que deben insertarse
            $allNewMenu_Id = array_merge($currentMenuIds, $menuIdsToActivate);
            $menuIdsToInsert = array_diff($menuIds, $allNewMenu_Id);

            // Insertar los nuevos accesos
            if (!empty($menuIdsToInsert)) {
                $values = [];
                foreach ($menuIdsToInsert as $menuId) {
                    $values[] = $userTypeId;
                    $values[] = (int)$menuId;
                }

                $stmt = $this->db->prepare(
                    "INSERT INTO usuario_tipo_menus (usuario_tipo_id, menu_id, fecha_creacion, estado_tipo_id)
                    VALUES " . str_repeat('(?,?,NOW(),2),', count($menuIdsToInsert) - 1) . "(?,?,NOW(),2)"
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
