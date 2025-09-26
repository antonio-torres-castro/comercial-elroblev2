<?php

namespace App\Services;

use App\Config\Database;
use PDO;
use PDOException;

class PermissionService
{
    private $db;
    private static $permissionsCache = [];
    private static $menusCache = [];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Verificar si un usuario tiene un permiso específico
     */
    public function hasPermission(int $userId, string $permissionName): bool
    {
        try {
            // Obtener el tipo de usuario
            $userType = $this->getUserType($userId);
            if (!$userType) {
                return false;
            }

            // Verificar en cache primero
            $cacheKey = "permissions_{$userType['id']}";
            if (!isset(self::$permissionsCache[$cacheKey])) {
                self::$permissionsCache[$cacheKey] = $this->loadUserPermissions($userType['id']);
            }

            $permissions = self::$permissionsCache[$cacheKey];

            return in_array(strtolower($permissionName), array_map('strtolower', $permissions));
        } catch (PDOException $e) {
            error_log('PermissionService::hasPermission error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si el usuario actual (en sesión) tiene un permiso
     */
    public function currentUserHasPermission(string $permissionName): bool
    {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        return $this->hasPermission($_SESSION['user_id'], $permissionName);
    }

    /**
     * Verificar si un usuario puede acceder a un menú específico
     */
    public function hasMenuAccess(int $userId, string $menuName): bool
    {
        try {
            // Obtener el tipo de usuario
            $userType = $this->getUserType($userId);
            if (!$userType) {
                return false;
            }

            // Verificar en cache primero
            $cacheKey = "menus_{$userType['id']}";
            if (!isset(self::$menusCache[$cacheKey])) {
                self::$menusCache[$cacheKey] = $this->loadUserMenus($userType['id']);
            }

            $menus = self::$menusCache[$cacheKey];

            return in_array(strtolower($menuName), array_map('strtolower', $menus));
        } catch (PDOException $e) {
            error_log('PermissionService::hasMenuAccess error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si el usuario actual puede acceder a un menú
     */
    public function currentUserHasMenuAccess(string $menuName): bool
    {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        return $this->hasMenuAccess($_SESSION['user_id'], $menuName);
    }

    /**
     * Obtener todos los permisos de un usuario
     */
    public function getUserPermissions(int $userId): array
    {
        try {
            $userType = $this->getUserType($userId);
            if (!$userType) {
                return [];
            }

            // Verificar cache
            $cacheKey = "permissions_{$userType['id']}";
            if (!isset(self::$permissionsCache[$cacheKey])) {
                self::$permissionsCache[$cacheKey] = $this->loadUserPermissions($userType['id']);
            }

            return self::$permissionsCache[$cacheKey];
        } catch (PDOException $e) {
            error_log('PermissionService::getUserPermissions error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener todos los menús accesibles para un usuario
     */
    public function getUserMenus(int $userId): array
    {
        try {
            $userType = $this->getUserType($userId);
            if (!$userType) {
                return [];
            }

            // Verificar cache
            $cacheKey = "menus_{$userType['id']}";
            if (!isset(self::$menusCache[$cacheKey])) {
                self::$menusCache[$cacheKey] = $this->loadUserMenus($userType['id']);
            }

            return self::$menusCache[$cacheKey];
        } catch (PDOException $e) {
            error_log('PermissionService::getUserMenus error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Verificar múltiples permisos (AND - todos deben ser verdaderos)
     */
    public function hasAllPermissions(int $userId, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($userId, $permission)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Verificar múltiples permisos (OR - al menos uno debe ser verdadero)
     */
    public function hasAnyPermission(int $userId, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($userId, $permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Verificar si un tipo de usuario tiene acceso de administrador
     */
    public function isAdmin(int $userId): bool
    {
        try {
            $userType = $this->getUserType($userId);
            return $userType && strtolower($userType['nombre']) === 'admin';
        } catch (PDOException $e) {
            error_log('PermissionService::isAdmin error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si el usuario actual es admin
     */
    public function currentUserIsAdmin(): bool
    {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        return $this->isAdmin($_SESSION['user_id']);
    }

    /**
     * Obtener todos los tipos de permisos disponibles
     */
    public function getAllPermissionTypes(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, nombre, descripcion 
                FROM permiso_tipos 
                ORDER BY nombre
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('PermissionService::getAllPermissionTypes error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener todos los menús disponibles
     */
    public function getAllMenus(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, nombre, descripcion 
                FROM menu 
                WHERE estado_tipo_id = 1 
                ORDER BY nombre
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('PermissionService::getAllMenus error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Asignar permiso a un tipo de usuario
     */
    public function assignPermissionToUserType(int $userTypeId, int $permissionId): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT IGNORE INTO usuario_tipo_permisos 
                (permiso_id, usuario_tipo_id, fecha_creacion, estado_tipo_id) 
                VALUES (?, ?, CURDATE(), 1)
            ");
            $result = $stmt->execute([$permissionId, $userTypeId]);

            // Limpiar cache
            $this->clearPermissionsCache($userTypeId);

            return $result;
        } catch (PDOException $e) {
            error_log('PermissionService::assignPermissionToUserType error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Remover permiso de un tipo de usuario
     */
    public function removePermissionFromUserType(int $userTypeId, int $permissionId): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE usuario_tipo_permisos 
                SET estado_tipo_id = 4, fecha_modificacion = CURDATE()
                WHERE permiso_id = ? AND usuario_tipo_id = ?
            ");
            $result = $stmt->execute([$permissionId, $userTypeId]);

            // Limpiar cache
            $this->clearPermissionsCache($userTypeId);

            return $result;
        } catch (PDOException $e) {
            error_log('PermissionService::removePermissionFromUserType error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Asignar menú a un tipo de usuario
     */
    public function assignMenuToUserType(int $userTypeId, int $menuId): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT IGNORE INTO usuario_tipo_menus 
                (menu_id, usuario_tipo_id, fecha_creacion, estado_tipo_id) 
                VALUES (?, ?, CURDATE(), 1)
            ");
            $result = $stmt->execute([$menuId, $userTypeId]);

            // Limpiar cache
            $this->clearMenusCache($userTypeId);

            return $result;
        } catch (PDOException $e) {
            error_log('PermissionService::assignMenuToUserType error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Remover menú de un tipo de usuario
     */
    public function removeMenuFromUserType(int $userTypeId, int $menuId): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE usuario_tipo_menus 
                SET estado_tipo_id = 4, fecha_modificacion = CURDATE()
                WHERE menu_id = ? AND usuario_tipo_id = ?
            ");
            $result = $stmt->execute([$menuId, $userTypeId]);

            // Limpiar cache
            $this->clearMenusCache($userTypeId);

            return $result;
        } catch (PDOException $e) {
            error_log('PermissionService::removeMenuFromUserType error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Limpiar cache de permisos
     */
    public function clearPermissionsCache(?int $userTypeId = null): void
    {
        if ($userTypeId) {
            unset(self::$permissionsCache["permissions_{$userTypeId}"]);
        } else {
            self::$permissionsCache = [];
        }
    }

    /**
     * Limpiar cache de menús
     */
    public function clearMenusCache(?int $userTypeId = null): void
    {
        if ($userTypeId) {
            unset(self::$menusCache["menus_{$userTypeId}"]);
        } else {
            self::$menusCache = [];
        }
    }

    // ============ MÉTODOS PRIVADOS ============

    /**
     * Obtener tipo de usuario por ID de usuario
     */
    private function getUserType(int $userId): array|false
    {
        try {
            $stmt = $this->db->prepare("
                SELECT ut.id, ut.nombre, ut.descripcion
                FROM usuarios u
                INNER JOIN usuario_tipos ut ON u.usuario_tipo_id = ut.id
                WHERE u.id = ? AND u.estado_tipo_id IN (1, 2)
            ");
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('PermissionService::getUserType error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Cargar permisos de un tipo de usuario desde la BD
     */
    private function loadUserPermissions(int $userTypeId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT pt.nombre
                FROM usuario_tipo_permisos utp
                INNER JOIN permiso_tipos pt ON utp.permiso_id = pt.id
                WHERE utp.usuario_tipo_id = ? AND utp.estado_tipo_id = 1
            ");
            $stmt->execute([$userTypeId]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log('PermissionService::loadUserPermissions error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Cargar menús de un tipo de usuario desde la BD
     */
    private function loadUserMenus(int $userTypeId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT m.nombre
                FROM usuario_tipo_menus utm
                INNER JOIN menu m ON utm.menu_id = m.id
                WHERE utm.usuario_tipo_id = ? 
                AND utm.estado_tipo_id = 1 
                AND m.estado_tipo_id = 1
            ");
            $stmt->execute([$userTypeId]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log('PermissionService::loadUserMenus error: ' . $e->getMessage());
            return [];
        }
    }
}
