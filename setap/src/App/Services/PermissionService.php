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

            return in_array($permissionName, $permissions);
        } catch (PDOException $e) {
            error_log("Error verificando permisos: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si un usuario tiene acceso a un menú
     */
    public function hasMenuAccess(int $userId, string $menuName): bool
    {
        try {
            $userType = $this->getUserType($userId);
            if (!$userType) {
                return false;
            }

            $cacheKey = "menus_{$userType['id']}";
            if (!isset(self::$menusCache[$cacheKey])) {
                self::$menusCache[$cacheKey] = $this->loadUserMenus($userType['id']);
            }

            $menus = self::$menusCache[$cacheKey];

            return in_array($menuName, $menus);
        } catch (PDOException $e) {
            error_log("Error verificando acceso a menú: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener todos los menús accesibles para un usuario
     * @return array
     */
    public function getUserMenus(int $userId)
    {
        try {
            $userType = $this->getUserType($userId);
            if (!$userType) {
                return [];
            }

            $stmt = $this->db->prepare("
                SELECT m.nombre, m.url, m.icono, m.orden
                FROM usuario_tipo_menus utm
                INNER JOIN menus m ON utm.menu_id = m.id
                WHERE utm.usuario_tipo_id = ? AND m.estado_tipo_id = 2
                ORDER BY m.orden ASC
            ");

            $stmt->execute([$userType['id']]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error obteniendo menús de usuario: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener el tipo de usuario
     * @return array|null
     */
    private function getUserType(int $userId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT ut.id, ut.nombre 
                FROM usuarios u
                INNER JOIN usuario_tipos ut ON u.usuario_tipo_id = ut.id
                WHERE u.id = ?
            ");

            $stmt->execute([$userId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error obteniendo tipo de usuario: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Cargar permisos del usuario desde la base de datos
     */
    private function loadUserPermissions(int $userTypeId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT p.nombre
                FROM usuario_tipo_permisos utp
                INNER JOIN permisos p ON utp.permiso_id = p.id
                WHERE utp.usuario_tipo_id = ? AND p.estado_tipo_id = 2
            ");

            $stmt->execute([$userTypeId]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("Error cargando permisos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Cargar menús del usuario desde la base de datos
     */
    private function loadUserMenus(int $userTypeId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT m.nombre
                FROM usuario_tipo_menus utm
                INNER JOIN menus m ON utm.menu_id = m.id
                WHERE utm.usuario_tipo_id = ? AND (m.estado_tipo_id = 1 or m.estado_tipo_id = 2)
            ");

            $stmt->execute([$userTypeId]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("Error cargando menús: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Limpiar cache de permisos
     */
    public static function clearCache(): void
    {
        self::$permissionsCache = [];
        self::$menusCache = [];
    }

    /**
     * Verificar múltiples permisos a la vez
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
     * Verificar que el usuario tenga TODOS los permisos
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
}
