<?php

namespace App\Models;

use App\Config\Database;
use PDO;
use Exception;

class Menu
{
    private $db;
    private $table = 'menu';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Obtener todos los menús activos
     */
    public function getAll(array $filters = []): array
    {
        try {
            $query = "
                SELECT 
                    m.id,
                    m.nombre,
                    m.descripcion,
                    m.url,
                    m.icono,
                    m.orden,
                    m.estado_tipo_id,
                    m.fecha_creacion,
                    m.fecha_modificacion,
                    m.display,
                    et.nombre as estado_nombre
                FROM {$this->table} m
                LEFT JOIN estado_tipos et ON m.estado_tipo_id = et.id
                WHERE m.estado_tipo_id != 4
            ";

            $params = [];

            // Filtros opcionales
            if (!empty($filters['nombre'])) {
                $query .= " AND m.nombre LIKE ?";
                $params[] = '%' . $filters['nombre'] . '%';
            }

            if (!empty($filters['estado_tipo_id'])) {
                $query .= " AND m.estado_tipo_id = ?";
                $params[] = $filters['estado_tipo_id'];
            }

            if (!empty($filters['display'])) {
                $query .= " AND m.display LIKE ?";
                $params[] = '%' . $filters['display'] . '%';
            }

            $query .= " ORDER BY m.orden ASC, m.nombre ASC";

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error al obtener menús: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener menú por ID
     */
    public function find(int $id): ?array
    {
        try {
            $query = "
                SELECT 
                    id,
                    nombre,
                    descripcion,
                    url,
                    icono,
                    orden,
                    estado_tipo_id,
                    fecha_creacion,
                    fecha_modificacion,
                    display
                FROM {$this->table} 
                WHERE id = ?
            ";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (Exception $e) {
            error_log("Error al obtener menú por ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Crear nuevo menú
     */
    public function create(array $data): int
    {
        try {
            $query = "
                INSERT INTO {$this->table} (
                    nombre, 
                    descripcion, 
                    url, 
                    icono, 
                    orden, 
                    estado_tipo_id, 
                    display, 
                    fecha_creacion
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ";

            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['nombre'],
                $data['descripcion'] ?? null,
                $data['url'] ?? null,
                $data['icono'] ?? null,
                $data['orden'] ?? 0,
                $data['estado_tipo_id'] ?? 1,
                $data['display'] ?? $data['nombre'] ?? ''
            ]);

            return (int)$this->db->lastInsertId();
        } catch (Exception $e) {
            error_log("Error al crear menú: " . $e->getMessage());
            throw new Exception("Error al crear el menú: " . $e->getMessage());
        }
    }

    /**
     * Actualizar menú
     */
    public function update(int $id, array $data): bool
    {
        try {
            $query = "
                UPDATE {$this->table} 
                SET 
                    nombre = ?, 
                    descripcion = ?, 
                    url = ?, 
                    icono = ?, 
                    orden = ?, 
                    estado_tipo_id = ?, 
                    display = ?, 
                    fecha_modificacion = NOW()
                WHERE id = ?
            ";

            $stmt = $this->db->prepare($query);
            return $stmt->execute([
                $data['nombre'],
                $data['descripcion'] ?? null,
                $data['url'] ?? null,
                $data['icono'] ?? null,
                $data['orden'] ?? 0,
                $data['estado_tipo_id'] ?? 1,
                $data['display'] ?? $data['nombre'] ?? '',
                $id
            ]);
        } catch (Exception $e) {
            error_log("Error al actualizar menú: " . $e->getMessage());
            throw new Exception("Error al actualizar el menú: " . $e->getMessage());
        }
    }

    /**
     * Eliminar menú (soft delete)
     */
    public function delete(int $id): bool
    {
        try {
            $query = "
                UPDATE {$this->table} 
                SET estado_tipo_id = 4, fecha_modificacion = NOW() 
                WHERE id = ?
            ";

            $stmt = $this->db->prepare($query);
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("Error al eliminar menú: " . $e->getMessage());
            throw new Exception("Error al eliminar el menú: " . $e->getMessage());
        }
    }

    /**
     * Cambiar estado del menú
     */
    public function toggleStatus(int $id): bool
    {
        try {
            // Obtener estado actual
            $currentMenu = $this->find($id);
            if (!$currentMenu) {
                throw new Exception("Menú no encontrado");
            }

            // Cambiar estado (2=Activo, 3=Inactivo)
            $newStatus = ($currentMenu['estado_tipo_id'] == 2) ? 3 : 2;

            $query = "
                UPDATE {$this->table} 
                SET estado_tipo_id = ?, fecha_modificacion = NOW() 
                WHERE id = ?
            ";

            $stmt = $this->db->prepare($query);
            return $stmt->execute([$newStatus, $id]);
        } catch (Exception $e) {
            error_log("Error al cambiar estado del menú: " . $e->getMessage());
            throw new Exception("Error al cambiar el estado del menú: " . $e->getMessage());
        }
    }

    /**
     * Obtener estados disponibles para menús
     */
    public function getStatusTypes(): array
    {
        try {
            $query = "
                SELECT id, nombre 
                FROM estado_tipos 
                WHERE id IN (1, 2, 3)
                ORDER BY id ASC
            ";

            $stmt = $this->db->prepare($query);
            $stmt->execute();

            $estados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Si no hay estados específicos, usar estados predeterminados
            if (empty($estados)) {
                $estados = [
                    ['id' => 1, 'nombre' => 'Creado'],
                    ['id' => 2, 'nombre' => 'Activo'], 
                    ['id' => 3, 'nombre' => 'Inactivo']
                ];
            }

            return $estados;
        } catch (Exception $e) {
            error_log("Error al obtener estados: " . $e->getMessage());
            return [
                ['id' => 1, 'nombre' => 'Creado'],
                ['id' => 2, 'nombre' => 'Activo'], 
                ['id' => 3, 'nombre' => 'Inactivo']
            ];
        }
    }

    /**
     * Verificar si el nombre del menú ya existe
     */
    public function nameExists(string $nombre, int $excludeId = null): bool
    {
        try {
            $query = "SELECT COUNT(*) FROM {$this->table} WHERE nombre = ? AND estado_tipo_id != 4";
            $params = [$nombre];

            if ($excludeId) {
                $query .= " AND id != ?";
                $params[] = $excludeId;
            }

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);

            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("Error al verificar nombre de menú: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si la URL del menú ya existe
     */
    public function urlExists(string $url, int $excludeId = null): bool
    {
        try {
            $query = "SELECT COUNT(*) FROM {$this->table} WHERE url = ? AND estado_tipo_id != 4";
            $params = [$url];

            if ($excludeId) {
                $query .= " AND id != ?";
                $params[] = $excludeId;
            }

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);

            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("Error al verificar URL de menú: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener el siguiente número de orden disponible
     */
    public function getNextOrder(): int
    {
        try {
            $query = "SELECT COALESCE(MAX(orden), 0) + 1 FROM {$this->table} WHERE estado_tipo_id != 4";
            $stmt = $this->db->prepare($query);
            $stmt->execute();

            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            error_log("Error al obtener siguiente orden: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Obtener menús para el sistema de navegación (solo activos y con display=1)
     */
    public function getNavigationMenus(): array
    {
        try {
            $query = "
                SELECT 
                    id,
                    nombre,
                    display,
                    url,
                    icono,
                    orden
                FROM {$this->table} 
                WHERE estado_tipo_id = 2 
                AND display IS NOT NULL 
                AND display != ''
                ORDER BY orden ASC, nombre ASC
            ";

            $stmt = $this->db->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error al obtener menús de navegación: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener menús específicos para un usuario basado en sus permisos
     */
    public function getMenusForUser(int $userId): array
    {
        try {
            // Usar App\Services\PermissionService para verificar permisos
            $permissionService = new \App\Services\PermissionService();
            
            // Obtener todos los menús activos
            $allMenus = $this->getNavigationMenus();
            
            // Filtrar menús según permisos del usuario
            $userMenus = [];
            foreach ($allMenus as $menu) {
                // Verificar si el usuario tiene acceso a este menú
                if ($permissionService->hasMenuAccess($userId, $menu['nombre'])) {
                    $userMenus[] = $menu;
                }
            }
            
            return $userMenus;
        } catch (Exception $e) {
            error_log("Error al obtener menús de usuario: " . $e->getMessage());
            return [];
        }
    }
}