<?php

namespace App\Models;

use App\Config\Database;
use App\Helpers\Logger;
use PDO;
use Exception;

class Process
{
    private $db;
    private $table = 'proveedor_procesos';
    private $tasksTable = 'proveedor_proceso_tareas';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Obtener todos los procesos
     */
    public function getAll(array $filters = []): array
    {
        try {
            $query = "
                SELECT
                    pp.*,
                    p.razon_social as proveedor_nombre
                FROM {$this->table} pp
                LEFT JOIN proveedores p ON pp.proveedor_id = p.id
                WHERE 1=1
            ";

            $params = [];

            if (!empty($filters['proveedor_id'])) {
                $query .= " AND pp.proveedor_id = ?";
                $params[] = $filters['proveedor_id'];
            }

            if (!empty($filters['nombre'])) {
                $query .= " AND pp.nombre LIKE ?";
                $params[] = '%' . $filters['nombre'] . '%';
            }

            $query .= " ORDER BY pp.nombre ASC";

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            Logger::error("Process::getAll: " . $e->getMessage());
            throw new Exception("Error al obtener la lista de procesos");
        }
    }

    /**
     * Obtener un proceso por ID
     */
    public function find(int $id): ?array
    {
        try {
            $query = "
                SELECT
                    pp.*,
                    p.razon_social as proveedor_nombre
                FROM {$this->table} pp
                LEFT JOIN proveedores p ON pp.proveedor_id = p.id
                WHERE pp.id = ?
            ";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (Exception $e) {
            Logger::error("Process::find: " . $e->getMessage());
            throw new Exception("Error al obtener el proceso");
        }
    }

    /**
     * Obtener procesos por proveedor
     */
    public function getByProvider(int $proveedorId): array
    {
        try {
            $query = "
                SELECT
                    pp.id,
                    pp.nombre,
                    pp.descripcion
                FROM {$this->table} pp
                WHERE pp.proveedor_id = ?
                ORDER BY pp.nombre ASC
            ";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$proveedorId]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            Logger::error("Process::getByProvider: " . $e->getMessage());
            throw new Exception("Error al obtener procesos por proveedor");
        }
    }

    /**
     * Crear un nuevo proceso
     */
    public function create(array $data): int
    {
        try {
            $query = "
                INSERT INTO {$this->table} (
                    proveedor_id,
                    nombre,
                    descripcion
                ) VALUES (?, ?, ?)
            ";

            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['proveedor_id'],
                $data['nombre'],
                $data['descripcion'] ?? null
            ]);

            return (int) $this->db->lastInsertId();
        } catch (Exception $e) {
            Logger::error("Process::create: " . $e->getMessage());
            throw new Exception("Error al crear el proceso");
        }
    }

    /**
     * Actualizar un proceso
     */
    public function update(int $id, array $data): bool
    {
        try {
            $query = "
                UPDATE {$this->table} SET
                    proveedor_id = ?,
                    nombre = ?,
                    descripcion = ?
                WHERE id = ?
            ";

            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                $data['proveedor_id'],
                $data['nombre'],
                $data['descripcion'] ?? null,
                $id
            ]);

            return $result && $stmt->rowCount() > 0;
        } catch (Exception $e) {
            Logger::error("Process::update: " . $e->getMessage());
            throw new Exception("Error al actualizar el proceso");
        }
    }

    /**
     * Eliminar proceso
     */
    public function delete(int $id): bool
    {
        try {
            // Primero eliminar las tareas asociadas al proceso
            $this->clearProcessTasks($id);

            $query = "DELETE FROM {$this->table} WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([$id]);

            return $result && $stmt->rowCount() > 0;
        } catch (Exception $e) {
            Logger::error("Process::delete: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtener tareas de un proceso
     */
    public function getProcessTasks(int $processId): array
    {
        try {
            $query = "
                SELECT
                    ppt.id,
                    ppt.tarea_id,
                    ppt.hh,
                    t.nombre as tarea_nombre,
                    t.descripcion as tarea_descripcion,
                    tc.nombre as categoria_nombre
                FROM {$this->tasksTable} ppt
                JOIN tareas t ON ppt.tarea_id = t.id
                LEFT JOIN tarea_categorias tc ON t.tarea_categoria_id = tc.id
                WHERE ppt.proveedor_proceso_id = ?
                ORDER BY t.nombre ASC
            ";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$processId]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            Logger::error("Process::getProcessTasks: " . $e->getMessage());
            throw new Exception("Error al obtener tareas del proceso");
        }
    }

    /**
     * Agregar tarea a un proceso
     */
    public function addTaskToProcess(int $processId, int $tareaId, float $hh): int
    {
        try {
            $query = "
                INSERT INTO {$this->tasksTable} (
                    proveedor_proceso_id,
                    tarea_id,
                    hh
                ) VALUES (?, ?, ?)
            ";

            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $processId,
                $tareaId,
                $hh
            ]);

            return (int) $this->db->lastInsertId();
        } catch (Exception $e) {
            Logger::error("Process::addTaskToProcess: " . $e->getMessage());
            throw new Exception("Error al agregar tarea al proceso");
        }
    }

    /**
     * Eliminar tarea de un proceso
     */
    public function removeTaskFromProcess(int $processTaskId): bool
    {
        try {
            $query = "DELETE FROM {$this->tasksTable} WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([$processTaskId]);

            return $result && $stmt->rowCount() > 0;
        } catch (Exception $e) {
            Logger::error("Process::removeTaskFromProcess: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Limpiar todas las tareas de un proceso
     */
    public function clearProcessTasks(int $processId): bool
    {
        try {
            $query = "DELETE FROM {$this->tasksTable} WHERE proveedor_proceso_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$processId]);

            return true;
        } catch (Exception $e) {
            Logger::error("Process::clearProcessTasks: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtener tareas por proveedor
     */
    public function getTasksByProvider(int $proveedorId): array
    {
        try {
            $query = "
                SELECT
                    t.id,
                    t.nombre,
                    t.descripcion,
                    t.tarea_categoria_id,
                    tc.nombre as categoria_nombre,
                    et.nombre as estado_nombre
                FROM tareas t
                LEFT JOIN tarea_categorias tc ON t.tarea_categoria_id = tc.id
                LEFT JOIN estado_tipos et ON t.estado_tipo_id = et.id
                WHERE t.proveedor_id = ? AND t.estado_tipo_id != 4
                ORDER BY t.nombre ASC
            ";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$proveedorId]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            Logger::error("Process::getTasksByProvider: " . $e->getMessage());
            throw new Exception("Error al obtener tareas por proveedor");
        }
    }

    /**
     * Obtener tareas filtradas por proveedor y categoria
     */
    public function getTasksFiltered(int $proveedorId, ?int $categoriaId = null): array
    {
        try {
            $query = "
                SELECT
                    t.id,
                    t.nombre,
                    t.descripcion,
                    t.tarea_categoria_id,
                    tc.nombre as categoria_nombre,
                    et.nombre as estado_nombre
                FROM tareas t
                LEFT JOIN tarea_categorias tc ON t.tarea_categoria_id = tc.id
                LEFT JOIN estado_tipos et ON t.estado_tipo_id = et.id
                WHERE t.proveedor_id = ? AND t.estado_tipo_id != 4
            ";

            $params = [$proveedorId];

            if ($categoriaId) {
                $query .= " AND t.tarea_categoria_id = ?";
                $params[] = $categoriaId;
            }

            $query .= " ORDER BY t.nombre ASC";

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            Logger::error("Process::getTasksFiltered: " . $e->getMessage());
            throw new Exception("Error al obtener tareas filtradas");
        }
    }

    /**
     * Obtener todas las categorias de tareas
     */
    public function getTaskCategories(): array
    {
        try {
            $query = "
                SELECT
                    id,
                    nombre
                FROM tarea_categorias
                ORDER BY nombre ASC
            ";

            $stmt = $this->db->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            Logger::error("Process::getTaskCategories: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener detalle de tarea por ID
     */
    public function getTaskDetail(int $tareaId): ?array
    {
        try {
            $query = "
                SELECT
                    t.*,
                    tc.nombre as categoria_nombre,
                    et.nombre as estado_nombre
                FROM tareas t
                LEFT JOIN tarea_categorias tc ON t.tarea_categoria_id = tc.id
                LEFT JOIN estado_tipos et ON t.estado_tipo_id = et.id
                WHERE t.id = ?
            ";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$tareaId]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (Exception $e) {
            Logger::error("Process::getTaskDetail: " . $e->getMessage());
            throw new Exception("Error al obtener detalle de tarea");
        }
    }

    /**
     * Verificar si existe un proceso con el mismo nombre para el proveedor
     */
    public function processExistsByName(string $nombre, int $proveedorId, ?int $excludeId = null): bool
    {
        try {
            $query = "SELECT id FROM {$this->table} WHERE nombre = ? AND proveedor_id = ?";
            $params = [$nombre, $proveedorId];

            if ($excludeId) {
                $query .= " AND id != ?";
                $params[] = $excludeId;
            }

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);

            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            Logger::error("Process::processExistsByName: " . $e->getMessage());
            return false;
        }
    }
}
