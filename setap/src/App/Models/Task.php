<?php

namespace App\Models;

use App\Config\Database;
use PDO;
use PDOException;
use Exception;

class Task
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Obtener todas las tareas con información relacionada
     */
    public function getAll(array $filters = []): array
    {
        try {
            $sql = "
                SELECT 
                    pt.id,
                    pt.nombre as tarea_nombre,
                    pt.descripcion,
                    pt.fecha_inicio,
                    pt.fecha_fin,
                    pt.fecha_Creado,
                    p.cliente_nombre,
                    p.id as proyecto_id,
                    tt.nombre as tipo_tarea,
                    et.nombre as estado,
                    et.id as estado_tipo_id,
                    u.nombre_usuario as asignado_a
                FROM proyecto_tareas pt
                INNER JOIN proyectos p ON pt.proyecto_id = p.id
                INNER JOIN tarea_tipos tt ON pt.tarea_tipo_id = tt.id
                INNER JOIN estado_tipos et ON pt.estado_tipo_id = et.id
                LEFT JOIN usuarios u ON pt.usuario_id = u.id
                WHERE pt.estado_tipo_id != 4
            ";

            $params = [];

            // Filtros
            if (!empty($filters['proyecto_id'])) {
                $sql .= " AND pt.proyecto_id = ?";
                $params[] = $filters['proyecto_id'];
            }

            if (!empty($filters['estado_tipo_id'])) {
                $sql .= " AND pt.estado_tipo_id = ?";
                $params[] = $filters['estado_tipo_id'];
            }

            if (!empty($filters['usuario_id'])) {
                $sql .= " AND pt.usuario_id = ?";
                $params[] = $filters['usuario_id'];
            }

            $sql .= " ORDER BY pt.fecha_inicio DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error en Task::getAll: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener tarea por ID
     */
    public function getById(int $id): ?array
    {
        try {
            $sql = "
                SELECT 
                    pt.*,
                    p.cliente_nombre,
                    p.id as proyecto_id,
                    tt.nombre as tipo_tarea,
                    et.nombre as estado,
                    u.nombre_usuario as asignado_a
                FROM proyecto_tareas pt
                INNER JOIN proyectos p ON pt.proyecto_id = p.id
                INNER JOIN tarea_tipos tt ON pt.tarea_tipo_id = tt.id
                INNER JOIN estado_tipos et ON pt.estado_tipo_id = et.id
                LEFT JOIN usuarios u ON pt.usuario_id = u.id
                WHERE pt.id = ?
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            error_log("Error en Task::getById: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Crear nueva tarea
     */
    public function create(array $data): ?int
    {
        try {
            $sql = "
                INSERT INTO proyecto_tareas (
                    proyecto_id, 
                    tarea_tipo_id, 
                    nombre, 
                    descripcion, 
                    fecha_inicio, 
                    fecha_fin, 
                    usuario_id, 
                    estado_tipo_id, 
                    fecha_Creado
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ";

            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['proyecto_id'],
                $data['tarea_tipo_id'],
                $data['nombre'],
                $data['descripcion'] ?? '',
                $data['fecha_inicio'],
                $data['fecha_fin'],
                $data['usuario_id'] ?? null,
                $data['estado_tipo_id'] ?? 1 // Estado "Creado" por defecto
            ]);

            return $result ? $this->db->lastInsertId() : null;
        } catch (PDOException $e) {
            error_log("Error en Task::create: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Actualizar tarea
     */
    public function update(int $id, array $data): bool
    {
        try {
            $sql = "
                UPDATE proyecto_tareas 
                SET 
                    proyecto_id = ?, 
                    tarea_tipo_id = ?, 
                    nombre = ?, 
                    descripcion = ?, 
                    fecha_inicio = ?, 
                    fecha_fin = ?, 
                    usuario_id = ?, 
                    estado_tipo_id = ?,
                    fecha_modificacion = NOW()
                WHERE id = ?
            ";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['proyecto_id'],
                $data['tarea_tipo_id'],
                $data['nombre'],
                $data['descripcion'] ?? '',
                $data['fecha_inicio'],
                $data['fecha_fin'],
                $data['usuario_id'] ?? null,
                $data['estado_tipo_id'],
                $id
            ]);
        } catch (PDOException $e) {
            error_log("Error en Task::update: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar tarea (soft delete)
     */
    public function delete(int $id): bool
    {
        try {
            $sql = "UPDATE proyecto_tareas SET estado_tipo_id = 4, fecha_modificacion = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error en Task::delete: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener tipos de tareas disponibles
     */
    public function getTaskTypes(): array
    {
        try {
            $sql = "SELECT id, nombre FROM tarea_tipos WHERE estado_tipo_id = 1 ORDER BY nombre";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error en Task::getTaskTypes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener proyectos disponibles
     */
    public function getProjects(): array
    {
        try {
            $sql = "SELECT id, cliente_nombre FROM proyectos WHERE estado_tipo_id IN (1, 2, 5) ORDER BY cliente_nombre";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error en Task::getProjects: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener usuarios disponibles para asignación
     */
    public function getUsers(): array
    {
        try {
            $sql = "
                SELECT u.id, u.nombre_usuario, p.nombre as nombre_completo
                FROM usuarios u
                INNER JOIN personas p ON u.persona_id = p.id
                WHERE u.estado_tipo_id = 1 
                ORDER BY p.nombre
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error en Task::getUsers: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener estados de tareas
     */
    public function getTaskStates(): array
    {
        try {
            $sql = "SELECT id, nombre FROM estado_tipos WHERE contexto = 'tareas' ORDER BY id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error en Task::getTaskStates: " . $e->getMessage());
            return [];
        }
    }
}