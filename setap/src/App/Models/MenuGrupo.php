<?php

namespace App\Models;

use App\Config\Database;
use App\Constants\AppConstants;
use App\Helpers\Logger;

use PDO;
use Exception;

class MenuGrupo
{
    private $db;
    private $table = 'menu_grupo';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Obtener todos los grupos de menús activos
     */
    public function getAll(array $filters = []): array
    {
        try {
            $query = "
                SELECT
                    mg.id,
                    mg.nombre,
                    mg.descripcion,
                    mg.icono,
                    mg.orden,
                    mg.display,
                    mg.fecha_creacion,
                    mg.fecha_modificacion,
                    mg.estado_tipo_id,
                    et.nombre as estado_nombre
                FROM {$this->table} mg
                LEFT JOIN estado_tipos et ON mg.estado_tipo_id = et.id
                WHERE mg.estado_tipo_id != 4
            ";

            $params = [];

            // Filtros opcionales
            if (!empty($filters['nombre'])) {
                $query .= " AND mg.nombre LIKE ?";
                $params[] = '%' . $filters['nombre'] . '%';
            }

            if (!empty($filters['estado_tipo_id'])) {
                $query .= " AND mg.estado_tipo_id = ?";
                $params[] = $filters['estado_tipo_id'];
            }

            if (!empty($filters['display'])) {
                $query .= " AND mg.display LIKE ?";
                $params[] = '%' . $filters['display'] . '%';
            }

            $query .= " ORDER BY mg.orden ASC, mg.nombre ASC";

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            Logger::error("obtener grupos de menús: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener grupo por ID
     */
    public function find(int $id): ?array
    {
        try {
            $query = "
                SELECT
                    id,
                    nombre,
                    descripcion,
                    icono,
                    orden,
                    display,
                    fecha_creacion,
                    fecha_modificacion,
                    estado_tipo_id
                FROM {$this->table}
                WHERE id = ?
            ";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (Exception $e) {
            Logger::error("obtener grupo por ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Crear nuevo grupo de menús
     */
    public function create(array $data): int
    {
        try {
            $query = "
                INSERT INTO {$this->table} (
                    nombre,
                    descripcion,
                    icono,
                    orden,
                    display,
                    estado_tipo_id,
                    fecha_creacion
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ";

            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['nombre'],
                $data['descripcion'] ?? null,
                $data['icono'] ?? null,
                $data['orden'] ?? 999,
                $data['display'] ?? $data['nombre'] ?? '',
                $data['estado_tipo_id'] ?? 1
            ]);

            return (int)$this->db->lastInsertId();
        } catch (Exception $e) {
            Logger::error("crear grupo de menús: " . $e->getMessage());
            throw new Exception("Error al crear el grupo de menús: " . $e->getMessage());
        }
    }

    /**
     * Actualizar grupo de menús
     */
    public function update(int $id, array $data): bool
    {
        try {
            $query = "
                UPDATE {$this->table}
                SET
                    nombre = ?,
                    descripcion = ?,
                    icono = ?,
                    orden = ?,
                    display = ?,
                    estado_tipo_id = ?,
                    fecha_modificacion = NOW()
                WHERE id = ?
            ";

            $stmt = $this->db->prepare($query);
            return $stmt->execute([
                $data['nombre'],
                $data['descripcion'] ?? null,
                $data['icono'] ?? null,
                $data['orden'] ?? 999,
                $data['display'] ?? $data['nombre'] ?? '',
                $data['estado_tipo_id'] ?? 1,
                $id
            ]);
        } catch (Exception $e) {
            Logger::error("actualizar grupo de menús: " . $e->getMessage());
            throw new Exception("Error al actualizar el grupo de menús: " . $e->getMessage());
        }
    }

    /**
     * Eliminar grupo de menús (soft delete)
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
            Logger::error("eliminar grupo de menús: " . $e->getMessage());
            throw new Exception("Error al eliminar el grupo de menús: " . $e->getMessage());
        }
    }

    /**
     * Obtener grupos activos para navegación
     */
    public function getActiveGroups(): array
    {
        try {
            $query = "
                SELECT
                    id,
                    nombre,
                    descripcion,
                    icono,
                    orden,
                    display
                FROM {$this->table}
                WHERE estado_tipo_id = 2
                ORDER BY orden ASC, nombre ASC
            ";

            $stmt = $this->db->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            Logger::error("obtener grupos activos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Verificar si el nombre del grupo ya existe
     */
    public function nameExists(string $nombre, ?int $excludeId = null): bool
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
            Logger::error("verificar nombre de grupo: " . $e->getMessage());
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
            Logger::error("obtener siguiente orden: " . $e->getMessage());
            return 999;
        }
    }

    /**
     * Obtener estados disponibles para grupos
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
            Logger::error("obtener estados: " . $e->getMessage());
            return [
                ['id' => 1, 'nombre' => 'Creado'],
                ['id' => 2, 'nombre' => 'Activo'],
                ['id' => 3, 'nombre' => 'Inactivo']
            ];
        }
    }

    /**
     * Cambiar estado del grupo
     */
    public function toggleStatus(int $id): bool
    {
        try {
            // Obtener estado actual
            $currentGroup = $this->find($id);
            if (!$currentGroup) {
                throw new Exception(AppConstants::ERROR_GROUP_NOT_FOUND);
            }

            // Cambiar estado (2=Activo, 3=Inactivo)
            $newStatus = ($currentGroup['estado_tipo_id'] == 2) ? 3 : 2;

            $query = "
                UPDATE {$this->table}
                SET estado_tipo_id = ?, fecha_modificacion = NOW()
                WHERE id = ?
            ";

            $stmt = $this->db->prepare($query);
            return $stmt->execute([$newStatus, $id]);
        } catch (Exception $e) {
            Logger::error("cambiar estado del grupo: " . $e->getMessage());
            throw new Exception("Error al cambiar el estado del grupo: " . $e->getMessage());
        }
    }
}
