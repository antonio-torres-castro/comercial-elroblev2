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
                    pt.tarea_id,
                    t.nombre as tarea_nombre,
                    t.descripcion,
                    pt.fecha_inicio,
                    pt.duracion_horas,
                    pt.prioridad,
                    pt.fecha_Creado,
                    p.id as proyecto_id,
                    CONCAT('Proyecto para ', c.razon_social) as proyecto_nombre,
                    c.razon_social as cliente_nombre,
                    tt.nombre as tipo_tarea,
                    et.nombre as estado,
                    et.id as estado_tipo_id,
                    plan.nombre_usuario as planificador_nombre,
                    exec.nombre_usuario as ejecutor_nombre,
                    super.nombre_usuario as supervisor_nombre
                FROM proyecto_tareas pt
                INNER JOIN tareas t ON pt.tarea_id = t.id
                INNER JOIN proyectos p ON pt.proyecto_id = p.id
                INNER JOIN clientes c ON p.cliente_id = c.id
                INNER JOIN tarea_tipos tt ON p.tarea_tipo_id = tt.id
                INNER JOIN estado_tipos et ON pt.estado_tipo_id = et.id
                INNER JOIN usuarios plan ON pt.planificador_id = plan.id
                LEFT JOIN usuarios exec ON pt.ejecutor_id = exec.id
                LEFT JOIN usuarios super ON pt.supervisor_id = super.id
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
                $sql .= " AND (pt.ejecutor_id = ? OR pt.planificador_id = ? OR pt.supervisor_id = ?)";
                $params[] = $filters['usuario_id'];
                $params[] = $filters['usuario_id'];
                $params[] = $filters['usuario_id'];
            }

            $sql .= " ORDER BY pt.fecha_inicio DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                    t.nombre as tarea_nombre,
                    t.descripcion as tarea_descripcion,
                    p.id as proyecto_id,
                    CONCAT('Proyecto para ', c.razon_social) as proyecto_nombre,
                    c.razon_social as cliente_nombre,
                    tt.nombre as tipo_tarea,
                    et.nombre as estado,
                    plan.nombre_usuario as planificador_nombre,
                    exec.nombre_usuario as ejecutor_nombre,
                    super.nombre_usuario as supervisor_nombre
                FROM proyecto_tareas pt
                INNER JOIN tareas t ON pt.tarea_id = t.id
                INNER JOIN proyectos p ON pt.proyecto_id = p.id
                INNER JOIN clientes c ON p.cliente_id = c.id
                INNER JOIN tarea_tipos tt ON p.tarea_tipo_id = tt.id
                INNER JOIN estado_tipos et ON pt.estado_tipo_id = et.id
                INNER JOIN usuarios plan ON pt.planificador_id = plan.id
                LEFT JOIN usuarios exec ON pt.ejecutor_id = exec.id
                LEFT JOIN usuarios super ON pt.supervisor_id = super.id
                WHERE pt.id = ?
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("Error en Task::getById: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Crear nueva tarea en proyecto
     */
    public function create(array $data): ?int
    {
        try {
            $this->db->beginTransaction();
            
            // Primero crear la tarea en el catálogo general si no existe
            if (!empty($data['nueva_tarea_nombre'])) {
                $stmt = $this->db->prepare("
                    INSERT INTO tareas (nombre, descripcion, estado_tipo_id) 
                    VALUES (?, ?, 1)
                ");
                $stmt->execute([
                    $data['nueva_tarea_nombre'],
                    $data['nueva_tarea_descripcion'] ?? '',
                ]);
                $tareaId = $this->db->lastInsertId();
            } else {
                $tareaId = $data['tarea_id'];
            }

            // Luego crear la asignación proyecto-tarea
            $stmt = $this->db->prepare("
                INSERT INTO proyecto_tareas (
                    proyecto_id, 
                    tarea_id, 
                    planificador_id,
                    ejecutor_id,
                    supervisor_id,
                    fecha_inicio, 
                    duracion_horas, 
                    prioridad,
                    estado_tipo_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $result = $stmt->execute([
                $data['proyecto_id'],
                $tareaId,
                $data['planificador_id'],
                $data['ejecutor_id'] ?? null,
                $data['supervisor_id'] ?? null,
                $data['fecha_inicio'],
                $data['duracion_horas'] ?? 1.0,
                $data['prioridad'] ?? 0,
                $data['estado_tipo_id'] ?? 1 // Estado "Creado" por defecto
            ]);

            if ($result) {
                $this->db->commit();
                return $this->db->lastInsertId();
            } else {
                $this->db->rollBack();
                return null;
            }
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error en Task::create: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Actualizar tarea en proyecto
     */
    public function update(int $id, array $data): bool
    {
        try {
            $sql = "
                UPDATE proyecto_tareas 
                SET 
                    proyecto_id = ?, 
                    planificador_id = ?,
                    ejecutor_id = ?, 
                    supervisor_id = ?,
                    fecha_inicio = ?, 
                    duracion_horas = ?, 
                    prioridad = ?,
                    estado_tipo_id = ?,
                    fecha_modificacion = CURRENT_TIMESTAMP
                WHERE id = ?
            ";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['proyecto_id'],
                $data['planificador_id'],
                $data['ejecutor_id'] ?? null,
                $data['supervisor_id'] ?? null,
                $data['fecha_inicio'],
                $data['duracion_horas'] ?? 1.0,
                $data['prioridad'] ?? 0,
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
     * Obtener tipos de tareas disponibles (catálogo general)
     */
    public function getTaskTypes(): array
    {
        try {
            $sql = "SELECT id, nombre, descripcion FROM tareas WHERE estado_tipo_id != 4 ORDER BY nombre";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            $sql = "
                SELECT p.id, CONCAT('Proyecto para ', c.razon_social) as nombre, c.razon_social as cliente_nombre
                FROM proyectos p
                INNER JOIN clientes c ON p.cliente_id = c.id
                WHERE p.estado_tipo_id IN (1, 2, 5) 
                ORDER BY c.razon_social
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                WHERE u.estado_tipo_id = 2
                ORDER BY p.nombre
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            $sql = "
                SELECT id, nombre, descripcion 
                FROM estado_tipos 
                WHERE id IN (1, 2, 5, 6, 7, 8) 
                ORDER BY id
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en Task::getTaskStates: " . $e->getMessage());
            return [];
        }
    }
}